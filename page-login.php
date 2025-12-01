<?php
/*
Template Name: Login
*/


// Начало сессии — должно быть до любого вывода
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

global $wpdb;

$login_error = '';

// Обработка формы входа
if (isset($_POST['login_submit'])) {

    $username = sanitize_text_field($_POST['username']);
    $password = sanitize_text_field($_POST['password']);

    // Ищем пользователя в таблице Employee
    $user = $wpdb->get_row($wpdb->prepare(
        "SELECT e.login, e.password, r.role_name 
         FROM Employee e
         LEFT JOIN Role r ON e.role_id = r.role_id
         WHERE e.login = %s",
        $username
    ));

    if ($user) {
        // Проверяем пароль
        if ($user->password === $password) { // если хеш, тогда использовать password_verify
            // Сохраняем данные в сессию
            $_SESSION['user_login'] = $user->login;
            $_SESSION['user_role'] = $user->role_name;

            // Редирект в зависимости от роли
            if ($user->role_name === 'Администратор') {
                wp_redirect(site_url('/admin/catalog/dresses'));
                exit;
            } elseif ($user->role_name === 'Менеджер') {
                wp_redirect(site_url('/mymanager'));
                exit;
            } else {
                $login_error = 'У вас нет прав доступа.';
            }
        } else {
            $login_error = 'Неверный пароль.';
        }
    } else {
        $login_error = 'Пользователь не найден.';
    }
}

// Если пользователь уже залогинен в сессии — перенаправляем
if (isset($_SESSION['user_login']) && isset($_SESSION['user_role'])) {
    if ($_SESSION['user_role'] === 'admin') {
        wp_redirect(site_url('/admin/catalog/dresses'));
        exit;
    } elseif ($_SESSION['user_role'] === 'manager') {
        wp_redirect(site_url('/manager'));
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход</title>
    <link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/style/login.css">
</head>
<body>

<div class="admin-login-wrapper">
    <div class="admin-login-box">
        <h2>Вход</h2>

        <?php if($login_error): ?>
            <div class="login-error"><?php echo esc_html($login_error); ?></div>
        <?php endif; ?>

        <form method="post">
            <input type="text" name="username" placeholder="Логин" required>
            <div class="password-wrapper">
                <input type="password" name="password" id="admin-password" placeholder="Пароль" required>
                <span class="toggle-eye" onclick="togglePassword()">
                    <img id="eye-open" src="<?php echo get_template_directory_uri(); ?>/images/open_eye.svg" alt="Показать пароль">
                    <img id="eye-closed" src="<?php echo get_template_directory_uri(); ?>/images/closed_eye.svg" alt="Скрыть пароль" style="display:none">
                </span>
            </div>
            <br>
            <button type="submit" name="login_submit">Войти</button>
        </form>
    </div>
</div>

<script>
function togglePassword() {
    const pass = document.getElementById('admin-password');
    const eyeOpen = document.getElementById('eye-open');
    const eyeClosed = document.getElementById('eye-closed');

    if(pass.type === 'password') {
        pass.type = 'text';
        eyeOpen.style.display = 'none';
        eyeClosed.style.display = 'inline';
    } else {
        pass.type = 'password';
        eyeOpen.style.display = 'inline';
        eyeClosed.style.display = 'none';
    }
}
</script>

</body>
</html>
