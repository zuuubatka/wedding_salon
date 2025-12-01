<?php
/*
Template Name: Add Product
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Проверка прав
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Администратор') {
    wp_safe_redirect(site_url('/login'));
    exit;
}

global $wpdb;

// ---------------------------
// Получаем категорию из URL
// ---------------------------
$category_slug = $_GET['category'] ?? '';
if (!$category_slug) {
    $_SESSION['add_product_error'] = 'Категория не указана!';
    wp_safe_redirect(site_url('/admin/catalog'));
    exit;
}

$product_type = $wpdb->get_row($wpdb->prepare(
    "SELECT product_type_id, product_type_name FROM ProductType WHERE slug = %s LIMIT 1",
    $category_slug
));

if (!$product_type) {
    $_SESSION['add_product_error'] = 'Категория не найдена!';
    wp_safe_redirect(site_url('/admin/catalog'));
    exit;
}

$product_type_id = $product_type->product_type_id;
error_log("Add Product: category_slug = $category_slug, product_type_id = $product_type_id");

// ---------------------------
// Пути и конфиг для фото
// ---------------------------
$upload_dir = get_template_directory() . '/images/card/';
$relative_path = 'images/card/';

if (!file_exists($upload_dir)) {
    wp_mkdir_p($upload_dir);
}

// ---------------------------
// Обработка POST
// ---------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = isset($_POST['product_name']) ? sanitize_text_field($_POST['product_name']) : '';
    $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
    $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
    $availability = isset($_POST['availability']) ? 1 : 0;

    if (empty($name) || $price <= 0) {
        $_SESSION['add_product_error'] = 'Заполните название и цену.';
        wp_safe_redirect($_SERVER['REQUEST_URI']);
        exit;
    }

    // Генерация уникального артикула
    do {
        $article = mt_rand(100000, 999999);
        $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `Product` WHERE `article` = %d", $article));
    } while ($exists > 0);

    // Вставка товара
    $inserted = $wpdb->insert('Product', [
        'product_name'    => $name,
        'article'         => $article,
        'description'     => $description,
        'price'           => $price,
        'availability'    => $availability,
        'product_type_id' => $product_type_id
    ]);

    if ($inserted === false) {
        $_SESSION['add_product_error'] = 'Ошибка при добавлении товара в базу.';
        wp_safe_redirect($_SERVER['REQUEST_URI']);
        exit;
    }

    $product_id = $wpdb->insert_id;

    // ---------------------------
    // Сохранение размеров и количества
    // ---------------------------
    if (!empty($_POST['sizes'])) {
        foreach ($_POST['sizes'] as $size_id => $qty) {
            $qty = intval($qty);
            if ($qty > 0) {
                $wpdb->insert('ProductSize', [
                    'product_id'        => $product_id,
                    'size_id'           => $size_id,
                    'quantity_in_stock' => $qty
                ]);
            }
        }
    }

    // ---------------------------
    // Обработка фото (если файлы уже загружены вручную)
    // ---------------------------
    if (!empty($_FILES['photos']['name'][0])) {
        foreach ($_FILES['photos']['name'] as $index => $orig_name) {
            if (empty($orig_name)) continue;

            $filename = basename($orig_name);
            $photo_url = $relative_path . $filename;
            $photo_description = isset($_POST['photo_descriptions'][$index]) ? sanitize_text_field($_POST['photo_descriptions'][$index]) : '';

            $full_path = $upload_dir . $filename;
            if (file_exists($full_path)) {
                $wpdb->insert('ProductPhoto', [
                    'product_id'    => $product_id,
                    'photo_url'     => $photo_url,
                    'description'   => $photo_description,
                    'display_order' => $index + 1
                ]);
            } else {
                $_SESSION['photo_warning'] = "Файл '{$filename}' не найден в папке {$upload_dir}. Фото не добавлено.";
            }
        }
    }

    // Успех — редирект на список товаров категории
    wp_safe_redirect(site_url('/admin/catalog/' . $category_slug));
    exit;
}

// ---------------------------
// Получаем все размеры для формы
// ---------------------------
$sizes = $wpdb->get_results("SELECT * FROM Size ORDER BY size_id ASC");

// ---------------------------
// Подключение CSS и начало формы
// ---------------------------
?>

<link rel="stylesheet" href="<?php echo esc_url(get_template_directory_uri() . '/style/add-product.css'); ?>">

<main class="admin-main">
    <div class="container">
        <form method="POST" enctype="multipart/form-data" class="add-product-form" id="add-product-form">
            <div class="tovar">
    <h1>Добавить товар в категорию: <?php echo esc_html($product_type->product_type_name); ?></h1>

    <?php if (!empty($_SESSION['add_product_error'])): ?>
        <div class="error"><?php echo esc_html($_SESSION['add_product_error']); unset($_SESSION['add_product_error']); ?></div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['photo_warning'])): ?>
        <div class="warning"><?php echo esc_html($_SESSION['photo_warning']); unset($_SESSION['photo_warning']); ?></div>
    <?php endif; ?>

    <input type="hidden" name="product_type_id" value="<?php echo intval($product_type_id); ?>">

    <label>Название товара:</label>
    <input type="text" name="product_name" required>

    <label>Описание:</label>
    <textarea name="description" rows="4"></textarea>

    <label>Цена:</label>
    <input type="number" step="0.01" name="price" required>

    <label>
        <input type="checkbox" name="availability"> В наличии
    </label>

    <div class="sizes-section">
        <h2>Размеры и наличие</h2>
        <div class="size-grid" id="size-grid">
            <?php foreach ($sizes as $size): ?>
            <div class="size-wrapper" data-size-id="<?php echo esc_attr($size->size_id); ?>">
                <div class="size-box inactive">
                    <span class="size-label"><?php echo esc_html(strtoupper($size->size_value)); ?></span>
                </div>
                <input type="number" name="sizes[<?php echo esc_attr($size->size_id); ?>]" class="size-qty" value="" min="1" style="display:none;">
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    

    <button type="submit" class="btn-submit">Добавить товар</button>

    </div>


            <!-- Модалка для подтверждения удаления -->
            <div id="confirm-modal" class="confirm-modal" style="display:none;">
                <div class="confirm-content">
                    <p>Удалить этот размер из наличия?</p>
                    <button type="button" id="confirm-yes">Да</button>
                    <button type="button" id="confirm-no">Отмена</button>
                </div>
            </div>

             <!-- Модалка для подтверждения удаления всех размеров -->
            <div id="confirm-availability-modal" class="confirm-modal" style="display:none;">
                <div class="confirm-content">
                    <p>Вы снимаете галочку "В наличии". Все размеры этого товара будут удалены. Продолжить?</p>
                    <button type="button" id="confirm-availability-yes">Да</button>
                    <button type="button" id="confirm-availability-no">Отмена</button>
                </div>
            </div>

            <!-- Модалка для ошибки при отсутствии размеров -->
            <div id="no-sizes-modal" class="confirm-modal" style="display:none;">
                <div class="confirm-content">
                    <p>Вы выбрали "В наличии", но не указали ни одного размера!</p>
                    <button type="button" id="no-sizes-ok">OK</button>
                </div>
            </div>

            <!-- Модалка для ошибки при отсутствии фото -->
            <div id="no-photo-modal" class="confirm-modal" style="display:none;">
                <div class="confirm-content">
                    <p>Указано описание фото, но само фото не выбрано!</p>
                    <button type="button" id="no-photo-ok">OK</button>
                </div>
            </div>

                

            <div class="photo">
                <div class="photo-header">
                    <h1>Фотографии</h1>
                    <button type="button" class="btn-add-photo" id="add-photo-btn">+ Добавить фото</button>
                </div>

                <div id="photo-container" class="photo-container"></div>
            </div>
        </form>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('photo-container');
    const addBtn = document.getElementById('add-photo-btn');
    let dragged = null;

    addBtn.addEventListener('click', () => {
    const block = document.createElement('div');
    block.className = 'photo-block';
    block.draggable = true;
    block.innerHTML = `
        <div class="photo-preview" onclick="this.querySelector('input[type=file]').click()">
            <input type="file" name="photos[]" accept="image/*" style="display:none">
            <img src="" alt="Предпросмотр" style="display:none; max-width:100%; max-height:100%; object-fit: contain;" draggable="false" oncontextmenu="return false;" onmousedown="return false;">
        </div>
        <input type="text" name="photo_descriptions[]" placeholder="Описание фото">
        <input type="hidden" name="display_order[]" value="">
        <input type="hidden" name="selected_filename[]" value="">
        <button type="button" class="remove-photo">
            <img src="<?php echo esc_url(get_template_directory_uri() . '/images/close1.svg'); ?>" alt="Удалить" class="close-icon">

        </button>
    `;

    const input = block.querySelector('input[type=file]');
    const img = block.querySelector('img[alt="Предпросмотр"]');
    const filenameInput = block.querySelector('input[name="selected_filename[]"]');
    const removeBtn = block.querySelector('.remove-photo');

    // Стили для кнопки удаления с SVG
    removeBtn.style.cssText = `
        position: absolute;
        top: 4px;
        right: 6px;
        background: none;
        border: none;
        cursor: pointer;
        padding: 0;
        width: 20px;
        height: 20px;
        z-index: 10;
    `;

    const closeIcon = removeBtn.querySelector('.close-icon');
    closeIcon.style.cssText = `
        width: 100%;
        height: 100%;
        object-fit: contain;
        pointer-events: none;
    `;

    removeBtn.addEventListener('click', () => {
        block.remove();
        updateOrders();
    });

    input.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (!file) return;

        filenameInput.value = file.name;

        const reader = new FileReader();
        reader.onload = (ev) => {
            img.src = ev.target.result;
            img.style.display = 'block';
        };
        reader.readAsDataURL(file);
    });

    container.appendChild(block);
    updateOrders();

    // Drag and drop
    block.addEventListener('dragstart', () => {
        dragged = block;
        block.classList.add('dragging');
    });

    block.addEventListener('dragend', () => {
        block.classList.remove('dragging');
        dragged = null;
        updateOrders();
    });
    });


    container.addEventListener('dragover', (e) => {
        e.preventDefault();
        const afterElement = getDragAfterElement(container, e.clientY);
        if (afterElement == null) {
            container.appendChild(dragged);
        } else {
            container.insertBefore(dragged, afterElement);
        }
    });

    function getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('.photo-block:not(.dragging)')];

        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }

    function updateOrders() {
        container.querySelectorAll('.photo-block').forEach((block, index) => {
            const orderInput = block.querySelector('input[name="display_order[]"]');
            if (orderInput) {
                orderInput.value = index + 1;
            }
        });
    }

    const priceInput = document.querySelector('input[name="price"]');
    if (priceInput) {
        priceInput.addEventListener('input', () => {
            priceInput.value = priceInput.value.replace(/[^0-9.]/g, '');
        });
    }

    // Предотвращаем отправку формы, если не выбраны файлы
    document.getElementById('add-product-form').addEventListener('submit', (e) => {
        const fileInputs = container.querySelectorAll('input[name="photos[]"]');
        let hasEmptyFiles = false;
        
        fileInputs.forEach(input => {
            if (input.hasAttribute('required') && !input.files[0]) {
                hasEmptyFiles = true;
            }
        });

        if (hasEmptyFiles) {
            e.preventDefault();
            alert('Пожалуйста, выберите файлы для всех добавленных фотографий или удалите пустые блоки.');
            return false;
        }
    });
});

document.addEventListener("DOMContentLoaded", () => {
    const sizeGrid = document.getElementById("size-grid");
    const modal = document.getElementById('confirm-modal');
    const yesBtn = document.getElementById('confirm-yes');
    const noBtn = document.getElementById('confirm-no');
    let targetBox = null;

    sizeGrid.addEventListener("click", (e) => {
        const box = e.target.closest(".size-box");
        if (!box) return;

        const wrapper = box.closest(".size-wrapper");
        const input = wrapper.querySelector(".size-qty");

        // если был неактивный (серый)
        if (box.classList.contains("inactive")) {
            box.classList.remove("inactive");
            box.classList.add("active");
            box.style.backgroundColor = "white";
            input.style.display = "block";
            input.value = input.value || 1;
        } 
        // если активный (белый) — открываем модалку подтверждения
        else if (box.classList.contains('active')) {
            targetBox = box;
            modal.style.display = 'flex';
        }
    });

    // Кнопка "Да" в модалке
    yesBtn.addEventListener('click', () => {
        if (targetBox) {
            const wrapper = targetBox.closest('.size-wrapper');
            const input = wrapper.querySelector('.size-qty');
            input.value = '';
            input.style.display = 'none';
            targetBox.classList.remove('active');
            targetBox.classList.add('inactive');
            targetBox.style.backgroundColor = '#e0e0e0';
        }
        modal.style.display = 'none';
        targetBox = null;
    });

    // Кнопка "Нет" в модалке
    noBtn.addEventListener('click', () => {
        modal.style.display = 'none';
        targetBox = null;
    });
});

document.addEventListener("DOMContentLoaded", () => {
    const availabilityCheckbox = document.querySelector('input[name="availability"]');
    const sizeSection = document.querySelector('.sizes-section'); // блок с размерами
    const sizeGrid = document.getElementById("size-grid");

    const confirmAvailabilityModal = document.getElementById('confirm-availability-modal');
    const confirmYes = document.getElementById('confirm-availability-yes');
    const confirmNo = document.getElementById('confirm-availability-no');

    // Модалка если нет ни одного размера
    const noSizesModal = document.getElementById('no-sizes-modal');
    const noSizesOk = document.getElementById('no-sizes-ok');

    // --- Снятие галочки "В наличии" ---
    availabilityCheckbox.addEventListener('change', (e) => {
        if (!availabilityCheckbox.checked) {
            // Всегда показываем модалку при попытке снять галочку
            confirmAvailabilityModal.style.display = 'flex';
        } else {
            // Галочка установлена обратно — показываем блок размеров
            sizeSection.style.display = 'block';
        }
    });

    confirmYes.addEventListener('click', () => {
        // Убираем все размеры
        sizeGrid.querySelectorAll('.size-wrapper').forEach(wrapper => {
            const box = wrapper.querySelector('.size-box');
            const input = wrapper.querySelector('.size-qty');

            box.classList.remove('active');
            box.classList.add('inactive');
            box.style.backgroundColor = '#e0e0e0';
            input.value = '';
            input.style.display = 'none';
        });

        // Скрываем блок размеров
        sizeSection.style.display = 'none';

        // Снимаем галочку окончательно
        availabilityCheckbox.checked = false;

        confirmAvailabilityModal.style.display = 'none';
    });

    confirmNo.addEventListener('click', () => {
        // Возвращаем галочку обратно
        availabilityCheckbox.checked = true;
        confirmAvailabilityModal.style.display = 'none';
    });

    // --- Проверка при отправке формы ---
    const form = document.getElementById('add-product-form') || document.getElementById('edit-product-form');

    form.addEventListener('submit', (e) => {
        if (availabilityCheckbox.checked) {
            const hasSize = [...sizeGrid.querySelectorAll('.size-qty')].some(input => input.value && parseInt(input.value) > 0);
            if (!hasSize) {
                e.preventDefault();
                if (noSizesModal) {
                    noSizesModal.style.display = 'flex';
                } else {
                    alert('Вы выбрали "В наличии", но не указали ни одного размера.');
                }
                return false;
            }
        }
    });

    noSizesOk.addEventListener('click', () => {
        noSizesModal.style.display = 'none';
    });

    // --- Инициализация видимости блока размеров при загрузке ---
    sizeSection.style.display = availabilityCheckbox.checked ? 'block' : 'none';
});

document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById('add-product-form');
    if (!form) return;

    const photoContainer = document.getElementById('photo-container');
    const noPhotoModal = document.getElementById('no-photo-modal');
    const noPhotoOk = document.getElementById('no-photo-ok');

    if (!photoContainer || !noPhotoModal || !noPhotoOk) return;

    // --- Проверка при отправке формы ---
    form.addEventListener('submit', (e) => {
        let hasPhotoError = false;

        photoContainer.querySelectorAll('.photo-block').forEach(block => {
            const fileInput = block.querySelector('input[name="photos[]"]');
            const descInput = block.querySelector('input[name="photo_descriptions[]"]');

            if (descInput && fileInput) {
                // Если есть описание, но нет файла
                if (descInput.value.trim() !== '' && !fileInput.files[0]) {
                    hasPhotoError = true;
                }
            }
        });

        if (hasPhotoError) {
            e.preventDefault();
            noPhotoModal.style.display = 'flex';
        }
    });

    noPhotoOk.addEventListener('click', () => {
        noPhotoModal.style.display = 'none';
    });
});
</script>

