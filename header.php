<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">

    <title><?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

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
                'theme_location' => 'main-menu',
                'container' => 'nav',
                'container_class' => 'main-navigation',
                'menu_class' => 'menu',
            ));
            ?>
        </nav>

        <!-- Иконки: поиск, избранное, примерочная -->
        <div class="header-icons">
            <a href="#search" class="icon-heart" title="Поиск">
                <img src="<?php echo get_template_directory_uri(); ?>/images/search.svg" alt="Поиск">
            </a>
            <a href="<?php echo site_url('/favorite'); ?>" class="icon-heart" title="Понравившиеся товары">
                <img src="<?php echo get_template_directory_uri(); ?>/images/heart.svg" alt="Избранное">
            </a>
            <a href="<?php echo site_url('/fitting'); ?>" class="icon-heart" title="Примерочная">
                <img src="<?php echo get_template_directory_uri(); ?>/images/hanger.svg" alt="Примерочная">
            </a>
        </div>

      <!--  <div class="header-btn">
            <a href="https://salon/card" class="btn-book">Записаться</a>
        </div>  -->

        <!-- 🔹 Блок авторизации -->
        <div class="user-auth">
            <?php
            global $wpdb;
            $client_name = '';
            if (isset($_COOKIE['client_id'])) {
                $client_id = intval($_COOKIE['client_id']);
                $table = 'Client';
                $client = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE client_id = %d", $client_id));
                if ($client) {
                    $client_name = esc_html($client->first_name);
                }
            }

            if ($client_name): ?>
                <div class="auth-info">
                    <span class="auth-name">Привет, <?php echo $client_name; ?></span>
                    <button id="logout-btn" class="logout-btn">Выйти</button>
                </div>
            <?php else: ?>
                <button id="open-auth-modal" class="login-btn" title="Войти">
                    <img src="<?php echo get_template_directory_uri(); ?>/images/persone.svg" alt="Примерочная">
                    
                </button>
            <?php endif; ?>
        </div>
    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', () => {
            document.cookie = "client_id=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
            location.reload();
        });
    }

    const loginBtn = document.getElementById('open-auth-modal');
    if (loginBtn) {
        loginBtn.addEventListener('click', () => {
            const modal = document.getElementById('auth-modal');
            if (modal) modal.style.display = 'block';
        });
    }
});
</script>

<style>
.user-auth {
    display: flex;
    align-items: center;
    margin-left: 15px;
}
.login-btn, .logout-btn {
    background: none;
    border: none;
    color: #333;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 5px;
}
.login-btn svg {
    stroke: #333;
}
.login-btn:hover, .logout-btn:hover {
    color: #a57bc9;
}
.auth-info {
    display: flex;
    align-items: center;
    gap: 10px;
}
</style>
