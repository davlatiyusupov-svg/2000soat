<?php
// webhook.php - Taskora AI Telegram Bot in PHP using php-telegram-bot/core

// Composer autoload (assume installed via composer require php-telegram-bot/core)
require __DIR__ . '/vendor/autoload.php';

use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\DB;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\InlineKeyboard;

// Bot token and admin IDs
$bot_token = '8394787578:AAGMij7l-p3NVrvr3LMsklwUDAgCQTBrf4Y'; // Replace with your bot token
$bot_username = 'taskora_bot'; // Replace with your bot username
$admin_ids = [123456789]; // Replace with your Telegram ID

try {
    // Create Telegram API object
    $telegram = new Telegram($bot_token, $bot_username);

    // Enable SQLite
    $pdo = new PDO('sqlite:taskora.db');
    DB::setPdo($pdo);

    // Handle webhook update
    $update = $telegram->handle();

} catch (TelegramException $e) {
    // Log telegram errors
    error_log($e->getMessage());
}

// Multilingual translations
$translations = [
    'uz' => [
        'greeting' => "Assalomu alaykum! Ishchilaringizni ro'yxatini yuritamiz. Iltimos, tilni tanlang:",
        'phone_request' => "Iltimos, telefon raqamingizni yuboring:",
        'group_instruction' => "Guruhni yarating va botni admin qiling. Bajarildi?",
        'work_hours' => "Ish vaqtingizni kiriting (misol: 09:00-18:00)",
        'attendance_buttons' => ["Keldim", "Ketdim"],
        'daily_report' => "Kunlik hisobot:",
        'card_update' => "Iltimos, yangi karta raqamini kiriting:",
        'admin_panel' => "Admin panel:",
        'advertisement' => "Reklamani yuboring:",
        'view_requests' => "Murojaatlarni ko‘ring:",
        'new_admin' => "Yangi admin qo‘shish:",
        'subscription_free' => "Birinchi 3 kun bepul!",
        'subscription_frozen' => "Akkount muzlatildi. Iltimos, to'lov qiling: 49 ming/oy yoki 420 ming/yil",
        'payment_pending' => "To'lov tasdiqlanmoqda...",
        'subscription_active' => "Obuna faol! {duration} muddatga",
    ],
    'ru' => [
        'greeting' => "Здравствуйте! Мы ведем учет ваших сотрудников. Пожалуйста, выберите язык:",
        'phone_request' => "Пожалуйста, отправьте ваш номер телефона:",
        'group_instruction' => "Создайте группу и добавьте бота администратором. Выполнено?",
        'work_hours' => "Введите рабочие часы (например: 09:00-18:00)",
        'attendance_buttons' => ["Prishel", "Ushel"],
        'daily_report' => "Ежедневный отчет:",
        'card_update' => "Пожалуйста, введите новый номер карты:",
        'admin_panel' => "Панель администратора:",
        'advertisement' => "Отправить рекламу:",
        'view_requests' => "Просмотреть запросы:",
        'new_admin' => "Добавить нового администратора:",
        'subscription_free' => "Первые 3 дня бесплатно!",
        'subscription_frozen' => "Аккаунт заморожен. Пожалуйста, оплатите: 49 тыс/мес или 420 тыс/год",
        'payment_pending' => "Оплата подтверждается...",
        'subscription_active' => "Подписка активна! На {duration}",
    ]
];

// Create tables if not exist
$pdo->exec("CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY, telegram_id INTEGER, username TEXT, phone TEXT, language TEXT DEFAULT 'uz', is_director INTEGER DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, subscription_start DATETIME, subscription_type TEXT, subscription_end DATETIME)");
$pdo->exec("CREATE TABLE IF NOT EXISTS groups (id INTEGER PRIMARY KEY, group_id INTEGER, title TEXT, working_hours TEXT, director_id INTEGER)");
$pdo->exec("CREATE TABLE IF NOT EXISTS attendance (id INTEGER PRIMARY KEY, user_id INTEGER, group_id INTEGER, action TEXT, timestamp DATETIME DEFAULT CURRENT_TIMESTAMP, date DATE)");
$pdo->exec("CREATE TABLE IF NOT EXISTS payments (id INTEGER PRIMARY KEY, user_id INTEGER, amount REAL, status TEXT DEFAULT 'pending', created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");

// Check subscription status
function checkSubscription($user_id, $pdo) {
    $stmt = $pdo->prepare("SELECT subscription_start, subscription_type, subscription_end FROM users WHERE telegram_id = :user_id");
    $stmt->execute(['user_id' => $user_id]);
    $sub = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sub || !$sub['subscription_start']) {
        // First time, start free trial
        $start = new DateTime();
        $end = new DateTime('+3 days');
        $stmt = $pdo->prepare("UPDATE users SET subscription_start = :start, subscription_type = 'trial', subscription_end = :end WHERE telegram_id = :user_id");
        $stmt->execute(['start' => $start->format('Y-m-d H:i:s'), 'end' => $end->format('Y-m-d H:i:s'), 'user_id' => $user_id]);
        return 'active';
    }

    $end = new DateTime($sub['subscription_end']);
    if (new DateTime() > $end) {
        return 'frozen';
    }

    return 'active';
}

// Update subscription after payment
function updateSubscription($user_id, $type, $pdo) {
    $start = new DateTime();
    $end = ($type == 'monthly') ? new DateTime('+1 month') : new DateTime('+1 year');
    $stmt = $pdo->prepare("UPDATE users SET subscription_start = :start, subscription_type = :type, subscription_end = :end WHERE telegram_id = :user_id");
    $stmt->execute(['start' => $start->format('Y-m-d H:i:s'), 'type' => $type, 'end' => $end->format('Y-m-d H:i:s'), 'user_id' => $user_id]);
}

// Custom Start Command
class StartCommand extends \Longman\TelegramBot\Command {
    protected $name = 'start';
    protected $description = 'Start command';
    protected $usage = '/start';
    protected $version = '1.0.0';

    public function execute() {
        global $translations, $admin_ids;

        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();
        $user_id = $message->getFrom()->getId();
        $pdo = DB::getPdo();

        // Check subscription
        $sub_status = checkSubscription($user_id, $pdo);
        if ($sub_status == 'frozen') {
            $stmt = $pdo->prepare("SELECT language FROM users WHERE telegram_id = :user_id");
            $stmt->execute(['user_id' => $user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $language = $user ? $user['language'] : 'uz';

            $data = [
                'chat_id' => $chat_id,
                'text' => $translations[$language]['subscription_frozen'],
            ];

            return Request::sendMessage($data);
        }

        $stmt = $pdo->prepare("SELECT * FROM users WHERE telegram_id = :user_id");
        $stmt->execute(['user_id' => $user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $language = $user['language'];
            $reply = $translations[$language]['greeting'];
        } else {
            // Language selection
            $inline_keyboard = new InlineKeyboard([
                ['text' => 'Uzbek', 'callback_data' => 'lang_uz'],
                ['text' => 'Russian', 'callback_data' => 'lang_ru'],
            ]);

            $data = [
                'chat_id' => $chat_id,
                'text' => $translations['uz']['greeting'],
                'reply_markup' => $inline_keyboard,
            ];

            return Request::sendMessage($data);
        }

        $data = [
            'chat_id' => $chat_id,
            'text' => $reply,
        ];

        return Request::sendMessage($data);
    }
}

// Callback query handler
if ($update->getCallbackQuery()) {
    $callback = $update->getCallbackQuery();
    $callback_id = $callback->getId();
    $chat_id = $callback->getMessage()->getChat()->getId();
    $user_id = $callback->getFrom()->getId();
    $data = $callback->getData();
    $pdo = DB::getPdo();

    if (strpos($data, 'lang_') === 0) {
        $language = substr($data, 5);
        
        $stmt = $pdo->prepare("INSERT OR REPLACE INTO users (telegram_id, language) VALUES (:user_id, :language)");
        $stmt->execute(['user_id' => $user_id, 'language' => $language]);

        $text = $translations[$language]['phone_request'];

        $data = [
            'chat_id' => $chat_id,
            'text' => $text,
        ];

        Request::sendMessage($data);

        Request::answerCallbackQuery([
            'callback_query_id' => $callback_id,
            'text' => 'Til tanlandi!',
        ]);
    } elseif (strpos($data, 'att_') === 0) {
        $action = substr($data, 4);
        $today = date('Y-m-d');
        $timestamp = date('Y-m-d H:i:s');

        $stmt = $pdo->prepare("SELECT * FROM attendance WHERE user_id = :user_id AND group_id = :group_id AND date = :date AND action = :action");
        $stmt->execute(['user_id' => $user_id, 'group_id' => $chat_id, 'date' => $today, 'action' => $action]);
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("SELECT language FROM users WHERE telegram_id = :user_id");
            $stmt->execute(['user_id' => $user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $language = $user ? $user['language'] : 'uz';

            $reply = ($action == 'arrival') ? $translations[$language]['already_arrived'] : $translations[$language]['already_departed'];
        } else {
            $stmt = $pdo->prepare("INSERT INTO attendance (user_id, group_id, action, timestamp, date) VALUES (:user_id, :group_id, :action, :timestamp, :date)");
            $stmt->execute(['user_id' => $user_id, 'group_id' => $chat_id, 'action' => $action, 'timestamp' => $timestamp, 'date' => $today]);

            $stmt = $pdo->prepare("SELECT language FROM users WHERE telegram_id = :user_id");
            $stmt->execute(['user_id' => $user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $language = $user ? $user['language'] : 'uz';

            $reply = ($action == 'arrival') ? $translations[$language]['success_arrival'] : $translations[$language]['success_departure'];

            // Send sticker (replace with valid sticker ID)
            Request::sendSticker([
                'chat_id' => $chat_id,
                'sticker' => 'CAACAgIAAxkBAAEBCeRiAAH0kAAFCfYAAQABAAECAAIAAgACAAIDAAIBAAECAAIAAgACAAL4DwAB8wABAQAB' // Valid sticker ID
            ]);
        }

        Request::answerCallbackQuery([
            'callback_query_id' => $callback_id,
            'text' => $reply,
        ]);
    } elseif (strpos($data, 'admin_') === 0) {
        if (in_array($user_id, $admin_ids)) {
            $admin_action = substr($data, 6);
            // Handle admin actions like broadcast, card update, etc.
            // For example, broadcast
            if ($admin_action == 'broadcast') {
                $text = "Reklama matnini kiriting:";
            } // Add more
            $data = [
                'chat_id' => $chat_id,
                'text' => $text,
            ];
            Request::sendMessage($data);
        }
    }
}

// Message handler for phone, work hours, etc.
if ($update->getMessage()) {
    $message = $update->getMessage();
    $chat_id = $message->getChat()->getId();
    $user_id = $message->getFrom()->getId();
    $text = $message->getText();
    $pdo = DB::getPdo();

    $stmt = $pdo->prepare("SELECT language FROM users WHERE telegram_id = :user_id");
    $stmt->execute(['user_id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $language = $user ? $user['language'] : 'uz';

    // Conversation logic for phone, group, work hours
    $conversation = new Conversation($user_id, $chat_id, 'setup');
    $notes = &$conversation->notes;
    !is_array($notes) ? $notes = [] : null;
    $state = $notes['state'] ?? 0;

    switch ($state) {
        case 0:
            if (preg_match('/^\+998\d{9}$/', $text)) {
                $notes['phone'] = $text;
                $notes['state'] = 1;
                $conversation->update();

                $inline_keyboard = new InlineKeyboard([
                    ['text' => 'Bajarildi', 'callback_data' => 'group_ready'],
                ]);

                $data = [
                    'chat_id' => $chat_id,
                    'text' => $translations[$language]['group_instruction'],
                    'reply_markup' => $inline_keyboard,
                ];

                Request::sendMessage($data);
            }
            break;
        case 1:
            $notes['work_hours'] = $text;
            $notes['state'] = 2;
            $conversation->update();

            // Save to DB
            $stmt = $pdo->prepare("UPDATE users SET phone = :phone, is_director = 1 WHERE telegram_id = :user_id");
            $stmt->execute(['phone' => $notes['phone'], 'user_id' => $user_id]);

            $data = [
                'chat_id' => $chat_id,
                'text' => $translations[$language]['setup_complete'],
            ];

            Request::sendMessage($data);
            $conversation->stop();
            break;
    }

    // Group attendance buttons
    if ($message->getChat()->getType() == 'group' || $message->getChat()->getType() == 'supergroup') {
        $inline_keyboard = new InlineKeyboard([
            ['text' => $translations[$language]['attendance_buttons'][0], 'callback_data' => 'att_arrival'],
            ['text' => $translations[$language]['attendance_buttons'][1], 'callback_data' => 'att_departure'],
        ]);

        $data = [
            'chat_id' => $chat_id,
            'text' => 'Ish vaqtini belgilang:',
            'reply_markup' => $inline_keyboard,
        ];

        Request::sendMessage($data);
    }
}

// For daily report, create a separate script daily_report.php and run via cron
// daily_report.php example
/*
<?php
require __DIR__ . '/vendor/autoload.php';

use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\Request;

$bot_token = 'YOUR_BOT_TOKEN_HERE';
$telegram = new Telegram($bot_token);

$pdo = new PDO('sqlite:taskora.db');
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT * FROM attendance WHERE date = :date");
$stmt->execute(['date' => $today]);
$attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group by group_id and send to directors
foreach ($attendance as $att) {
    $group_id = $att['group_id'];
    $stmt = $pdo->prepare("SELECT director_id, language FROM groups JOIN users ON groups.director_id = users.telegram_id WHERE group_id = :group_id");
    $stmt->execute(['group_id' => $group_id]);
    $dir = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($dir) {
        $report = $translations[$dir['language']]['daily_report'] . "\n";
        $report .= "User: " . $att['user_id'] . " Action: " . $att['action'] . " Time: " . $att['timestamp'] . "\n";
        Request::sendMessage([
            'chat_id' => $dir['director_id'],
            'text' => $report,
        ]);
    }
}
?>
*/
// Cron example: php /path/to/daily_report.php at 00:00 daily

// Admin panel extensions for payment confirmation
if ($update->getCallbackQuery() && strpos($data, 'admin_approve_payment') === 0) {
    if (in_array($user_id, $admin_ids)) {
        $payment_id = substr($data, 20);
        $stmt = $pdo->prepare("UPDATE payments SET status = 'confirmed' WHERE id = :id");
        $stmt->execute(['id' => $payment_id]);

        $stmt = $pdo->prepare("SELECT user_id, amount FROM payments WHERE id = :id");
        $stmt->execute(['id' => $payment_id]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        $type = ($payment['amount'] == 49000) ? 'monthly' : 'yearly';
        updateSubscription($payment['user_id'], $type, $pdo);

        Request::answerCallbackQuery([
            'callback_query_id' => $callback_id,
            'text' => 'To\'lov tasdiqlandi!',
        ]);
    }
}

// Payment request logic (when frozen)
if ($sub_status == 'frozen') {
    $inline_keyboard = new InlineKeyboard([
        ['text' => 'Oylik (49 ming)', 'callback_data' => 'pay_monthly'],
        ['text' => 'Yillik (420 ming)', 'callback_data' => 'pay_yearly'],
    ]);

    $data = [
        'chat_id' => $chat_id,
        'text' => $translations[$language]['subscription_frozen'],
        'reply_markup' => $inline_keyboard,
    ];

    Request::sendMessage($data);
}

// Callback for payment
if (strpos($data, 'pay_') === 0) {
    $type = substr($data, 4);
    $amount = ($type == 'monthly') ? 49000 : 420000;

    $stmt = $pdo->prepare("INSERT INTO payments (user_id, amount) VALUES (:user_id, :amount)");
    $stmt->execute(['user_id' => $user_id, 'amount' => $amount]);

    $reply = $translations[$language]['payment_pending'];

    Request::answerCallbackQuery([
        'callback_query_id' => $callback_id,
        'text' => $reply,
    ]);

    // Notify admins
    foreach ($admin_ids as $admin) {
        Request::sendMessage([
            'chat_id' => $admin,
            'text' => "Yangi to'lov: User {$user_id}, Summa {$amount}",
        ]);
    }
}

// Add commands
$telegram->addCommandClass(StartCommand::class);
$telegram->addCommandClass(AdminCommand::class);