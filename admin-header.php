<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Если пользователь не вошёл — отправляем на страницу логина
if (!isset($_SESSION['user_login']) || !isset($_SESSION['user_role'])) {
    wp_redirect(site_url('/login'));
    exit;
}

global $wpdb;

// Получаем логин пользователя из сессии
$user_login = $_SESSION['user_login'];

// Получаем данные пользователя из базы
$user = $wpdb->get_row( $wpdb->prepare(
    "SELECT e.login, e.first_name, e.last_name, r.role_name 
     FROM Employee e
     LEFT JOIN Role r ON e.role_id = r.role_id
     WHERE e.login = %s",
    $user_login
) );

if ($user) {
    $user_name = $user->first_name . ' ' . $user->last_name; // имя и фамилия
    $user_role = $user->role_name;
} else {
    $user_name = $user_login; // если что-то пошло не так — показываем логин
    $user_role = $_SESSION['user_role'];
}
?>
<?php include get_template_directory() . '/modal.php'; ?>



<link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/style/admin-header.css">




<header class="site-header">
    <div class="container header-inner">
        <div class="logo">
            <a href="<?php echo home_url(); ?>">
                <img src="<?php echo get_template_directory_uri(); ?>/images/logo.png" alt="Логотип">
            </a>
        </div>

        <nav class="main-nav">
            <?php
			wp_nav_menu(array(
    			'theme_location' => 'admin-menu', // Название твоего меню
    			'container' => 'nav',             // Обёртка <nav>
    			'container_class' => 'main-navigation', // Класс для nav
    			'menu_class' => 'menu',           // Класс для <ul>
			));
			?>
        </nav>

        

		<div class="admin-user">
        <span class="user-role"><?php echo esc_html($user_role); ?></span>
        <span class="user-name"><?php echo esc_html($user_name); ?></span>
        <a class="logout-btn" href="<?php echo get_template_directory_uri(); ?>/logout.php">
            <img src="<?php echo get_template_directory_uri(); ?>/images/log-out.svg" alt="Выйти">
        </a>
        </div>
    </div>
</header>
