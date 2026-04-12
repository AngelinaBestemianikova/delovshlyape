<?php

/**
 * Настройки SMTP для отправки ответов клиентам (админка → обращения).
 * Заполните поля или скопируйте структуру из mail_settings.example.php
 */
return [
    'smtp_host' => 'smtp.devmail.email',
    'smtp_port' => 587,
    'smtp_secure' => '',
    'smtp_user' => 'test-87',
    'smtp_pass' => '1H10MelqRvt7vqkVscn3',
    'from_email' => 'test-87@inbound.devmail.email',
    'from_name' => 'Незабываемые праздники Delovslyape',
    'reply_subject' => 'Ответ на ваше обращение',
];
