<?php
// taskora.php
// Single-file Telegram webhook bot for "Taskora Ai bot"
// PHP 8+, PDO + MySQL required. Put this file on HTTPS-enabled hosting and set webhook to its URL.

// ---------------- CONFIG ----------------
const BOT_TOKEN = '8394787578:AAGMij7l-p3NVrvr3LMsklwUDAgCQTBrf4Y';
const API_URL = 'https://api.telegram.org/bot'.BOT_TOKEN.'/';

$db = (object)[
    'host' => '127.0.0.1',
    'name' => 'taskora_ai',
    'user' => 'dbuser',
    'pass' => 'dbpass',
    'charset' => 'utf8mb4'
];

// Admin TG IDs (array of integers). Replace with real admin ids.
$ADMIN_IDS = [6067477588];

// Default group chat id or username (used for cron messages). Use '@yourgroup' or numeric chat id.
$GROUP_CHAT = '@yourgroupusername_or_id';

// Card info shown to users for manual payment (editable via admin panel).
$DEFAULT_CARD = "8600 1234 5678 9012 | Ism Familiyangiz";

// Cron secret (use a long random string). Use when calling cron endpoints: https://site/taskora.php?cron=morning&secret=LONG
const CRON_SECRET = 'replace_with_long_secret_string';

// Trial days and prices (so‘zsiz sonlar)
const TRIAL_DAYS = 3;
$PRICES = [
    '1m' => 49000,
    '3m' => 147000,
    '1y' => 468000
];

// ---------------- END CONFIG ----------------

// Basic helpers
function apiRequest($method, $params = []) {
    $url = API_URL . $method;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
    $res = curl_exec($ch);
    if ($res === false) {
        error_log("Curl error: ".curl_error($ch));
        return null;
    }
    return json_decode($res, true);
}

function sendMessage($chat_id, $text, $opts = []) {
    $data = array_merge(['chat_id'=>$chat_id,'text'=>$text,'parse_mode'=>'HTML'], $opts);
    return apiRequest('sendMessage', $data);
}

function sendPhoto($chat_id, $photo_file_id, $caption = '', $opts = []) {
    $data = array_merge(['chat_id'=>$chat_id,'photo'=>$photo_file_id,'caption'=>$caption,'parse_mode'=>'HTML'], $opts);
    return apiRequest('sendPhoto', $data);
}

function answerCallback($callback_id, $text = '', $show_alert = false) {
    return apiRequest('answerCallbackQuery', ['callback_query_id'=>$callback_id,'text'=>$text,'show_alert'=>$show_alert]);
}

// DB connection
try {
    $dsn = "mysql:host={$db->host};charset={$db->charset}";
    $pdo = new PDO($dsn, $db->user, $db->pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    // create DB if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db->name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$db->name}`;");
} catch (Exception $e) {
    http_response_code(500);
    echo "DB connection error";
    error_log($e->getMessage());
    exit;
}

// Create required tables if not exist
$pdo->exec("
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tg_id BIGINT NOT NULL UNIQUE,
  first_name VARCHAR(255) DEFAULT '',
  last_name VARCHAR(255) DEFAULT '',
  username VARCHAR(255) DEFAULT '',
  phone VARCHAR(50) DEFAULT NULL,
  lang VARCHAR(5) DEFAULT 'uz',
  work_hours VARCHAR(20) DEFAULT NULL,
  is_admin TINYINT(1) DEFAULT 0,
  active_until DATE DEFAULT NULL,
  trial_started DATE DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS attendance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  tg_id BIGINT,
  date DATE,
  came_at TIME DEFAULT NULL,
  left_at TIME DEFAULT NULL,
  note VARCHAR(255) DEFAULT NULL,
  status ENUM('present','absent','partial') DEFAULT 'present',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_user_date (tg_id, date),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  tg_id BIGINT DEFAULT NULL,
  amount INT,
  period ENUM('1m','3m','1y'),
  status ENUM('waiting','confirmed','rejected') DEFAULT 'waiting',
  proof_file_id VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS settings (
  k VARCHAR(64) PRIMARY KEY,
  v TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// ensure some default settings
$stmt = $pdo->prepare("INSERT IGNORE INTO settings (k,v) VALUES (?,?)");
$stmt->execute(['card_info', $DEFAULT_CARD]);
$stmt->execute(['trial_days', TRIAL_DAYS]);
$stmt->execute(['bot_status', 'running']); // running or stopped
$stmt->execute(['payment_accept', '1']); // 1 allow, 0 disallow

// ---------------- Utility DB functions ----------------
function getUserByTg($pdo, $tg_id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE tg_id = ?");
    $stmt->execute([$tg_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
function createOrUpdateUser($pdo, $from) {
    $u = getUserByTg($pdo, $from['id']);
    if (!$u) {
        $stmt = $pdo->prepare("INSERT INTO users (tg_id, first_name, last_name, username, lang, trial_started, active_until) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $trial_start = date('Y-m-d');
        $active_until = date('Y-m-d', strtotime("+".TRIAL_DAYS." days"));
        $stmt->execute([$from['id'], $from['first_name'] ?? '', $from['last_name'] ?? '', $from['username'] ?? '', 'uz', $trial_start, $active_until]);
        return getUserByTg($pdo, $from['id']);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET first_name=?, last_name=?, username=? WHERE tg_id=?");
        $stmt->execute([$from['first_name'] ?? '', $from['last_name'] ?? '', $from['username'] ?? '', $from['id']]);
        return getUserByTg($pdo, $from['id']);
    }
}

function isBotRunning($pdo) {
    $stmt = $pdo->prepare("SELECT v FROM settings WHERE k='bot_status'");
    $stmt->execute();
    $v = $stmt->fetchColumn();
    return $v === 'running';
}
function paymentAccept($pdo) {
    $stmt = $pdo->prepare("SELECT v FROM settings WHERE k='payment_accept'");
    $stmt->execute();
    return $stmt->fetchColumn() === '1';
}
function getSetting($pdo, $k) {
    $stmt = $pdo->prepare("SELECT v FROM settings WHERE k=?");
    $stmt->execute([$k]);
    return $stmt->fetchColumn();
}
function setSetting($pdo, $k, $v) {
    $stmt = $pdo->prepare("REPLACE INTO settings (k,v) VALUES (?,?)");
    $stmt->execute([$k, $v]);
}

// ---------------- End DB utils ----------------

// Read update
$update_raw = file_get_contents('php://input');
$update = json_decode($update_raw, true);

// Allow calling cron endpoints via GET with secret
if (php_sapi_name() !== 'cli' && isset($_GET['cron']) && isset($_GET['secret']) && $_GET['secret'] === CRON_SECRET) {
    $cron = $_GET['cron'];
    if ($cron === 'morning') {
        cronMorning($pdo);
        exit('ok');
    } elseif ($cron === 'night') {
        cronNight($pdo);
        exit('ok');
    } else {
        exit('unknown cron');
    }
}

// Helper: build inline keyboard JSON quickly
function ikb($rows) {
    return json_encode(['inline_keyboard'=>$rows], JSON_UNESCAPED_UNICODE);
}

// Build main group attendance message keyboard (keldim/ketdim)
$attendance_kb = ikb([[['text'=>'Keldim','callback_data'=>'keldim'], ['text'=>'Ketdim','callback_data'=>'ketdim']]]);

// ---------------- Handlers ----------------
if ($update) {
    // message handler
    if (isset($update['message'])) {
        $msg = $update['message'];
        $chat_id = $msg['chat']['id'];
        $from = $msg['from'] ?? null;

        // if bot is set to stopped, notify only admins (simple)
        if (!isBotRunning($pdo)) {
            if (!in_array($from['id'], $GLOBALS['ADMIN_IDS'])) {
                sendMessage($chat_id, "Bot hozir texnik ishlarda. Keyinroq urinib ko'ring.");
                exit;
            }
        }

        // register / update user
        if ($from) createOrUpdateUser($pdo, $from);

        // text commands
        if (isset($msg['text'])) {
            $text = trim($msg['text']);

            // Start
            if (str_starts_with($text, '/start')) {
                // welcome and language selection inline
                $keyboard = ikb([[['text'=>"O'zbekcha",'callback_data'=>'lang_uz'], ['text'=>'Русский','callback_data'=>'lang_ru']]]);
                sendMessage($chat_id, "Assalomu alaykum. Tilni tanlang / Выберите язык:", ['reply_markup'=>$keyboard]);
                exit;
            }

            if ($text === '/admin') {
                // only allow admin ids
                if (!in_array($from['id'], $GLOBALS['ADMIN_IDS'])) {
                    sendMessage($chat_id, "Siz admin emassiz.");
                    exit;
                }
                // admin menu (inline)
                $menu = ikb([
                    [['text'=>'Reklama / Notification yuborish','callback_data'=>'admin_notify']],
                    [['text'=>'Obunalarni tekshirish','callback_data'=>'admin_payments']],
                    [['text'=>'Yangi admin qo\'shish','callback_data'=>'admin_add']],
                    [['text'=>'Botni stop qilish','callback_data'=>'admin_stop']],
                    [['text'=>'Botni qayta ishga tushurish','callback_data'=>'admin_start']],
                    [['text'=>'Kartani almashtirish','callback_data'=>'admin_change_card']],
                    [['text'=>'To\'lov qabulini o\'chirish/yoqish','callback_data'=>'admin_toggle_payment']]
                ]);
                sendMessage($chat_id, "Admin panel:", ['reply_markup'=>$menu]);
                exit;
            }

            // If user sends work hours like 09:00-18:00
            if (preg_match('/^\d{2}:\d{2}-\d{2}:\d{2}$/', $text)) {
                $stmt = $pdo->prepare("UPDATE users SET work_hours=? WHERE tg_id=?");
                $stmt->execute([$text, $from['id']]);
                $trial_days = (int)getSetting($pdo,'trial_days');
                $msgt = "Ish vaqti saqlandi: <b>{$text}</b>.\nEndi botni guruhga qo'shib admin qiling. 3 kun bepul sinab ko'rish beriladi.";
                sendMessage($chat_id, $msgt);
                exit;
            }

            // Payment initiator: show plans
            if ($text === '/pay' || $text === 'To\'lov qildim' || $text === 'Оплатил') {
                $card = getSetting($pdo,'card_info');
                $kb = ikb([
                    [['text'=>"1 oy — {$GLOBALS['PRICES']['1m']} so'm",'callback_data'=>'buy_1m']],
                    [['text'=>"3 oy — {$GLOBALS['PRICES']['3m']} so'm",'callback_data'=>'buy_3m']],
                    [['text'=>"1 yil — {$GLOBALS['PRICES']['1y']} so'm",'callback_data'=>'buy_1y']],
                    [['text'=>'Kartani ko\'rsatish','callback_data'=>'show_card']],
                    [['text'=>"To'lov qildim (skrinshot yuborish)",'callback_data'=>'paid_i_clicked']]
                ]);
                sendMessage($chat_id, "To'lov rejalari:\n\n1 oy: {$GLOBALS['PRICES']['1m']} so'm\n3 oy: {$GLOBALS['PRICES']['3m']} so'm\n1 yil: {$GLOBALS['PRICES']['1y']} so'm\n\nKartaga to‘lov qilib, so‘ngras skrinshot yuboring.", ['reply_markup'=>$kb]);
                exit;
            }

            // Murojaat
            if ($text === 'Murojaat yuborish' || $text === 'Обратиться') {
                sendMessage($chat_id, "Iltimos, muammo haqida batafsil yozing va kerak bo'lsa skrinshot yuboring. Adminlar tekshiradi.");
                exit;
            }
        }

        // contact (reply keyboard with request_contact sends this)
        if (isset($msg['contact'])) {
            $contact = $msg['contact']['phone_number'];
            $stmt = $pdo->prepare("UPDATE users SET phone=? WHERE tg_id=?");
            $stmt->execute([$contact, $from['id']]);
            sendMessage($chat_id, "Telefon qabul qilindi: {$contact}\nIltimos ish vaqtingizni formatda kiriting (misol: 09:00-18:00).");
            exit;
        }

        // photo (likely payment proof)
        if (isset($msg['photo'])) {
            // find largest size
            $photos = $msg['photo'];
            $largest = end($photos)['file_id'];
            $stmt = $pdo->prepare("SELECT id FROM users WHERE tg_id=?");
            $stmt->execute([$from['id']]);
            $u = $stmt->fetch(PDO::FETCH_ASSOC);
            $user_id = $u['id'] ?? null;
            // simple default: 1m if not specified
            $stmt = $pdo->prepare("INSERT INTO payments (user_id, tg_id, amount, period, status, proof_file_id) VALUES (?, ?, ?, ?, 'waiting', ?)");
            $stmt->execute([$user_id, $from['id'], $GLOBALS['PRICES']['1m'], '1m', $largest]);
            $pay_id = $pdo->lastInsertId();
            // notify admins with confirm/reject buttons
            $kb = ikb([[['text'=>'Tasdiqlash','callback_data'=>"admin_confirm_{$pay_id}"], ['text'=>'Bekor qilish','callback_data'=>"admin_reject_{$pay_id}"]]]);
            foreach ($GLOBALS['ADMIN_IDS'] as $aid) {
                sendPhoto($aid, $largest, "To'lov tasdiqi: @{$from['username']} (ID: {$from['id']})\nPayment ID: {$pay_id}", ['reply_markup'=>$kb]);
            }
            sendMessage($chat_id, "Skrinshot qabul qilindi. Moderatorlar 8 soat ichida tekshiradi.");
            exit;
        }

    } // end message

    // Callback queries (inline buttons)
    if (isset($update['callback_query'])) {
        $cb = $update['callback_query'];
        $data = $cb['data'];
        $from = $cb['from'];
        $chat_id = $cb['message']['chat']['id'];
        $msg_id = $cb['message']['message_id'];

        // Language selection
        if ($data === 'lang_uz' || $data === 'lang_ru') {
            $lang = $data === 'lang_uz' ? 'uz' : 'ru';
            $stmt = $pdo->prepare("UPDATE users SET lang=? WHERE tg_id=?");
            $stmt->execute([$lang, $from['id']]);
            answerCallback($cb['id'], "Til saqlandi.");
            // request contact via reply keyboard (can't via inline)
            $replyKb = json_encode(['keyboard'=>[[['text'=>'📱 Telefonni yuborish','request_contact'=>true]]],'one_time_keyboard'=>true,'resize_keyboard'=>true], JSON_UNESCAPED_UNICODE);
            sendMessage($from['id'], $lang === 'uz' ? "Telefon raqamingizni tugma orqali yuboring:" : "Отправьте номер телефона через кнопку:");
            // We cannot pass reply_markup easily as JSON in this helper; use API directly:
            apiRequest('sendMessage', ['chat_id'=>$from['id'],'text'=>($lang==='uz'?"Telefon raqamingizni tugma orqali yuboring:":"Отправьте номер телефона через кнопку:"),'reply_markup'=>$replyKb]);
            exit;
        }

        // Attendance toggles
        if ($data === 'keldim' || $data === 'ketdim') {
            $today = date('Y-m-d');
            $time = date('H:i:s');
            $user = getUserByTg($pdo, $from['id']);
            if (!$user) {
                answerCallback($cb['id'], "Ro'yxatdan o'ting /start.");
                exit;
            }
            // ensure attendance row
            $stmt = $pdo->prepare("SELECT * FROM attendance WHERE tg_id=? AND date=?");
            $stmt->execute([$from['id'], $today]);
            $att = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$att) {
                $stmt = $pdo->prepare("INSERT INTO attendance (user_id, tg_id, date) VALUES (?, ?, ?)");
                $stmt->execute([$user['id'], $from['id'], $today]);
                $att_id = $pdo->lastInsertId();
            } else {
                $att_id = $att['id'];
            }
            if ($data === 'keldim') {
                $stmt = $pdo->prepare("UPDATE attendance SET came_at=? WHERE id=?");
                $stmt->execute([substr($time,0,8), $att_id]);
                answerCallback($cb['id'], "Keldi: ".substr($time,0,5));
                // broadcast to group
                sendMessage($chat_id, "{$user['first_name']} keldi: ".substr($time,0,5));
                exit;
            } else {
                $stmt = $pdo->prepare("UPDATE attendance SET left_at=? WHERE id=?");
                $stmt->execute([substr($time,0,8), $att_id]);
                answerCallback($cb['id'], "Ketdi: ".substr($time,0,5));
                sendMessage($chat_id, "{$user['first_name']} ketdi: ".substr($time,0,5));
                exit;
            }
        }

        // Payment flow: show card
        if ($data === 'show_card') {
            $card = getSetting($pdo,'card_info');
            answerCallback($cb['id'], "Kartani ko'rsatildi.");
            sendMessage($from['id'], "To'lov qilish uchun kartamiz:\n<code>{$card}</code>\nTo'lov qildim tugmasini bosib, skrinshot yuboring.");
            exit;
        }

        // User clicked buy plan
        if (str_starts_with($data,'buy_')) {
            $period = substr($data,4);
            $amount = $GLOBALS['PRICES'][$period] ?? null;
            if (!$amount) {
                answerCallback($cb['id'], "Xato reja.");
                exit;
            }
            answerCallback($cb['id'], "Tanlandi: {$period}");
            $card = getSetting($pdo,'card_info');
            $kb = ikb([[['text'=>"To'lov qildim (skrinshot yuborish)", 'callback_data'=>'paid_i_clicked']]]);
            sendMessage($from['id'], "Iltimos {$amount} so'mni quyidagi karta raqamiga o'tkazing:\n<code>{$card}</code>\nSo'ngra 'To'lov qildim' tugmasini bosib, skrinshot yuboring.", ['reply_markup'=>$kb]);
            exit;
        }

        // User indicates they paid (flow: ask for screenshot)
        if ($data === 'paid_i_clicked') {
            answerCallback($cb['id'], "Skrinshot yuboring.");
            sendMessage($from['id'], "Skrinshot yuboring. Adminlar 8 soat ichida tekshiradi. Tasdiqlansa, hisobingiz faollashtiriladi.");
            exit;
        }

        // Admin confirm / reject payment
        if (str_starts_with($data, 'admin_confirm_') || str_starts_with($data, 'admin_reject_')) {
            // only admins
            if (!in_array($from['id'], $GLOBALS['ADMIN_IDS'])) {
                answerCallback($cb['id'], "Siz admin emassiz.");
                exit;
            }
            $parts = explode('_', $data);
            $action = $parts[1];
            $pay_id = $parts[2];
            if ($action === 'confirm') {
                $stmt = $pdo->prepare("SELECT * FROM payments WHERE id=?");
                $stmt->execute([$pay_id]);
                $p = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$p) {
                    answerCallback($cb['id'], "To'lov topilmadi.");
                    exit;
                }
                // mark confirmed
                $stmt = $pdo->prepare("UPDATE payments SET status='confirmed' WHERE id=?");
                $stmt->execute([$pay_id]);
                // extend user's active_until based on period
                $period = $p['period'] ?? '1m';
                $stmt = $pdo->prepare("SELECT * FROM users WHERE tg_id=?");
                $stmt->execute([$p['tg_id']]);
                $u = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($u) {
                    $current = $u['active_until'] && $u['active_until'] >= date('Y-m-d') ? new DateTime($u['active_until']) : new DateTime();
                    if ($period === '1m') $current->modify('+1 month');
                    if ($period === '3m') $current->modify('+3 month');
                    if ($period === '1y') $current->modify('+1 year');
                    $stmt = $pdo->prepare("UPDATE users SET active_until=? WHERE id=?");
                    $stmt->execute([$current->format('Y-m-d'), $u['id']]);
                    sendMessage($u['tg_id'], "To'lovingiz tasdiqlandi. Hisobingiz faollashtirildi: {$current->format('Y-m-d')} gacha.");
                }
                answerCallback($cb['id'], "To'lov tasdiqlandi.");
                exit;
            } else {
                $stmt = $pdo->prepare("UPDATE payments SET status='rejected' WHERE id=?");
                $stmt->execute([$pay_id]);
                $stmt = $pdo->prepare("SELECT tg_id FROM payments WHERE id=?");
                $stmt->execute([$pay_id]);
                $t = $stmt->fetchColumn();
                if ($t) sendMessage($t, "Siz yuborgan to'lov rad etildi. Iltimos murojat qiling.");
                answerCallback($cb['id'], "To'lov rad etildi.");
                exit;
            }
        }

        // Admin menu actions
        if ($data === 'admin_notify') {
            if (!in_array($from['id'], $GLOBALS['ADMIN_IDS'])) { answerCallback($cb['id'], "Siz admin emassiz."); exit; }
            answerCallback($cb['id'], "Reklama yuborish uchun admin chatga matn yuboring (men ushbu bot orqali barcha userlarga jo'nataman).");
            // store state? simple approach: admin will send /notify TEXT; implement simple command below
            exit;
        }
        if ($data === 'admin_payments') {
            if (!in_array($from['id'], $GLOBALS['ADMIN_IDS'])) { answerCallback($cb['id'], "Siz admin emassiz."); exit; }
            // list waiting payments
            $stmt = $pdo->query("SELECT * FROM payments WHERE status='waiting' ORDER BY created_at DESC LIMIT 20");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!$rows) {
                answerCallback($cb['id'], "Tasdiqlash uchun to'lovlar yo'q.");
                exit;
            }
            foreach ($rows as $r) {
                $kb = ikb([[['text'=>'Tasdiqlash','callback_data'=>"admin_confirm_{$r['id']}"], ['text'=>'Bekor','callback_data'=>"admin_reject_{$r['id']}"]]]);
                sendMessage($from['id'], "Payment ID: {$r['id']}\nTG ID: {$r['tg_id']}\nAmount: {$r['amount']}\nPeriod: {$r['period']}\nStatus: {$r['status']}", ['reply_markup'=>$kb]);
            }
            answerCallback($cb['id'], "Ro'yxat yuborildi.");
            exit;
        }

        if ($data === 'admin_add') {
            if (!in_array($from['id'], $GLOBALS['ADMIN_IDS'])) { answerCallback($cb['id'], "Siz admin emassiz."); exit; }
            answerCallback($cb['id'], "Yangi admin qo'shish uchun: /addadmin TG_ID");
            exit;
        }

        if ($data === 'admin_stop') {
            if (!in_array($from['id'], $GLOBALS['ADMIN_IDS'])) { answerCallback($cb['id'], "Siz admin emassiz."); exit; }
            setSetting($pdo,'bot_status','stopped');
            answerCallback($cb['id'], "Bot to'xtatildi.");
            exit;
        }
        if ($data === 'admin_start') {
            if (!in_array($from['id'], $GLOBALS['ADMIN_IDS'])) { answerCallback($cb['id'], "Siz admin emassiz."); exit; }
            setSetting($pdo,'bot_status','running');
            answerCallback($cb['id'], "Bot qayta ishga tushirildi.");
            exit;
        }
        if ($data === 'admin_change_card') {
            if (!in_array($from['id'], $GLOBALS['ADMIN_IDS'])) { answerCallback($cb['id'], "Siz admin emassiz."); exit; }
            answerCallback($cb['id'], "Kartani almashtirish uchun: /setcard Raqam | Ism Familiyasi");
            exit;
        }
        if ($data === 'admin_toggle_payment') {
            if (!in_array($from['id'], $GLOBALS['ADMIN_IDS'])) { answerCallback($cb['id'], "Siz admin emassiz."); exit; }
            $now = paymentAccept($pdo);
            setSetting($pdo,'payment_accept', $now ? '0' : '1');
            answerCallback($cb['id'], $now ? "To'lov qabul qilinishi o'chirildi." : "To'lov qabul qilinishi yoqildi.");
            exit;
        }

        // fallback
        answerCallback($cb['id'], "OK");
        exit;
    }
}

// ---------------- Admin text commands (simple parsing) ----------------
if ($update && isset($update['message']['text'])) {
    $text = trim($update['message']['text']);
    $chat_id = $update['message']['chat']['id'];
    $from = $update['message']['from'];
    // /notify message -> broadcast to group or all users
    if (str_starts_with($text, '/notify ') && in_array($from['id'], $GLOBALS['ADMIN_IDS'])) {
        $payload = trim(substr($text,8));
        // send to group
        if ($GROUP_CHAT) sendMessage($GROUP_CHAT, "[ADMIN] ".$payload);
        // also optionally to all users - be careful; here we send to group only.
        sendMessage($chat_id, "Yuborildi.");
        exit;
    }
    // /addadmin TG_ID
    if (str_starts_with($text, '/addadmin') && in_array($from['id'], $GLOBALS['ADMIN_IDS'])) {
        $parts = preg_split('/\s+/', $text);
        $new = $parts[1] ?? null;
        if (!$new || !is_numeric($new)) {
            sendMessage($chat_id, "To'g'ri format: /addadmin TG_ID");
            exit;
        }
        // add to users table (if not exists) and set is_admin
        $stmt = $pdo->prepare("INSERT IGNORE INTO users (tg_id, first_name) VALUES (?, ?)");
        $stmt->execute([$new, 'Admin']);
        $stmt = $pdo->prepare("UPDATE users SET is_admin=1 WHERE tg_id=?");
        $stmt->execute([$new]);
        // also append to runtime ADMIN_IDS (not persistent across reloads)
        sendMessage($chat_id, "Yangi admin qo'shildi: {$new}. Iltimos taskora.php faylida ADMIN_IDS ga qo'shing (agar kerak bo'lsa).");
        exit;
    }
    // /setcard Raqam | Ism Familiyasi
    if (str_starts_with($text, '/setcard') && in_array($from['id'], $GLOBALS['ADMIN_IDS'])) {
        $card = trim(substr($text,8));
        if (!$card) {
            sendMessage($chat_id, "Format: /setcard 8600 1234 ... | Ism Familiya");
            exit;
        }
        setSetting($pdo,'card_info',$card);
        sendMessage($chat_id, "Kart ma'lumotlari yangilandi.");
        exit;
    }

    // /report YYYY-MM for monthly report (admin)
    if (str_starts_with($text, '/report') && in_array($from['id'], $GLOBALS['ADMIN_IDS'])) {
        $arg = trim(substr($text,7));
        $month = $arg ?: date('Y-m');
        // collect attendance for that month
        $stmt = $pdo->prepare("SELECT a.*, u.first_name FROM attendance a JOIN users u ON u.tg_id = a.tg_id WHERE a.date LIKE ? ORDER BY a.date ASC");
        $stmt->execute([$month.'-%']);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $report = "Oylik hisobot: {$month}\n\n";
        if (!$rows) $report .= "Ma'lumot topilmadi.";
        else {
            foreach ($rows as $r) {
                $came = $r['came_at'] ? substr($r['came_at'],0,5) : '—';
                $left = $r['left_at'] ? substr($r['left_at'],0,5) : '—';
                $report .= "{$r['date']} | {$r['first_name']} | keldi: {$came} | ketdi: {$left}\n";
            }
        }
        sendMessage($chat_id, $report);
        exit;
    }
}

// ---------------- Cron functions ----------------
function cronMorning($pdo) {
    global $GROUP_CHAT, $attendance_kb;
    // send morning message to group with inline keldim/ketdim
    $msg = "Assalomu alaykum, ishga kelgan bo'lsangiz '<b>Keldim</b>' tugmasini bosing, ketayotgan bo'lsangiz '<b>Ketdim</b>' tugmasini bosing.";
    sendMessage($GROUP_CHAT, $msg, ['reply_markup'=>$attendance_kb]);
}

function cronNight($pdo) {
    global $GROUP_CHAT;
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT a.*, u.first_name FROM attendance a JOIN users u ON u.tg_id = a.tg_id WHERE a.date = ?");
    $stmt->execute([$today]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $report = "Hisobot: {$today}\n\n";
    if (!$rows) {
        $report .= "Hech kim kiritmagan.\n";
    } else {
        foreach ($rows as $r) {
            $came = $r['came_at'] ? substr($r['came_at'],0,5) : '—';
            $left = $r['left_at'] ? substr($r['left_at'],0,5) : '—';
            $status = (!$r['came_at'] && !$r['left_at']) ? 'mas' : ($r['came_at'] && $r['left_at'] ? 'mas' : 'qisman');
            $report .= "{$r['first_name']} — keldi: {$came} | ketdi: {$left} | holati: {$status}\n";
        }
    }
    sendMessage($GROUP_CHAT, $report);
}

// ---------------- Simple health check for webhook (optional) ----------------
if (!empty($update)) {
    // we already handled updates above
} else {
    // no incoming update: provide a simple status page if accessed by browser without cron secret
    if (php_sapi_name() !== 'cli' && empty($_GET['cron'])) {
        header('Content-Type: text/plain; charset=utf-8');
        echo "Taskora Ai bot endpoint.\nSet webhook to this URL.\nCron endpoints:\n?cron=morning&secret=YOUR_SECRET\n?cron=night&secret=YOUR_SECRET\n";
        exit;
    }
}