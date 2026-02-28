import mysql.connector
import hashlib
from config import DB_CONFIG

class Database:
    def __init__(self):
        self.connection = None
    
    def connect(self):
        try:
            self.connection = mysql.connector.connect(
                host=DB_CONFIG['host'],
                database=DB_CONFIG['database'],
                user=DB_CONFIG['user'],
                password=DB_CONFIG['password'],
                port=DB_CONFIG['port']
            )
            return True
        except Exception as e:
            print(f"Ошибка подключения к MySQL: {e}")
            return False

    def check_email_exists(self, email):
        if not self.connect():
            return False

        try:
            cursor = self.connection.cursor()
            cursor.execute("SELECT 1 FROM users WHERE email = %s", (email,))
            return cursor.fetchone() is not None
        except Exception as e:
            print(f"Ошибка запроса: {e}")
            return False
        finally:
            if self.connection and self.connection.is_connected():
                cursor.close()
                self.connection.close()

    def verify_password(self, email, password):
        if not self.connect():
            return False

        try:
            cursor = self.connection.cursor(dictionary=True)
            cursor.execute(
                "SELECT password_hash, password_salt FROM users WHERE email = %s", 
                (email,)
            )
            user = cursor.fetchone()
            
            if not user:
                return False
                
            # Повторяем логику хеширования из PHP
            hashed_input = hashlib.md5(
                hashlib.md5(password.encode()).hexdigest().encode() + 
                str(user['password_salt']).encode()
            ).hexdigest()
            
            return hashed_input == user['password_hash']
            
        except Exception as e:
            print(f"Ошибка проверки пароля: {e}")
            return False
        finally:
            if self.connection and self.connection.is_connected():
                cursor.close()
                self.connection.close()

    def get_user_reservations(self, email):
        if not self.connect():
            return None

        try:
            cursor = self.connection.cursor()
            query = """
                SELECT b.id, p.name as program_name, b.event_date, b.event_date as end_date
                FROM bookings b
                JOIN users u ON b.user_id = u.id
                JOIN programs p ON b.program_id = p.id
                WHERE u.email = %s
                ORDER BY b.event_date DESC  
            """
            cursor.execute(query, (email,))
            return cursor.fetchall()
        except Exception as e:
            print(f"Ошибка запроса: {e}")
            return None
        finally:
            if self.connection and self.connection.is_connected():
                cursor.close()
                self.connection.close()
                
    def cancel_reservation_by_id(self, reservation_id):
        if not self.connect():
            return False

        try:
            cursor = self.connection.cursor()
            cursor.execute("DELETE FROM bookings WHERE id = %s", (reservation_id,))
            self.connection.commit()
            return cursor.rowcount > 0
        except Exception as e:
            print(f"Ошибка при удалении брони: {e}")
            return False
        finally:
            if self.connection and self.connection.is_connected():
                cursor.close()
                self.connection.close()