import logging
from telegram import (
    Update,
    InlineKeyboardButton,
    InlineKeyboardMarkup,
)
from telegram.ext import (
    Application,
    CommandHandler,
    MessageHandler,
    CallbackQueryHandler,
    ContextTypes,
    ConversationHandler,
    filters,
)
# --- BOT TOKEN ---
TOKEN = "8394787578:AAGMij7l-p3NVrvr3LMsklwUDAgCQTBrf4Y"

ADMIN_IDS = [6067477588]  # Replace with real admin Telegram user IDs

MONTHLY_PRICE = 49000
YEARLY_PRICE = 420000
YEARLY_DISCOUNT = 20

LANGS = ["uz", "ru"]
SELECT_LANG, MAIN_MENU = range(2)
CREATE_COMPANY, COMPANY_NAME, WORKING_HOURS, EMPLOYEE_COUNT = range(10, 14)
JOIN_EMPLOYEE, INVITE_CODE, USER_FULLNAME, USER_ROLE = range(20, 24)
EMPLOYEE_ACTIONS, EMPLOYEE_VIDEO = range(30, 32)
SEND_REQUEST, PREMIUM, DELETE_COMPANY = range(40, 43)
ADMIN_PANEL, ADMIN_ACTION = range(50, 52)

STRINGS = {
    "uz": {
        "start": "Assalomu alaykum! {user}, Taskora’ga xush kelibsiz! 👋",
        "choose_lang": "Iltimos, tilni tanlang:",
        "choose_usage": "Taskora’dan qanday foydalanmoqchisiz?",
        "create_company": "Kompaniya ochish",
        "join_employee": "Xodim sifatida qo’shilish",
        "ask_company_name": "Kompaniya nomini kiriting:",
        "ask_work_hours": "Ish vaqtini kiriting (misol: 9:00–18:00):",
        "ask_employee_count": "Xodimlar sonini tanlang:",
        "employee_count_opts": ["5+", "10–20+", "20–50+", "50–100+", "Ko’proq"],
        "no_employees": "Hozircha xodimlar yo‘q. Xodimlarni taklif qilish kodi: {code}",
        "employee_list": "Xodimlar ro’yxati:",
        "dashboard_buttons": ["Kunlik hisobot", "Oylik hisobot", "Top Reyting"],
        "request": "Moderatorga xabar yuborish",
        "premium": "Taskora Premium",
        "delete_company": "Kompaniyani o'chirish",
        "invite_code": "Taklif kodini kiriting:",
        "enter_fullname": "Ismingiz va familiyangizni kiriting:",
        "enter_role": "Ish rolini kiriting (masalan: Dasturchi):",
        "arrived": "Men keldim",
        "left": "Men ketdim",
        "send_video": "Iltimos, 5 daqiqa ichida qisqa video yuboring.",
        "report_daily": "Kunlik hisobot",
        "report_monthly": "Oylik hisobot (Excel)",
        "report_top": "Top Reyting",
        "payment_info": "Obuna narxi: {monthly} UZS (oylik), {yearly} UZS (yillik, {discount}% chegirma)",
        "pay_instructions": "To’lovni 30 daqiqa ichida amalga oshiring, kvitansiyani saqlang va 'To‘ladim' tugmasini bosing.",
        "paid": "To‘ladim",
        "delete_company_confirm": "Kompaniyani o'chirishni tasdiqlaysizmi?",
        "deleted": "Kompaniya o'chirildi.",
        "admin_panel": "Admin Panel",
    },
    "ru": {
        "start": "Здравствуйте, {user}, добро пожаловать в Taskora! 👋",
        "choose_lang": "Пожалуйста, выберите язык:",
        "choose_usage": "Как вы планируете использовать Taskora?",
        "create_company": "Создать компанию",
        "join_employee": "Присоединиться как сотрудник",
        "ask_company_name": "Введите название компании:",
        "ask_work_hours": "Введите рабочие часы (например: 9:00–18:00):",
        "ask_employee_count": "Выберите количество сотрудников:",
        "employee_count_opts": ["5+", "10–20+", "20–50+", "50–100+", "Больше"],
        "no_employees": "Пока нет сотрудников. Код для приглашения: {code}",
        "employee_list": "Список сотрудников:",
        "dashboard_buttons": ["Ежедневный отчет", "Месячный отчет", "Топ рейтинг"],
        "request": "Отправить сообщение модератору",
        "premium": "Taskora Premium",
        "delete_company": "Удалить компанию",
        "invite_code": "Введите код приглашения:",
        "enter_fullname": "Введите имя и фамилию:",
        "enter_role": "Введите должность (например: Разработчик):",
        "arrived": "Я пришёл",
        "left": "Я ушёл",
        "send_video": "Пожалуйста, отправьте короткое видео в течение 5 минут.",
        "report_daily": "Ежедневный отчет",
        "report_monthly": "Месячный отчет (Excel)",
        "report_top": "Топ рейтинг",
        "payment_info": "Подписка: {monthly} UZS/мес, {yearly} UZS/год, скидка {discount}%",
        "pay_instructions": "Оплатите в течение 30 минут, сохраните чек и нажмите 'Я оплатил'.",
        "paid": "Я оплатил",
        "delete_company_confirm": "Подтвердите удаление компании.",
        "deleted": "Компания удалена.",
        "admin_panel": "Панель администратора",
    }
}

USERS = {}
COMPANIES = {}

def get_lang(user_id):
    return USERS.get(user_id, {}).get("lang", "uz")

def localize(user_id, key, **kwargs):
    lang = get_lang(user_id)
    txt = STRINGS[lang][key].format(**kwargs)
    return txt

def gen_invite_code(company_id):
    return f"TK{company_id:04d}"

async def start(update: Update, context: ContextTypes.DEFAULT_TYPE) -> int:
    keyboard = [
        [InlineKeyboardButton("O'zbekcha 🇺🇿", callback_data="uz"),
         InlineKeyboardButton("Русский 🇷🇺", callback_data="ru")]
    ]
    await update.message.reply_text(STRINGS["uz"]["choose_lang"], reply_markup=InlineKeyboardMarkup(keyboard))
    return SELECT_LANG

async def select_lang(update: Update, context: ContextTypes.DEFAULT_TYPE) -> int:
    query = update.callback_query
    lang = query.data
    user_id = query.from_user.id
    USERS[user_id] = {"lang": lang}
    await query.answer()
    greet = localize(user_id, "start", user=query.from_user.first_name)
    usage_keyboard = [
        [InlineKeyboardButton(localize(user_id, "create_company"), callback_data="create_company")],
        [InlineKeyboardButton(localize(user_id, "join_employee"), callback_data="join_employee")],
    ]
    await query.edit_message_text(f"{greet}\n\n{localize(user_id, 'choose_usage')}",
                                  reply_markup=InlineKeyboardMarkup(usage_keyboard))
    return MAIN_MENU

async def main_menu(update: Update, context: ContextTypes.DEFAULT_TYPE) -> int:
    query = update.callback_query
    user_id = query.from_user.id
    choice = query.data
    await query.answer()
    if choice == "create_company":
        await query.edit_message_text(localize(user_id, "ask_company_name"))
        return COMPANY_NAME
    elif choice == "join_employee":
        await query.edit_message_text(localize(user_id, "invite_code"))
        return INVITE_CODE

async def company_name(update: Update, context: ContextTypes.DEFAULT_TYPE) -> int:
    user_id = update.message.from_user.id
    name = update.message.text
    USERS[user_id]["company_name"] = name
    await update.message.reply_text(localize(user_id, "ask_work_hours"))
    return WORKING_HOURS

async def work_hours(update: Update, context: ContextTypes.DEFAULT_TYPE) -> int:
    user_id = update.message.from_user.id
    hours = update.message.text
    USERS[user_id]["work_hours"] = hours
    btns = [
        [InlineKeyboardButton(opt, callback_data=f"ec_{opt}")]
        for opt in STRINGS[get_lang(user_id)]["employee_count_opts"]
    ]
    await update.message.reply_text(localize(user_id, "ask_employee_count"),
        reply_markup=InlineKeyboardMarkup(btns))
    return EMPLOYEE_COUNT

async def employee_count(update: Update, context: ContextTypes.DEFAULT_TYPE) -> int:
    query = update.callback_query
    user_id = query.from_user.id
    count = query.data.replace("ec_", "")
    company_id = len(COMPANIES) + 1
    code = gen_invite_code(company_id)
    COMPANIES[company_id] = {
        "manager": user_id,
        "name": USERS[user_id]["company_name"],
        "hours": USERS[user_id]["work_hours"],
        "employee_count": count,
        "invite_code": code,
        "employees": [],
    }
    USERS[user_id]["company_id"] = company_id

    dashboard_btns = [
        [InlineKeyboardButton(localize(user_id, "report_daily"), callback_data="report_daily")],
        [InlineKeyboardButton(localize(user_id, "report_monthly"), callback_data="report_monthly")],
        [InlineKeyboardButton(localize(user_id, "report_top"), callback_data="report_top")],
    ]
    await query.edit_message_text(
        f"{localize(user_id, 'no_employees', code=code)}\n{localize(user_id, 'employee_list')}",
        reply_markup=InlineKeyboardMarkup(dashboard_btns)
    )
    return ConversationHandler.END

async def invite_code(update: Update, context: ContextTypes.DEFAULT_TYPE) -> int:
    user_id = update.message.from_user.id
    code = update.message.text.strip()
    company_id = None
    for cid, comp in COMPANIES.items():
        if comp["invite_code"] == code:
            company_id = cid
            break
    if not company_id:
        await update.message.reply_text("Kodni topib bo'lmadi. /start bilan qayta urinib ko’ring.")
        return ConversationHandler.END
    USERS[user_id]["company_id"] = company_id
    await update.message.reply_text(localize(user_id, "enter_fullname"))
    return USER_FULLNAME

async def user_fullname(update: Update, context: ContextTypes.DEFAULT_TYPE) -> int:
    user_id = update.message.from_user.id
    name = update.message.text
    USERS[user_id]["fullname"] = name
    await update.message.reply_text(localize(user_id, "enter_role"))
    return USER_ROLE

async def user_role(update: Update, context: ContextTypes.DEFAULT_TYPE) -> int:
    user_id = update.message.from_user.id
    role = update.message.text
    USERS[user_id]["role"] = role
    company_id = USERS[user_id]["company_id"]
    COMPANIES[company_id]["employees"].append({"id": user_id, "fullname": USERS[user_id]["fullname"], "role": role})
    btns = [
        [InlineKeyboardButton(localize(user_id, "arrived"), callback_data="arrived")],
        [InlineKeyboardButton(localize(user_id, "left"), callback_data="left")],
    ]
    await update.message.reply_text(
        "Ish kuni boshlandi. Quyidagi tugmalardan foydalaning.",
        reply_markup=InlineKeyboardMarkup(btns)
    )
    return EMPLOYEE_ACTIONS

async def employee_actions(update: Update, context: ContextTypes.DEFAULT_TYPE) -> int:
    query = update.callback_query
    user_id = query.from_user.id
    choice = query.data
    if choice == "arrived":
        await query.edit_message_text(localize(user_id, "send_video"))
        return EMPLOYEE_VIDEO
    elif choice == "left":
        await query.edit_message_text(localize(user_id, "send_video"))
        return EMPLOYEE_VIDEO
    return ConversationHandler.END

async def employee_video(update: Update, context: ContextTypes.DEFAULT_TYPE) -> int:
    user_id = update.message.from_user.id
    await update.message.reply_text("Tasdiqlandi! Hozirda ishga kelish/ketingiz hisoblandi.")
    return ConversationHandler.END

async def admin_panel(update: Update, context: ContextTypes.DEFAULT_TYPE):
    user_id = update.message.from_user.id
    text = update.message.text
    if user_id not in ADMIN_IDS:
        await update.message.reply_text("No access.")
        return
    await update.message.reply_text(localize(user_id, "admin_panel"))
    # TODO: Show admin actions/buttons

def main():
    app = Application.builder().token(TOKEN).build()

    conv_handler = ConversationHandler(
        entry_points=[CommandHandler('start', start)],
        states={
            SELECT_LANG: [CallbackQueryHandler(select_lang)],
            MAIN_MENU: [CallbackQueryHandler(main_menu)],
            COMPANY_NAME: [MessageHandler(filters.TEXT, company_name)],
            WORKING_HOURS: [MessageHandler(filters.TEXT, work_hours)],
            EMPLOYEE_COUNT: [CallbackQueryHandler(employee_count)],
            INVITE_CODE: [MessageHandler(filters.TEXT, invite_code)],
            USER_FULLNAME: [MessageHandler(filters.TEXT, user_fullname)],
            USER_ROLE: [MessageHandler(filters.TEXT, user_role)],
            EMPLOYEE_ACTIONS: [CallbackQueryHandler(employee_actions)],
            EMPLOYEE_VIDEO: [MessageHandler(filters.VIDEO, employee_video)],
        },
        fallbacks=[CommandHandler('admin', admin_panel)]
    )

    app.add_handler(conv_handler)
    app.add_handler(CommandHandler('admin', admin_panel))
    logging.basicConfig(level=logging.INFO)
    print("Taskora AI Bot started.")
    app.run_polling()

if __name__ == '__main__':
    main()