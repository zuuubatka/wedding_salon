<link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/style/favorite.css">

<?php
/*
Template Name: Избранное
*/
get_header();
global $wpdb;

// ===== Получаем client_id и guest_id из куки =====
$client_id = isset($_COOKIE['client_id']) ? intval($_COOKIE['client_id']) : null;
$guest_id  = isset($_COOKIE['guest_id'])  ? sanitize_text_field($_COOKIE['guest_id']) : null;

// ===== Получаем поисковый запрос =====
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// ===== Получаем список избранных товаров текущего пользователя =====
$table = 'Favorite';
$query = "SELECT product_id FROM $table WHERE 1=1";
$params = [];

if ($client_id) {
    $query .= " AND client_id=%d";
    $params[] = $client_id;
} elseif ($guest_id) {
    $query .= " AND guest_id=%s";
    $params[] = $guest_id;
}

$fav_ids = $params ? $wpdb->get_col($wpdb->prepare($query, ...$params)) : [];

// ===== Если есть избранные, получаем их данные =====
if (!empty($fav_ids)) {
    $placeholders = implode(',', array_fill(0, count($fav_ids), '%d'));
    $query = "SELECT * FROM Product WHERE product_id IN ($placeholders) ORDER BY product_id DESC";
    $products = $wpdb->get_results($wpdb->prepare($query, ...$fav_ids));
} else {
    $products = [];
}

// ===== Фильтруем по поиску =====
if ($search_query && !empty($products)) {
    $products = array_filter($products, function ($p) use ($search_query) {
        return stripos($p->product_name, $search_query) !== false;
    });
}
?>

<!-- Поиск по избранному -->
<form method="GET" class="favorite-search">
    <input 
        type="text" 
        name="search" 
        placeholder="Поиск по избранному..." 
        value="<?php echo esc_attr($search_query); ?>"
        class="search-input"
    >
    <button type="submit" class="btn-apply">Найти</button>
</form>

<!-- Список товаров -->
<main class="product-list" style="width:100%; grid-template-columns: repeat(4, 1fr); gap:20px;">
    <?php if (!empty($products)): ?>
      
            <?php foreach ($products as $product): ?>
                <?php
                $main_photo = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM ProductPhoto WHERE product_id = %d ORDER BY display_order ASC LIMIT 1",
                    $product->product_id
                ));
                ?>
                <div class="product-card">
                    <a href="<?php echo site_url('/card/?product_id=' . $product->product_id); ?>">
                        <?php if ($main_photo): ?>
                            <img src="<?php echo get_template_directory_uri() . '/' . $main_photo->photo_url; ?>" alt="">
                        <?php else: ?>
                            <img src="<?php echo get_template_directory_uri(); ?>/images/no-image.jpg" alt="Нет изображения">
                        <?php endif; ?>
                        <h4><?php echo esc_html($product->product_name); ?></h4>
                        <p>Цена: <?php echo (int)$product->price; ?> Br</p>
                    </a>
                </div>
            <?php endforeach; ?>
      
    <?php else: ?>
        <p style="text-align:center;">Избранные товары не найдены.</p>
    <?php endif; ?>
</main>

<?php get_footer(); ?>
