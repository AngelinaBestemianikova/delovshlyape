import logging
import sys
from datetime import datetime
from telegram import Update, ReplyKeyboardMarkup
from telegram.ext import (
    Application,
    CommandHandler,
    MessageHandler,
    ConversationHandler,
    filters,
    CallbackContext
)
from config import TOKEN, COMPANY_INFO, CONTACTS_HTML
from database import Database

# Настройка логирования
logging.basicConfig(
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    level=logging.DEBUG,  # Изменено на DEBUG для получения большего количества информации
    stream=sys.stdout  # Явно указываем вывод в консоль
)
logger = logging.getLogger(__name__)

# Состояния для ConversationHandler
USERNAME, PASSWORD, CANCEL_RESERVATION = range(3)

# Клавиатура
main_keyboard = ReplyKeyboardMarkup(
    [
        ["ℹ️ Информация о компании"],
        ["📞 Контакты"],
        ["📅 Мои бронирования"],
        ["🆘 Помощь"]
    ],
    resize_keyboard=True
)

def is_active_reservation(event_date):
    if isinstance(event_date, str):
        return datetime.strptime(event_date, '%Y-%m-%d').date() >= datetime.now().date()
    return event_date >= datetime.now().date()

async def start(update: Update, context: CallbackContext) -> None:
    try:
        await update.message.reply_text(
            "Добро пожаловать в бот компании Дело в шляпе! Выберите действие:",
            reply_markup=main_keyboard
        )
    except Exception as e:
        logger.error(f"Error in start command: {e}")

async def companyinfo(update: Update, context: CallbackContext) -> None:
    await update.message.reply_text(
        COMPANY_INFO,
        parse_mode='Markdown'
    )

async def contacts(update: Update, context: CallbackContext) -> None:
    await update.message.reply_text(
        CONTACTS_HTML,
        parse_mode='HTML',
        disable_web_page_preview=True,
    )

async def reservations_start(update: Update, context: CallbackContext) -> int:
    await update.message.reply_text(
        "Для просмотра бронирований введите вашу почту:",
        reply_markup=ReplyKeyboardMarkup([["/cancel"]], resize_keyboard=True)
    )
    return USERNAME

async def get_username(update: Update, context: CallbackContext) -> int:
    email = update.message.text
    db = Database()
    
    if not db.check_email_exists(email):
        await update.message.reply_text(
            "Пользователь с такой почтой не найден. Пожалуйста, введите почту еще раз или нажмите /cancel для отмены:"
        )
        return USERNAME
    
    context.user_data['email'] = email
    await update.message.reply_text("Теперь введите ваш пароль:")
    return PASSWORD

async def get_password(update: Update, context: CallbackContext) -> int:
    context.user_data['password'] = update.message.text
    email = context.user_data['email'] 
    password = context.user_data['password']
    
    db = Database()
    
    if not db.verify_password(email, password):
        await update.message.reply_text(
            "Неверный пароль. Пожалуйста, введите пароль еще раз или нажмите /cancel для отмены:",
            reply_markup=ReplyKeyboardMarkup([["/cancel"]], resize_keyboard=True)
        )
        return PASSWORD
    
    userbookings = db.get_user_reservations(email)
    
    if userbookings is None:
        await update.message.reply_text(
            "Ошибка подключения к базе данных. Попробуйте позже.",
            reply_markup=main_keyboard
        )
    elif not userbookings:
        await update.message.reply_text(
            "Бронирований не найдено.",
            reply_markup=main_keyboard
        )
    else:
        active_reservations = [
            r for r in userbookings 
            if is_active_reservation(r[2])  # r[2] - event_date
        ]
        
        if not active_reservations:
            await update.message.reply_text(
                "Активных бронирований не найдено.",
                reply_markup=main_keyboard
            )
        else:
            response = "*Ваши активные бронирования*:\n\n"
            for r in active_reservations:
                # r[0]-ID, r[1]-Программа, r[2]-Дата, r[3]-Адрес, r[4]-Гости
                response += (
                    f"🔹 *ID*: `{r[0]}`\n"
                    f"🎉 *Программа*: {r[1]}\n"
                    f"📅 *Дата*: {r[2]}\n"
                    f"📍 *Адрес*: {r[3]}\n"
                    f"👥 *Кол-во гостей*: {r[4]}\n\n"
                )
            response += "Введите ID бронирования, которое вы хотите *отменить*, или /cancel для выхода."
            
            context.user_data['active_reservations'] = [r[0] for r in active_reservations]
            await update.message.reply_text(response, parse_mode='Markdown')
            return CANCEL_RESERVATION

    context.user_data.clear()
    return ConversationHandler.END

async def cancel_reservation(update: Update, context: CallbackContext) -> int:
    user_input = update.message.text.strip()
    
    if not user_input.isdigit():
        await update.message.reply_text(
            "Пожалуйста, введите числовой ID бронирования или /cancel для выхода."
        )
        return CANCEL_RESERVATION

    reservation_id = int(user_input)
    allowed_ids = context.user_data.get('active_reservations', [])

    if reservation_id not in allowed_ids:
        await update.message.reply_text(
            "Бронирование с таким ID не найдено среди ваших активных. Попробуйте снова."
        )
        return CANCEL_RESERVATION

    db = Database()
    if db.cancel_reservation_by_id(reservation_id):
        await update.message.reply_text(
            f"✅ Бронирование с ID {reservation_id} успешно отменено.",
            reply_markup=main_keyboard
        )
    else:
        await update.message.reply_text(
            f"❌ Не удалось отменить бронирование. Попробуйте позже.",
            reply_markup=main_keyboard
        )

    context.user_data.clear()
    return ConversationHandler.END

async def cancel(update: Update, context: CallbackContext) -> int:
    await update.message.reply_text(
        "Действие отменено.",
        reply_markup=main_keyboard
    )
    context.user_data.clear()
    return ConversationHandler.END

async def help_command(update: Update, context: CallbackContext) -> None:
    await update.message.reply_text(
        "ℹ️ *Помощь*:\n\n"
        "Выберите одну из кнопок:\n"
        "ℹ️ Информация о компании - общая информация о компании\n"
        "📞 Контакты - контактные данные компании\n"
        "📅 Мои бронирования - просмотр активных бронирований\n\n"
        "Для просмотра бронирований потребуется ввести email и пароль.",
        parse_mode='Markdown'
    )

def main() -> None:
    try:
        application = Application.builder().token(TOKEN).build()
        
        application.add_handler(CommandHandler("start", start))
        application.add_handler(CommandHandler("help", help_command))

        conv_handler = ConversationHandler(
            entry_points=[MessageHandler(filters.Regex("^📅 Мои бронирования$"), reservations_start)],
            states={
                USERNAME: [MessageHandler(filters.TEXT & ~filters.COMMAND, get_username)],
                PASSWORD: [MessageHandler(filters.TEXT & ~filters.COMMAND, get_password)],
                CANCEL_RESERVATION: [MessageHandler(filters.TEXT & ~filters.COMMAND, cancel_reservation)],
            },
            fallbacks=[CommandHandler("cancel", cancel)],
        )

        application.add_handler(conv_handler)
        application.add_handler(MessageHandler(filters.Regex("^ℹ️ Информация о компании$"), companyinfo))
        application.add_handler(MessageHandler(filters.Regex("^📞 Контакты$"), contacts))
        application.add_handler(MessageHandler(filters.Regex("^🆘 Помощь$"), help_command))
        
        application.run_polling(allowed_updates=Update.ALL_TYPES)
        
    except Exception as e:
        logger.error(f"Error in main: {e}")

if __name__ == '__main__':
    main()