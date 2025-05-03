import os
from dotenv import load_dotenv

load_dotenv()

TOKEN = os.getenv('TELEGRAM_BOT_TOKEN')
DB_CONFIG = {
    'host': os.getenv('DB_HOST', 'localhost'),
    'database': os.getenv('DB_NAME', 'YachtClub'),
    'user': os.getenv('DB_USER', 'postgres'),
    'password': os.getenv('DB_PASSWORD', ''),
    'port': os.getenv('DB_PORT', '3306')
}

CLUB_INFO = """
🏛 *Название яхт-клуба*: La Vague Maritime

📍 *Адрес*: Минское море, Беларусь

⏰ *Часы работы*: 
   Пн–Вс: 10:00–23:00

🚢 *Аренда яхт*:
   - Прогулочные
   - Туристические
   - Спортивные
"""

CONTACTS = """
📞 *Контакты яхт-клуба*:

☎️ Телефон: +375 (44) 569-9999
📧 Email: info@maritime.com
🌐 Сайт: www.lavaguemaritime.com

📱 *Социальные сети*:
   - Instagram: @maritime_yacht_club
   - Facebook: @MaritimeYachtClub
   - Telegram: @maritimeyachtclub
"""