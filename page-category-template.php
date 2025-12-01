<link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/style/style.css">

<?php
/*
Template Name: Категория товара
*/
get_header();
global $wpdb;


// ===== Получаем slug категории из URL через query_var =====
$category_slug = get_query_var('category', '');
error_log("Slug из URL: " . $category_slug); // для отладки в PHP (лог серверный)

// ===== Находим тип товара по slug =====
$product_type = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM ProductType WHERE slug = %s LIMIT 1",
    $category_slug
));

if (!$product_type) {
    echo "<p>Категория не найдена.</p>";
    get_footer();
    exit;
}
?>


<script>
console.log("Slug из URL:", "<?php echo $category_slug; ?>");
console.log("Найденный тип товара:", "<?php echo $product_type->product_type_name; ?>");
</script>

<?php
// ===== Получаем фильтры =====
$selected_sizes = isset($_GET['size']) ? (array) $_GET['size'] : [];
$price_min = isset($_GET['price_min']) ? floatval($_GET['price_min']) : 0;
$price_max = isset($_GET['price_max']) ? floatval($_GET['price_max']) : 0;

// ===== SQL-запрос товаров по типу =====
$query = "
    SELECT DISTINCT p.*
    FROM Product p
    LEFT JOIN ProductSize ps ON p.product_id = ps.product_id
    LEFT JOIN Size s ON ps.size_id = s.size_id
    WHERE p.product_type_id = %d
";
$params = [$product_type->product_type_id];

if (!empty($selected_sizes)) {
    $placeholders = implode(',', array_fill(0, count($selected_sizes), '%s'));
    $query .= " AND s.size_value IN ($placeholders)";
    foreach ($selected_sizes as $s) $params[] = $s;
}

if ($price_min > 0) {
    $query .= " AND p.price >= %f";
    $params[] = $price_min;
}

if ($price_max > 0) {
    $query .= " AND p.price <= %f";
    $params[] = $price_max;
}

$query .= " ORDER BY p.product_id ASC";
$products = $params ? $wpdb->get_results($wpdb->prepare($query, ...$params)) : $wpdb->get_results($query);

// ===== Все размеры для фильтра =====
$sizes = $wpdb->get_results("SELECT * FROM Size ORDER BY size_id ASC");

// ===== Получаем client_id и guest_id =====
$client_id = isset($_COOKIE['client_id']) ? intval($_COOKIE['client_id']) : null;
$guest_id  = isset($_COOKIE['guest_id']) ? sanitize_text_field($_COOKIE['guest_id']) : null;

// ===== Получаем все избранные товары текущего пользователя =====
$fav_where = [];
$fav_params = [];
if ($client_id) {
    $fav_where[] = "client_id = %d";
    $fav_params[] = $client_id;
} else {
    $fav_where[] = "client_id IS NULL";
}
if ($guest_id) {
    $fav_where[] = "guest_id = %s";
    $fav_params[] = $guest_id;
} else {
    $fav_where[] = "guest_id IS NULL";
}
$fav_query = "SELECT product_id FROM Favorite WHERE " . implode(' AND ', $fav_where);
$favorites = $wpdb->get_col($wpdb->prepare($fav_query, ...$fav_params));
$favorite_set = array_flip($favorites); // для быстрого поиска
?>


<h1 class="catalog-title"><?php echo esc_html($product_type->product_type_name); ?></h1>
<div class="catalog-page">

    

    <!-- 🌸 Панель фильтров -->
    <aside class="filter-panel">
        <button class="toggle-filter">&larr;</button>
        <h3>Фильтры</h3>
        <form method="GET" class="filters">
            <input type="hidden" name="category" value="<?php echo esc_attr($category_slug); ?>">
            <p><strong>Размер:</strong></p>
            <?php foreach ($sizes as $size): ?>
                <label>
                    <input type="checkbox" name="size[]" value="<?php echo esc_attr($size->size_value); ?>"
                        <?php checked(in_array($size->size_value, $selected_sizes)); ?>>
                    <?php echo esc_html($size->size_value); ?>
                </label>
            <?php endforeach; ?>

            <p><strong>Цена:</strong></p>
            <input type="number" name="price_min" placeholder="от" value="<?php echo $price_min ?: ''; ?>">
            <input type="number" name="price_max" placeholder="до" value="<?php echo $price_max ?: ''; ?>">

            <button type="submit" class="btn-apply">Применить</button>
            <button type="submit" class="btn-reset"><a href="<?php echo site_url('/catalog/' . $category_slug . '/'); ?>">Сбросить фильтры</a></button>
        </form>
    </aside>

    <!-- 🌸 Товары -->
    <main class="product-list">
        <?php if ($products): ?>
            <?php foreach ($products as $product): ?>
                <?php
                $main_photo = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM ProductPhoto WHERE product_id = %d ORDER BY display_order ASC LIMIT 1",
                    $product->product_id
                ));
                $is_favorite = isset($favorite_set[$product->product_id]);
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

                    <button class="btn-favorite" data-product-id="<?php echo $product->product_id; ?>">
                        <img src="<?php echo get_template_directory_uri(); ?>/images/<?php echo $is_favorite ? 'pink-heart.svg' : 'heart.svg'; ?>" alt="Избранное">
                    </button>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Товары не найдены.</p>
        <?php endif; ?>
    </main>
</div>

<script>
const filterPanel = document.querySelector('.filter-panel');
const toggleBtn = document.querySelector('.toggle-filter');
toggleBtn.addEventListener('click', () => {
    filterPanel.classList.toggle('hidden');
    toggleBtn.innerHTML = filterPanel.classList.contains('hidden') ? '&rarr;' : '&larr;';
});
</script>

<?php get_footer(); ?>
