<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Уничтожаем сессию
$_SESSION = [];
session_destroy();

// Редирект на страницу логина
header('Location: /login');
exit;
