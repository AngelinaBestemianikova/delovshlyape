import logging
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
from config import TOKEN, CLUB_INFO, CONTACTS
from database import Database

# Настройка логирования
logging.basicConfig(
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    level=logging.INFO
)
logger = logging.getLogger(__name__)

# Состояния для ConversationHandler
USERNAME, PASSWORD, CANCEL_RESERVATION = range(3)

# Клавиатура
main_keyboard = ReplyKeyboardMarkup(
    [
        ["ℹ️ Информация о клубе"],
        ["📞 Контакты"],
        ["📅 Мои бронирования"],
        ["🆘 Помощь"]
    ],
    resize_keyboard=True
)

def is_active_reservation(start_date, end_date):
    return start_date >= datetime.now()

async def start(update: Update, context: CallbackContext) -> None:
    # await для асинхронного выполнения Telegram API запросов
    await update.message.reply_text(
        "Добро пожаловать в бот яхт-клуба! Выберите действие:",
        reply_markup=main_keyboard
    )

async def club_info(update: Update, context: CallbackContext) -> None:
    await update.message.reply_text(
        CLUB_INFO,
        # parse_mode='Markdown' включает форматирование текста
        parse_mode='Markdown'
    )

async def contacts(update: Update, context: CallbackContext) -> None:
    await update.message.reply_text(
        CONTACTS,
        parse_mode='Markdown'
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
            "Пользователь с такой почтой не найден.",
            reply_markup=main_keyboard
        )
        return ConversationHandler.END
    
    context.user_data['email'] = email
    await update.message.reply_text("Теперь введите ваш пароль:")
    return PASSWORD

async def get_password(update: Update, context: CallbackContext) -> int:
    context.user_data['password'] = update.message.text
    email = context.user_data['email'] 
    password = context.user_data['password']
    
    db = Database()
    
    # Сначала проверяем пароль
    if not db.verify_password(email, password):
        await update.message.reply_text(
            "Неверный пароль.",
            reply_markup=main_keyboard
        )
        context.user_data.clear()
        return ConversationHandler.END
    
    # Если пароль верный, получаем бронирования
    reservations = db.get_user_reservations(email)
    
    if reservations is None:
        await update.message.reply_text(
            "Ошибка подключения к базе данных. Попробуйте позже.",
            reply_markup=main_keyboard
        )
    elif not reservations:
        await update.message.reply_text(
            "Бронирований не найдено.",
            reply_markup=main_keyboard
        )
    else:
        active_reservations = [
            r for r in reservations 
            if is_active_reservation(r[2], r[3])  # r[2] - start_date
        ]
        
        if not active_reservations:
            await update.message.reply_text(
                "Активных бронирований не найдено.",
                reply_markup=main_keyboard
            )
        else:
            response = "*Ваши активные бронирования*:\n\n"
            for reservation in active_reservations:
                response += (
                    f"🔹 *ID*: {reservation[0]}\n"
                    f"🛥 *Яхта*: {reservation[1]}\n"
                    f"📅 *Начало*: {reservation[2].strftime('%d.%m.%Y %H:%M')}\n"
                    f"📅 *Окончание*: {reservation[3].strftime('%d.%m.%Y %H:%M')}\n\n"
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
        "ℹ️ Информация о клубе - общая информация о яхт-клубе\n"
        "📞 Контакты - контактные данные клуба\n"
        "📅 Мои бронирования - просмотр активных бронирований\n\n"
        "Для просмотра бронирований потребуется ввести email и пароль.",
        parse_mode='Markdown'
    )

def main() -> None:
    # Создает экземпляр бота через паттерн Builder
    application = Application.builder().token(TOKEN).build()

    # Регистрация обработчиков команд
    application.add_handler(CommandHandler("start", start))
    application.add_handler(CommandHandler("help", help_command))

    # многошаговый диалог
    conv_handler = ConversationHandler(
    entry_points=[MessageHandler(filters.Regex("^📅 Мои бронирования$"), reservations_start)],
    states={
        # Принимает только текстовые сообщения + игнорирует сообщения, начинающиеся с /
        USERNAME: [MessageHandler(filters.TEXT & ~filters.COMMAND, get_username)],
        PASSWORD: [MessageHandler(filters.TEXT & ~filters.COMMAND, get_password)],
        CANCEL_RESERVATION: [MessageHandler(filters.TEXT & ~filters.COMMAND, cancel_reservation)],
    },
    fallbacks=[CommandHandler("cancel", cancel)],
)

    application.add_handler(conv_handler)
    
    # Регистрация обработчиков кнопок
    application.add_handler(MessageHandler(filters.Regex("^ℹ️ Информация о клубе$"), club_info))
    application.add_handler(MessageHandler(filters.Regex("^📞 Контакты$"), contacts))
    application.add_handler(MessageHandler(filters.Regex("^🆘 Помощь$"), help_command))

    # Режим polling: бот постоянно опрашивает сервер Telegram на новые сообщения
    application.run_polling()

if __name__ == '__main__':
    main()