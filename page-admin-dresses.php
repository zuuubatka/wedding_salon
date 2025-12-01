<?php
/*
Template Name: Admin Catalog
*/
include get_template_directory() . '/admin-header.php';
global $wpdb;

// ===== Получаем slug категории из URL через query_var =====
$category_slug = get_query_var('category', '');
error_log("Админ: slug категории = " . $category_slug);

// ===== Находим тип товара по slug =====
$product_type = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM ProductType WHERE slug = %s LIMIT 1",
    $category_slug
));

if (!$product_type) {
    echo "<p>Категория не найдена.</p>";
    exit;
}

// ===== Получаем все товары для этой категории =====
$products = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM Product WHERE product_type_id = %d ORDER BY product_id ASC",
    $product_type->product_type_id
));

?>

<script>
console.log("Slug из URL:", "<?php echo $category_slug; ?>");
console.log("Найденный тип товара:", "<?php echo $product_type->product_type_name; ?>");
</script>

<?php include get_template_directory() . '/modal.php'; ?>
<link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/style/admin.css">

<h1 class="catalog-title">
    Товары категории: <?php echo esc_html($product_type->product_type_name); ?>
</h1>

<div class="admin-catalog-page">

    <!-- Пустая карточка для добавления нового товара -->
    <div class="product-card add-product">
        <a href="<?php echo site_url('/admin/add-product/?category=' . $category_slug); ?>">


            <div class="add-icon">+</div>
            <p>Добавить товар</p>
        </a>
    </div>

    <!-- Карточки существующих товаров -->
    <?php if ($products): ?>
        <?php foreach ($products as $product): ?>
            <?php
            $main_photo = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM ProductPhoto WHERE product_id = %d ORDER BY display_order ASC LIMIT 1",
                $product->product_id
            ));
            ?>
            <div class="product-card" data-product-id="<?php echo $product->product_id; ?>">
                <div class="admin-actions">
                    <a href="<?php echo site_url('/admin/edit-product/?id=' . $product->product_id . '&category=' . $category_slug); ?>" class="edit-btn">
                        <img src="<?php echo get_template_directory_uri(); ?>/images/edit.svg" alt="редактировать">
                    </a>
                    <button class="delete-btn" data-id="<?php echo $product->product_id; ?>">
                        <img src="<?php echo get_template_directory_uri(); ?>/images/delete.svg" alt="удалить">
                    </button>
                </div>
                <a href="<?php echo site_url('/admin/card/?product_id=' . $product->product_id); ?>">

                    <div class="product-card__image">
                        <?php if ($main_photo): ?>
                            <img src="<?php echo get_template_directory_uri() . '/' . $main_photo->photo_url; ?>" 
                                alt="<?php echo esc_attr($product->product_name); ?>">
                        <?php else: ?>
                            <img src="<?php echo get_template_directory_uri(); ?>/images/no-image.jpg" 
                                alt="Нет изображения">
                        <?php endif; ?>
                    </div>
                    <h4><?php echo esc_html($product->product_name); ?></h4>
                    <p>Цена: <?php echo (int)$product->price; ?> Br</p>
                </a>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Товары не найдены.</p>
    <?php endif; ?>

</div>



<script>
document.addEventListener("DOMContentLoaded", () => {
    let currentProductId = null;

    // Клик по кнопке удаления товара
    document.querySelectorAll(".delete-btn").forEach(btn => {
        btn.addEventListener("click", () => {
            currentProductId = btn.dataset.id;

            openModal({
                title: "Подтверждение удаления",
                message: "Вы уверены, что хотите удалить этот товар?",
                buttons: [
                    {
                        text: "Удалить",
                        class: "modal-confirm-btn",
                        onClick: () => {
                            if (!currentProductId) return;

                            fetch("<?php echo admin_url('admin-ajax.php'); ?>", {
                                method: "POST",
                                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                                body: new URLSearchParams({
                                    action: "delete_product",
                                    product_id: currentProductId
                                })
                            })
                            .then(res => res.text())
                            .then(() => {
                                const card = document.querySelector(`.product-card[data-product-id="${currentProductId}"]`);
                                if (card) card.remove();
                                closeModal();
                                currentProductId = null;
                            });
                        }
                    },
                    {
                        text: "Отмена",
                        class: "modal-cancel-btn",
                        onClick: () => {
                            closeModal();
                            currentProductId = null;
                        }
                    }
                ]
            });
        });
    });
});
</script>


<style>
/*НЕ УБИРАТЬ*/
.modal {
    display: none;
}
</style>
