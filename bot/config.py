import os
from dotenv import load_dotenv

load_dotenv()

TOKEN = os.getenv('TELEGRAM_BOT_TOKEN')
DB_CONFIG = {
    'host': os.getenv('DB_HOST', 'localhost'),
    'database': os.getenv('DB_NAME', 'Delovshlyape'),
    'user': os.getenv('DB_USER', 'root'),
    'password': os.getenv('DB_PASSWORD', ''),
    'port': os.getenv('DB_PORT', '3306')
}

COMPANY_INFO = """
🏛 *Название компании*: Дело в шляпе

📍 *Адрес*: пр-т Победителей 39, Минск, Беларусь

⏰ *Часы работы*: 
   Пн–Вс: 10:00–29:00

🎉 *Праздники*:
   - Для малышей
   - Для подростков
   - Особые мероприятия
"""

CONTACTS = """
📞 *Контакты компании*:

☎️ Телефон: +375 (44) 823-26-78
📧 Email: info@delovshlyape.com
🌐 Сайт: www.delovshlyape.com

📱 *Социальные сети*:
   - Instagram: @delovshlyape
   - Facebook: @Delovshlyape
   - Telegram: @delovshlyape
"""