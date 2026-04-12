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

# Официальный сайт (https — откроется в браузере по нажатию в Telegram)
WEBSITE_URL = "http://localhost/python/index.php"
PHONE_DISPLAY = "+375 (44) 823-26-78"
# Для ссылки «Позвонить» — только цифры и + в формате E.164
PHONE_TEL = "tel:+375448232678"
CONTACT_EMAIL = "info@delovshlyape.com"

COMPANY_INFO = """
🏛 *Название компании*: Дело в шляпе

📍 *Адрес*: пр-т Победителей 39, Минск, Беларусь

⏰ *Часы работы*: 
   Пн–Вс: 10:00–19:00

🎉 *Праздники*:
   - Для малышей
   - Для подростков
   - Особые мероприятия
"""

# HTML: tel:/mailto:/https: корректно открываются в клиенте Telegram
CONTACTS_HTML = f"""
<b>📞 Контакты компании</b>

☎️ Телефон: <a href="{PHONE_TEL}">{PHONE_DISPLAY}</a> — нажмите, чтобы позвонить
📧 Почта: <a href="mailto:{CONTACT_EMAIL}">{CONTACT_EMAIL}</a> — нажмите, чтобы написать
🌐 Сайт: <a href="{WEBSITE_URL}">delovshlyape.com</a> — нажмите, чтобы перейти на сайт

<b>📱 Социальные сети</b>
   • <a href="https://www.instagram.com/">Instagram</a>
   • <a href="https://www.facebook.com/">Facebook</a>
   • <a href="https://t.me/">Telegram</a>
""".strip()