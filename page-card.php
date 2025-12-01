<?php /* Подключение модального окна */ get_template_part('choice-modal'); ?>

<?php
/*
Template Name: Карточка товара
*/
get_header();
global $wpdb;

// Получаем ID товара из URL
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

// Получаем client_id и guest_id
$client_id = isset($_COOKIE['client_id']) ? intval($_COOKIE['client_id']) : null;
$guest_id  = isset($_COOKIE['guest_id']) ? sanitize_text_field($_COOKIE['guest_id']) : null;

// Получаем список избранного для текущего пользователя/гостя
$favorite_ids = [];
$table =  'Favorite';
if ($client_id || $guest_id) {
    $query = "SELECT product_id FROM $table WHERE 1=1";
    $params = [];

    if ($client_id) {
        $query .= " AND client_id=%d";
        $params[] = $client_id;
    } else {
        $query .= " AND client_id IS NULL";
    }

    if ($guest_id) {
        $query .= " AND guest_id=%s";
        $params[] = $guest_id;
    } else {
        $query .= " AND guest_id IS NULL";
    }

    $favorite_ids = $wpdb->get_col($wpdb->prepare($query, ...$params));
}

if ($product_id) {
    $product = $wpdb->get_row($wpdb->prepare("SELECT * FROM Product WHERE product_id = %d", $product_id));
    if ($product) {
        $product_type_id = $product->product_type_id;
        $product_type = $wpdb->get_row($wpdb->prepare("SELECT product_type_name FROM ProductType WHERE product_type_id = %d", $product_type_id));

        $size_type = 'one_size';
        if ($product_type) {
            if ($product_type->product_type_name === 'Свадебные платья') $size_type = 'dress';
            elseif ($product_type->product_type_name === 'Обувь') $size_type = 'shoes';
        }

        $all_sizes = $wpdb->get_results($wpdb->prepare("SELECT * FROM Size WHERE size_type = %s ORDER BY size_id ASC", $size_type));
        $photos = $wpdb->get_results($wpdb->prepare("SELECT * FROM ProductPhoto WHERE product_id = %d ORDER BY display_order ASC", $product_id));
        if (!$photos) $photos = [];

        $next_product = $wpdb->get_var($wpdb->prepare(
            "SELECT product_id FROM Product WHERE product_type_id = %d AND product_id > %d ORDER BY product_id ASC LIMIT 1",
            $product_type_id, $product_id
        ));
        $prev_product = $wpdb->get_var($wpdb->prepare(
            "SELECT product_id FROM Product WHERE product_type_id = %d AND product_id < %d ORDER BY product_id DESC LIMIT 1",
            $product_type_id, $product_id
        ));

        $product_sizes = $wpdb->get_results($wpdb->prepare(
            "SELECT product_size_id, size_id, quantity_in_stock FROM ProductSize WHERE product_id = %d",
            $product_id
        ));
        $sizes_qty = [];
        foreach ($product_sizes as $ps) $sizes_qty[$ps->size_id] = intval($ps->quantity_in_stock);
        $has_stock = count(array_filter($sizes_qty)) > 0;

        // Получаем размеры уже в примерочной
        $fitting_cart = []; // size_id => cart_item_id
        $fitting_room_id = null;
        if ($client_id) {
            $fitting_room_id = $wpdb->get_var($wpdb->prepare("SELECT fitting_room_id FROM FittingRoom WHERE client_id=%d", $client_id));
        } elseif ($guest_id) {
            $fitting_room_id = $wpdb->get_var($wpdb->prepare("SELECT fitting_room_id FROM FittingRoom WHERE guest_id=%s", $guest_id));
        }
        if ($fitting_room_id) {
            $items = $wpdb->get_results($wpdb->prepare(
                "SELECT ci.cart_item_id, ps.size_id
                 FROM CartItem ci
                 INNER JOIN ProductSize ps ON ci.product_size_id = ps.product_size_id
                 WHERE ci.fitting_room_id=%d AND ps.product_id=%d",
                $fitting_room_id, $product_id
            ));
            foreach ($items as $item) $fitting_cart[$item->size_id] = $item->cart_item_id;
        }

        $in_fitting_any = count($fitting_cart) > 0;
        $fitting_button_text = $in_fitting_any ? 'Удалить из примерочной' : 'В примерочную';
        ?>

        <div class="product-page">
            <!-- Галерея -->
            <div class="product-gallery">
                <div class="main-image">
                    <button class="prev-btn">&#10094;</button>
                    <img id="current-image" src="<?php echo esc_url(get_template_directory_uri() . '/' . ($photos[0]->photo_url ?? 'images/no-image.jpg')); ?>" 
                         alt="<?php echo esc_attr($photos[0]->description ?? 'Нет изображения'); ?>">
                    <button class="next-btn">&#10095;</button>
                </div>

                <div class="thumbnail-row">
                    <?php foreach ($photos as $index => $photo): ?>
                        <img src="<?php echo esc_url(get_template_directory_uri() . '/' . $photo->photo_url); ?>"
                             class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>"
                             alt="<?php echo esc_attr($photo->description); ?>">
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Информация о товаре -->
            <div class="product-info">
                <h2 class="product-title"><?php echo esc_html($product->product_name); ?></h2>
                <p class="product-code">Артикул: <?php echo esc_html($product->article); ?></p>
                <p class="product-price">Цена: <?php echo (int)$product->price; ?> Br</p>
                <p class="product-description"><?php echo esc_html($product->description); ?></p>

                <!-- Размеры -->
                <div class="product-sizes" style="margin:10px 0;">
                <?php if ($has_stock): ?>
                    <?php foreach ($all_sizes as $size):
                        $available = isset($sizes_qty[$size->size_id]) && $sizes_qty[$size->size_id] > 0;
                        $in_fitting = isset($fitting_cart[$size->size_id]);
                        $bg_color = $class = '';
                        if (!$available) {
                            $class = 'unavailable';
                            $bg_color = '#e0e0e0';
                        } elseif ($in_fitting) {
                            $class = 'in-fitting';
                            $bg_color = 'pink';
                        } else {
                            $class = 'available';
                            $bg_color = 'white';
                        }
                    ?>
                        <span class="size-item <?php echo $class; ?> "
                              data-size-id="<?php echo $size->size_id; ?>"
                              style="display:inline-block; border:1px solid #ccc; padding:5px 10px; margin:2px; cursor:<?php echo $class==='unavailable'?'not-allowed':'pointer'; ?>; background-color:<?php echo $bg_color; ?>; color:<?php echo $class==='unavailable'?'#888':'black'; ?>;">
                            <?php echo esc_html(strtoupper($size->size_value)); ?>
                        </span>
                    <?php endforeach; ?>
                    <input type="hidden" id="selected-size" name="selected_size" value="">
                <?php else: ?>
                    <span style="color:red;">Товара нет в наличии</span>
                <?php endif; ?>
                </div>

                <!-- Кнопки -->
                <div class="buttons">
                <button class="add-to-fitting-room" id="fitting-btn" data-product-id="<?php echo $product->product_id; ?>">
                    <?php echo $fitting_button_text; ?>
                </button>
                <button class="btn-favorite" data-product-id="<?php echo $product->product_id; ?>">
                    <img src="<?php echo get_template_directory_uri(); ?>/images/<?php echo in_array($product->product_id, $favorite_ids) ? 'pink-heart.svg' : 'heart.svg'; ?>" alt="Избранное">
                </button>
                </div>

                <div class="product-nav" style="margin-top: 20px;">
                    <?php if ($prev_product): ?>
                        <a href="?product_id=<?php echo $prev_product; ?>" class="prev-product">&larr; Предыдущий товар</a>
                    <?php else: ?><span></span><?php endif; ?>
                    <?php if ($next_product): ?>
                        <a href="?product_id=<?php echo $next_product; ?>" class="next-product">Следующий товар &rarr;</a>
                    <?php else: ?><span></span><?php endif; ?>
                </div>

                <div style="margin-top:15px;">
                    <a href="<?php echo site_url('/dresses'); ?>" class="btn-back-to-catalog">В каталог</a>
                </div>
            </div>
        </div>

<?php
    } else { echo "<p>Товар с ID {$product_id} не найден.</p>"; }
} else { echo "<p>ID товара не указан.</p>"; }

get_footer();
?>

<style>
.size-item { display:inline-block; border:1px solid #ccc; padding:5px 10px; margin:2px; cursor:pointer; transition: all 0.2s ease; font-weight:bold; }
.size-item.unavailable { background-color:#e0e0e0; cursor:not-allowed; color:#888; }
.size-item.in-fitting { background-color:pink; color:white; cursor:pointer; }
.size-item.selected { transform: scale(1.2); border:2px solid #440000; }
</style>


<script>
document.addEventListener('DOMContentLoaded', () => {
    // =========================
    // Размеры и примерочная
    // =========================
    const sizeItems = document.querySelectorAll('.size-item');
    const selectedSizeInput = document.getElementById('selected-size');
    const fittingBtn = document.getElementById('fitting-btn');
    let currentlySelected = null;

    

    // Инициализация: первый розовый
    const inFittingItems = Array.from(sizeItems).filter(i => i.classList.contains('in-fitting'));
    if (inFittingItems.length > 0) {
        currentlySelected = inFittingItems[0];
        currentlySelected.classList.add('selected');
        selectedSizeInput.value = currentlySelected.dataset.sizeId;
        fittingBtn.textContent = 'Удалить из примерочной';
    }

    // Клик по размерам
    sizeItems.forEach(item => {
        if (item.classList.contains('unavailable')) return;

        item.addEventListener('click', () => {
            if (currentlySelected) currentlySelected.classList.remove('selected');
            currentlySelected = item;
            currentlySelected.classList.add('selected');
            selectedSizeInput.value = currentlySelected.dataset.sizeId;
            fittingBtn.textContent = item.classList.contains('in-fitting')
                ? 'Удалить из примерочной'
                : 'В примерочную';
        });
    });

    // =========================
    // Галерея
    // =========================
    const thumbnails = document.querySelectorAll('.thumbnail');
    const mainImage = document.getElementById('current-image');
    const nextBtn = document.querySelector('.next-btn');
    const prevBtn = document.querySelector('.prev-btn');
    let currentIndex = 0;

   /* function showImage(index) {
        if (thumbnails.length === 0) return;

        if (index < 0) index = thumbnails.length - 1;
        if (index >= thumbnails.length) index = 0;

        mainImage.src = thumbnails[index].src;

        thumbnails.forEach(t => t.classList.remove('active'));
        thumbnails[index].classList.add('active');

        currentIndex = index;
    }*/

    // Стрелки
    if (nextBtn) nextBtn.addEventListener('click', () => showImage(currentIndex + 1));
    if (prevBtn) prevBtn.addEventListener('click', () => showImage(currentIndex - 1));

    // Клик по миниатюрам
    thumbnails.forEach((thumb, index) => {
        thumb.addEventListener('click', () => showImage(index));
    });

    // Инициализация галереи
    showImage(0);
});
</script>

