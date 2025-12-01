<?php
/*
Template Name: Админ Карточка товара
*/
include get_template_directory() . '/admin-header.php';
global $wpdb;

// Получаем ID товара из URL
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;



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

        
        ?>

        <link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/style.css">
    <link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/style/admin-card.css">

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
        <div class="sizes-row">
            <?php foreach ($all_sizes as $size):
                $qty = $sizes_qty[$size->size_id] ?? 0;
                $available = $qty > 0;

                if (!$available) {
                    $class = 'unavailable';
                } else {
                    $class = 'available';
                }
            ?>
                <div class="size-wrapper">
                    <!-- Верхний квадратик с размером -->
                    <span class="size-item <?php echo $class; ?>">
                        <?php echo esc_html(strtoupper($size->size_value)); ?>
                    </span>

                    <!-- Нижний квадратик с количеством -->
                    <?php if ($available): ?>
                        <span class="size-qty-box"><?php echo intval($qty); ?></span>
                    <?php else: ?>
                        <span class="size-qty-box empty"></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <span style="color:red;">Товара нет в наличии</span>
    <?php endif; ?>
</div>



                <!-- Кнопки -->
                

                <div class="product-nav" style="margin-top: 20px;">
                    <?php if ($prev_product): ?>
                        <a href="?product_id=<?php echo $prev_product; ?>" class="prev-product">&larr; Предыдущий товар</a>
                    <?php else: ?><span></span><?php endif; ?>
                    <?php if ($next_product): ?>
                        <a href="?product_id=<?php echo $next_product; ?>" class="next-product">Следующий товар &rarr;</a>
                    <?php else: ?><span></span><?php endif; ?>
                </div>

                
            </div>
        </div>

<?php
    } else { echo "<p>Товар с ID {$product_id} не найден.</p>"; }
} else { echo "<p>ID товара не указан.</p>"; }

get_footer();
?>



<script>
document.addEventListener('DOMContentLoaded', () => {
    // =========================
    // Размеры и примерочная
    // =========================
  /*  const sizeItems = document.querySelectorAll('.size-item');
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
    });*/

    // =========================
    // Галерея
    // =========================
    const thumbnails = document.querySelectorAll('.thumbnail');
    const mainImage = document.getElementById('current-image');
    const nextBtn = document.querySelector('.next-btn');
    const prevBtn = document.querySelector('.prev-btn');
    let currentIndex = 0;

    function showImage(index) {
        if (thumbnails.length === 0) return;

        if (index < 0) index = thumbnails.length - 1;
        if (index >= thumbnails.length) index = 0;

        mainImage.src = thumbnails[index].src;

        thumbnails.forEach(t => t.classList.remove('active'));
        thumbnails[index].classList.add('active');

        currentIndex = index;
    }

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

