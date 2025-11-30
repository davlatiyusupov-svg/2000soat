<?php
ob_start();
define('8394787578:AAGMij7l-p3NVrvr3LMsklwUDAgCQTBrf4Y','8394787578:AAGMij7l-p3NVrvr3LMsklwUDAgCQTBrf4Y');
$admin = "6067477588";
$admin2 = "yusupovdavlatbek";
$botname = bot('getme',['bot'])->result->username;
$kanal = "PHP_KODLAR";

// Ushbu kod @PHP_KODLAR kanali uchun @Farxodxon tomonidan yozildi ✅

function bot($method,$datas=[]){
	$url = "https://api.telegram.org/bot".API_KEY."/".$method;
	$ch = curl_init();
	curl_setopt($ch,CURLOPT_URL,$url);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
	curl_setopt($ch,CURLOPT_POSTFIELDS,$datas);
	$res = curl_exec($ch);
	if(curl_error($ch)){
		var_dump(curl_error($ch));
	}else{
		return json_decode($res);
	}
}

function statgroup($chatid){
    $check = file_get_contents("bot/statgroup.txt");
    $rd = explode("\n",$check);
    if(!in_array($chatid,$rd)){
        file_put_contents("bot/statgroup.txt","\n".$chatid,FILE_APPEND);
    }
}

function statuser($chatid){
    $check = file_get_contents("bot/statuser.txt");
    $rd = explode("\n",$check);
    if(!in_array($chatid,$rd)){
        file_put_contents("bot/statuser.txt","\n".$chatid,FILE_APPEND);
    }
}

$update = json_decode(file_get_contents('php://input'));
$message = $update->message;
$tx = $message->text;
$photo = $message->photo;
$cid = $message->chat->id;
$mid = $message->message_id;
$text = $message->text;
$chat_id = $message->chat->id;
$message_id = $message->message_id;
$uid = $message->from->id;
$name = $message->from->first_name;
$username = $message->from->username;
$bio = $message->from->about;
$type = $message->chat->type;
$botid = bot('getme',['bot'])->result->id;
$title = $message->chat->title;

$contact = $message->contact;
$contact_id = $contact->user_id;
$contact_user = $contact->username;
$contact_name = $contact->first_name;
$phone = $contact->phone_number;

$data = $update->callback_query->data;
$qid = $update->callback_query->id;
$cid2 = $update->callback_query->message->chat->id;
$mid2 = $update->callback_query->message->message_id;
$callfrid = $update->callback_query->from->id;
$name2 = $update->callback_query->from->first_name;
$username2 = $update->callback_query->from->username;
$lastname2 = $update->callback_query->from->last_name;
$bio2 = $update->callback_query->from->about;
mkdir("bot");
$step = file_get_contents("bot/$cid.step");

if($message -> text){
if($type == "private"){
statuser($cid);
}
if($type == "supergroup" or $type == "group"){
statgroup($cid);
}
}

if($text == "/start" or $text == "/start@$botname"){
if($type == "private"){
bot('sendVideo',[
'video'=>"https://t.me/$kanal/8",
'chat_id'=>$cid,
'caption'=>"<b>@$botname - Botni guruh va kanal ulash haqida tuliq video qo'llanma 🎥

Botni ishlatishni bilmasangiz videoni ko'ring albatta ✅</b>",
'parse_mode'=>"html",
'disable_web_page_preview'=>true,
]);
bot('sendMessage',[
'chat_id'=>$cid,
'text'=>"<b>Salom👋
Man gruhingizdagi a'zolarni kanalga a'zo bo'lmaguncha yozdirmayman👮‍♂

Men ishlashi uchun guruhingizga qo'shib admin qilasiz ulamoqchi bo'lgan kanalga ham admin bo'lishi kerak😁

➕ Guruhni kanalga ulash na'muna guruhga yuborasiz - /kanal @username

⛔️ Guruhga ulangan kanalni uzish - /unlink

📃 Bot Ishlatish haqida qo'llanma - /qollanma</b>",
'parse_mode'=>"html",
'disable_web_page_preview'=>true,
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"➕ GURUHGA QO'SHISH",'url'=>"https://telegram.me/$botname?startgroup=new"]],
]
])
]);
}
if($type == "supergroup" or $type == "group"){
bot('sendMessage',[
'chat_id'=>$cid,
'text'=>"<b>Salom👋
Man gruhingizdagi a'zolarni kanalga a'zo bo'lmaguncha yozdirmayman👮‍♂

Men ishlashi uchun guruhingizga qo'shib admin qilasiz ulamoqchi bo'lgan kanalga ham admin bo'lishi kerak😁

➕ Guruhni kanalga ulash na'muna guruhga yuborasiz - /kanal @username

⛔️ Guruhga ulangan kanalni uzish - /unlink</b>",
'parse_mode'=>"html",
'disable_web_page_preview'=>true,
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"🎥 Video qo'llanma ✅",'url'=>"https://t.me/$kanal/8"]],
]
])
]);
}
}

if($text == "/kanal" or $text == "/kanal@$botname" or $text == "/unlink" or $text == "/unlink@$botname"){
if($type == "private"){
bot('sendMessage',[
'chat_id'=>$cid,
'text'=>"<b>Ushbu buyruq faqat guruhlarda ishlaydi ✅</b>",
'parse_mode'=>"html",
'disable_web_page_preview'=>true,
]);
}
}

if($text == "/qollanma" or $text == "/qollanma@$botname"){
if($type == "private"){
bot('sendMessage',[
'chat_id'=>$cid,
'text'=>"<b>📃 Botni ishlatish bo'yicha qo'llanma:

1️⃣ - Botni guruhingizga qo'shasiz va super admin qilasiz!

2️⃣ - Bot ulamoqchi bo'lgan kanalga ham  admin qilasiz sababi bot kanalga a'zo bo'lgan yoki bo'lmaganligi tekshirish uchun!

3️⃣ - Guruhga /kanal @username na'muna uchun  so'zini yuborasiz bu buyruq faqat guruh adminlarida ishlaydi!

4️⃣ - Tayyor guruh muvaffaqqiyatli kanalga ulanadi ✅

📃 Yangiliklar - @$kanal</b>",
'parse_mode'=>"html",
'disable_web_page_preview'=>true,
]);
bot('sendVideo',[
'video'=>"https://t.me/$kanal/8",
'chat_id'=>$cid,
'caption'=>"<b>@$botname - Botni guruh va kanal ulash haqida tuliq video qo'llanma 🎥

Botni ishlatishni bilmasangiz videoni ko'ring albatta ✅</b>",
'parse_mode'=>"html",
'disable_web_page_preview'=>true,
]);
}
}

if($text == "/unlink" or $text == "/unlink@$botname"){
if($type == "group" or $type =="supergroup"){
$gett= bot ('getChatMember', [
'chat_id'=> $cid,
'user_id'=> $uid
]);
$get = $gett->result->status;
if ($get == "administrator" or $get == "creator"){
bot('deleteMessage',[
'chat_id'=>$cid,
'message_id'=>$mid,
]);
bot('sendMessage',[
'chat_id'=>$cid,
'text'=>"<b>🗑 Kanal o‘chirib tashlandi!</b>",
'parse_mode'=>"html",
'disable_web_page_preview'=>true,
]);
file_put_contents("bot/$cid.db","");
unlink("bot/$cid.db");
}
}
}

if ((mb_stripos($text,"/kanal ")!==false) and (strlen($tx) > 11)){
if($type == "supergroup" or $type == "group"){
$ex = explode(" ", $text);
$us = bot ('getChatMember', [
'chat_id'=> $cid,
'user_id'=> $uid
]);
$res = $us->result->status;
if ($res == "administrator" or $res == "creator"){
$gett= bot ('getChatMember', [
'chat_id'=> $ex[1],
'user_id'=> $botid,
]);
$get = $gett->result->status;
if ($get == "administrator" or $get == "creator"){
bot ('sendmessage', [
'chat_id'=> $cid,
'parse_mode'=>"html",
'disable_web_page_preview'=>true,
'text'=>"<b>📢 Kanal ulandi guruh a'zolari $ex[1] kanaliga a'zo bo'lishi shart ✅</b>",
'reply_to_message_id'=> $mid
]);
file_put_contents("bot/$cid.db", $ex[1]);
file_put_contents("bot/$cid.step","getchatmember");
}else{
bot ('sendmessage', [
'chat_id'=> $cid,
'parse_mode'=>"markdown",
'text'=>"*🚫 Bot kanalda admin emas, xatolikni to'g'irlab qayta urunib ko'ring!*",
'reply_to_message_id'=> $mid
]);
}
}
}
}

if($text == "/kanal" or $text == "/kanal@$botname"){
if($type == "supergroup" or $type == "group"){
$gett= bot ('getChatMember', [
'chat_id'=> $cid,
'user_id'=> $uid
]);
$get = $gett->result->status;
if ($get == "administrator" or $get == "creator"){
bot('deleteMessage',[
'chat_id'=>$cid,
'message_id'=>$mid,
]);
bot('sendMessage',[
'chat_id'=>$cid,
'text'=>"<b>❗ Ushbu buyruqdan foydalanish quyidagicha:</b>

<code>/kanal @username</code>",
'parse_mode'=>"html",
'disable_web_page_preview'=>true,
]);
}
}
}

$chan = file_get_contents("bot/$cid.db");
if($step == "getchatmember" and $chan == true){
if($text == "/kanal" or $text == "/kanal@$botname"){
}else{
if($type == "supergroup" or $type == "group"){
$us = bot('getChat', [
'chat_id'=>$chan,
]);
$user = $us->result->username;
$tit = $us->result->title;
$us = bot ('getChatMember', [
'chat_id'=>$chan,
'user_id'=>$uid,
]);
$get = $us->result->status;
if ($get =="administrator" or $get =="creator" or $get == "member"){
}else{
bot ('deleteMessage', [
'chat_id'=>$cid, 
'message_id'=>$mid,
]);
bot('SendMessage',[
'chat_id'=>$cid,
'text'=>"<b>👮‍♂️ Kechirasiz,</b> <a href='tg://user?id=$uid'>$name</a> <code>$title</code> <b>guruhda yozish uchun</b> @$user <b>kanaliga a'zo bo'lishingiz kerak pastdagi tugmani bosib kanalimizga a'zo bo'ling 👇</b>",
'parse_mode'=>"html",
'disable_web_page_preview'=>true,
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>$tit, 'url'=>"https://t.me/".$user]],
]
])
]);
}
}
}
}

if($text == "/panel" and $cid == $admin){
if($type == "private"){
unlink("bot/$cid.step");
bot('sendMessage',[
'chat_id'=>$cid,
'text'=>"<b>🎛 Botnig boshqaruv panelidasiz!</b>",
'parse_mode'=>"html",
'disable_web_page_preview'=>true,
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"📊 Statistika",'callback_data'=>"📊 Statistika"]],
[['text'=>"👤 Oddiy",'callback_data'=>"👤 Oddiy"],['text'=>"👥 Oddiy",'callback_data'=>"👥 Oddiy"]],
[['text'=>"👤 Forward",'callback_data'=>"👤 Forward"],['text'=>"👥 Forward",'callback_data'=>"👥 Forward"]],
]
])
]);
}
}

if($data == "panel" and $cid2 == $admin){
unlink("bot/$cid2.step");
bot('deleteMessage',[
'chat_id'=>$cid2,
'message_id'=>$mid2,
]);
bot('sendMessage',[
'chat_id'=>$cid2,
'text'=>"<b>🎛 Botnig boshqaruv panelidasiz!</b>",
'parse_mode'=>"html",
'disable_web_page_preview'=>true,
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"📊 Statistika",'callback_data'=>"📊 Statistika"]],
[['text'=>"👤 Oddiy",'callback_data'=>"👤 Oddiy"],['text'=>"👥 Oddiy",'callback_data'=>"👥 Oddiy"]],
[['text'=>"👤 Forward",'callback_data'=>"👤 Forward"],['text'=>"👥 Forward",'callback_data'=>"👥 Forward"]],
]
])
]);
}

if($data == "📊 Statistika" and $cid2 == $admin){
bot('deleteMessage',[
'chat_id'=>$cid2,
'message_id'=>$mid2,
]);
$guruh = file_get_contents("bot/statgroup.txt");
$groups = substr_count($guruh,"\n");
$azo = file_get_contents("bot/statuser.txt");
$users = substr_count($azo,"\n");
bot('sendMessage',[
'chat_id'=>$cid2,
'text'=>"<b>📊 Botimiz statistikasi.

👤Foydalanuvchilar soni : $users ta
👥 Guruhlar soni : $groups ta</b>",
'parse_mode'=>"html",
'disable_web_page_preview'=>true,
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"◀️ Orqaga",'callback_data'=>"panel"]],
]
])
]);
}

if($data == "👤 Oddiy" and $cid2 == $admin){
bot('deleteMessage',[
'chat_id'=>$cid2,
'message_id'=>$mid2,
]);
bot('sendMessage',[
'chat_id'=>$cid2,
'text'=>"<b>$data xabarni bu yerga yuboring:</b>",
'parse_mode'=>"html",
'disable_web_page_preview'=>true,
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"◀️ Orqaga",'callback_data'=>"panel"]],
]
])
]);
file_put_contents("bot/$cid2.step","👤 Oddiy");
}

if($step == "👤 Oddiy" and $cid == $admin){
if($type == "private"){
$lich = file_get_contents("bot/statuser.txt");
$lichka = explode("\n",$lich);
foreach($lichka as $lichkalar){
$okuser=bot('copyMessage',[
'chat_id'=>$lichkalar,
'from_chat_id'=>$message->chat->id,
'message_id'=>$message->message_id,
]);
$get = file_get_contents("bot/xabar.soni");
$gets = $get + 1;
file_put_contents("bot/xabar.soni","$gets");
}
$gets = file_get_contents("bot/xabar.soni");
$get = $gets - 1;
bot('sendMessage',[
'chat_id'=>$admin,
'text'=>"<b>✅ Xabaringiz $get ta foydalanuvchiga yetkazildi.</b>",
'parse_mode'=>"html",
'disable_web_page_preview'=>true,
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"◀️ Orqaga",'callback_data'=>"panel"]],
]
])
]);
file_put_contents("bot/xabar.soni","0");
unlink("bot/$cid.step");
}
}

if($data == "👥 Oddiy" and $cid2 == $admin){
bot('deleteMessage',[
'chat_id'=>$cid2,
'message_id'=>$mid2,
]);
bot('sendMessage',[
'chat_id'=>$cid2,
'text'=>"<b>$data xabarni bu yerga yuboring:</b>",
'parse_mode'=>"html",
'disable_web_page_preview'=>true,
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"◀️ Orqaga",'callback_data'=>"panel"]],
]
])
]);
file_put_contents("bot/$cid2.step","👥 Oddiy");
}

if($step == "👥 Oddiy" and $cid == $admin){
if($type == "private"){
$lich = file_get_contents("bot/statgroup.txt");
$lichka = explode("\n",$lich);
foreach($lichka as $lichkalar){
$okuser=bot('copyMessage',[
'chat_id'=>$lichkalar,
'from_chat_id'=>$message->chat->id,
'message_id'=>$message->message_id,
]);
$get = file_get_contents("bot/xabar.soni");
$gets = $get + 1;
file_put_contents("bot/xabar.soni","$gets");
}
$gets = file_get_contents("bot/xabar.soni");
$get = $gets - 1;
bot('sendMessage',[
'chat_id'=>$admin,
'text'=>"<b>✅ Xabaringiz $get ta guruhga yetkazildi.</b>",
'parse_mode'=>"html",
'disable_web_page_preview'=>true,
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"◀️ Orqaga",'callback_data'=>"panel"]],
]
])
]);
file_put_contents("bot/xabar.soni","0");
unlink("bot/$cid.step");
}
}

if($data == "👤 Forward" and $cid2 == $admin){
bot('deleteMessage',[
'chat_id'=>$cid2,
'message_id'=>$mid2,
]);
bot('sendMessage',[
'chat_id'=>$cid2,
'text'=>"<b>$data xabarni bu yerga yuboring:</b>",
'parse_mode'=>"html",
'disable_web_page_preview'=>true,
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"◀️ Orqaga",'callback_data'=>"panel"]],
]
])
]);
file_put_contents("bot/$cid2.step","👤 Forward");
}

if($step == "👤 Forward" and $cid == $admin){
if($type == "private"){
$lich = file_get_contents("bot/statuser.txt");
$lichka = explode("\n",$lich);
foreach($lichka as $lichkalar){
$okuser=bot('forwardMessage',[
'chat_id'=>$lichkalar,
'from_chat_id'=>$message->chat->id,
'message_id'=>$message->message_id,
]);
$get = file_get_contents("bot/xabar.soni");
$gets = $get + 1;
file_put_contents("bot/xabar.soni","$gets");
}
$gets = file_get_contents("bot/xabar.soni");
$get = $gets - 1;
bot('sendMessage',[
'chat_id'=>$admin,
'text'=>"<b>✅ Xabaringiz $get ta foydalanuvchiga yetkazildi.</b>",
'parse_mode'=>"html",
'disable_web_page_preview'=>true,
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"◀️ Orqaga",'callback_data'=>"panel"]],
]
])
]);
file_put_contents("bot/xabar.soni","0");
unlink("bot/$cid.step");
}
}

// Ushbu kod @PHP_KODLAR kanali uchun @Farxodxon tomonidan yozildi ✅

if($data == "👥 Forward" and $cid2 == $admin){
bot('deleteMessage',[
'chat_id'=>$cid2,
'message_id'=>$mid2,
]);
bot('sendMessage',[
'chat_id'=>$cid2,
'text'=>"<b>$data xabarni bu yerga yuboring:</b>",
'parse_mode'=>"html",
'disable_web_page_preview'=>true,
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"◀️ Orqaga",'callback_data'=>"panel"]],
]
])
]);
file_put_contents("bot/$cid2.step","👥 Forward");
}

if($step == "👥 Forward" and $cid == $admin){
if($type == "private"){
$lich = file_get_contents("bot/statgroup.txt");
$lichka = explode("\n",$lich);
foreach($lichka as $lichkalar){
$okuser=bot('forwardMessage',[
'chat_id'=>$lichkalar,
'from_chat_id'=>$message->chat->id,
'message_id'=>$message->message_id,
]);
$get = file_get_contents("bot/xabar.soni");
$gets = $get + 1;
file_put_contents("bot/xabar.soni","$gets");
}
$gets = file_get_contents("bot/xabar.soni");
$get = $gets - 1;
bot('sendMessage',[
'chat_id'=>$admin,
'text'=>"<b>✅ Xabaringiz $get ta guruhga yetkazildi.</b>",
'parse_mode'=>"html",
'disable_web_page_preview'=>true,
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"◀️ Orqaga",'callback_data'=>"panel"]],
]
])
]);
file_put_contents("bot/xabar.soni","0");
unlink("bot/$cid.step");
}
}