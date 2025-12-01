<?php
/*
Template Name: Edit Product
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

// Проверяем, есть ли ID товара в URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id <= 0) {
    $_SESSION['edit_product_error'] = 'Товар не найден.';
    wp_safe_redirect(site_url('/admin/catalog/dresses'));
    exit;
}

// ---------------------------
// ПУТИ И КОНФИГ
// ---------------------------
$upload_dir = get_template_directory() . '/images/card/';
$relative_path = 'images/card/';

if (!file_exists($upload_dir)) {
    wp_mkdir_p($upload_dir);
}

// Получаем текущий товар
$current_product = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM Product WHERE product_id = %d", 
    $product_id
));

if (!$current_product) {
    $_SESSION['edit_product_error'] = 'Товар не найден.';
    wp_safe_redirect(site_url('/admin/catalog/dresses'));
    exit;
}

// Получаем существующие фото товара
$existing_photos = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM ProductPhoto WHERE product_id = %d ORDER BY display_order ASC", 
    $product_id
));

// Получаем типы товаров
$product_types = $wpdb->get_results("SELECT product_type_id, product_type_name FROM ProductType");

// ---------------------------
// Обработка POST (обновление товара)
// ---------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['product_name']) ? sanitize_text_field($_POST['product_name']) : '';
    $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
    $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
    $availability = isset($_POST['availability']) ? 1 : 0;
    $product_type_id = isset($_POST['product_type_id']) ? intval($_POST['product_type_id']) : 0;

    // Валидация
    if (empty($name) || $price <= 0 || $product_type_id <= 0) {
        $_SESSION['edit_product_error'] = 'Заполните название, цену и тип товара.';
        wp_safe_redirect($_SERVER['REQUEST_URI']);
        exit;
    }

    // Обновление товара
    $updated = $wpdb->update(
        'Product',
        [
            'product_name'    => $name,
            'description'     => $description,
            'price'           => $price,
            'availability'    => $availability,
            'product_type_id' => $product_type_id
        ],
        ['product_id' => $product_id],
        ['%s', '%s', '%f', '%d', '%d'],
        ['%d']
    );

    if ($updated === false) {
        $_SESSION['edit_product_error'] = 'Ошибка при обновлении товара.';
        wp_safe_redirect($_SERVER['REQUEST_URI']);
        exit;
    }

    // Обновление размеров
    if (!empty($_POST['sizes'])) {
        foreach ($_POST['sizes'] as $size_id => $qty) {
            $qty = intval($qty);
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM ProductSize WHERE product_id=%d AND size_id=%d",
                $product_id, $size_id
            ));
            if ($qty > 0) {
                if ($existing) {
                    $wpdb->update('ProductSize', ['quantity_in_stock'=>$qty], 
                        ['product_id'=>$product_id, 'size_id'=>$size_id], ['%d'], ['%d','%d']);
                } else {
                    $wpdb->insert('ProductSize', [
                        'product_id' => $product_id,
                        'size_id' => $size_id,
                        'quantity_in_stock' => $qty
                    ]);
                }
            } else if ($existing) {
                $wpdb->delete('ProductSize', ['product_id'=>$product_id, 'size_id'=>$size_id], ['%d','%d']);
            }
        }
    }

    // Обновление описаний существующих фото
    if (!empty($_POST['photo_descriptions_existing'])) {
        foreach ($_POST['photo_descriptions_existing'] as $index => $description) {
            $photo_id = isset($_POST['existing_photos'][$index]) ? intval($_POST['existing_photos'][$index]) : 0;
            
            if ($photo_id > 0) {
                $wpdb->update(
                    'ProductPhoto',
                    ['description' => sanitize_text_field($description)],
                    ['photo_id' => $photo_id],
                    ['%s'],
                    ['%d']
                );
            }
        }
    }

    // Обновление порядка существующих фото
    if (!empty($_POST['display_order_existing'])) {
        foreach ($_POST['display_order_existing'] as $index => $order) {
            $photo_id = isset($_POST['existing_photos'][$index]) ? intval($_POST['existing_photos'][$index]) : 0;
            
            if ($photo_id > 0) {
                $wpdb->update(
                    'ProductPhoto',
                    ['display_order' => intval($order)],
                    ['photo_id' => $photo_id],
                    ['%d'],
                    ['%d']
                );
            }
        }
    }

    // Обработка новых фото (БЕЗ физического сохранения файлов - только пути)
    if (!empty($_FILES['photos']['name'][0])) {
        foreach ($_FILES['photos']['name'] as $index => $orig_name) {
            if (empty($orig_name)) continue;

            // Берем только имя файла с расширением (без пути)
            $filename = basename($orig_name);
            $photo_url = $relative_path . $filename;
            $photo_description = isset($_POST['photo_descriptions'][$index]) ? sanitize_text_field($_POST['photo_descriptions'][$index]) : '';

            // Проверяем, существует ли файл в папке
            $full_path = $upload_dir . $filename;
            if (file_exists($full_path)) {
                // Добавляем в конец существующего списка
                $new_order = count($existing_photos) + $index + 1;
                
                $wpdb->insert('ProductPhoto', [
                    'product_id'    => $product_id,
                    'photo_url'     => $photo_url,
                    'description'   => $photo_description,
                    'display_order' => $new_order
                ]);
            } else {
                $_SESSION['photo_warning'] = "Файл '{$filename}' не найден в папке. Фото не добавлено.";
            }
        }
    }

    // Обработка замены существующих фото (тоже только пути)
    if (!empty($_FILES['replace_photos']['name'][0])) {
        foreach ($_FILES['replace_photos']['name'] as $index => $orig_name) {
            if (empty($orig_name)) continue;

            $photo_id = isset($_POST['replace_photo_id'][$index]) ? intval($_POST['replace_photo_id'][$index]) : 0;
            
            if ($photo_id > 0) {
                // Берем только имя нового файла
                $new_filename = basename($orig_name);
                $new_photo_url = $relative_path . $new_filename;

                // Проверяем, существует ли новый файл в папке
                $new_full_path = $upload_dir . $new_filename;
                if (file_exists($new_full_path)) {
                    // Обновляем только путь в базе (файл не двигается)
                    $wpdb->update(
                        'ProductPhoto',
                        ['photo_url' => $new_photo_url],
                        ['photo_id' => $photo_id],
                        ['%s'],
                        ['%d']
                    );
                } else {
                    $_SESSION['photo_warning'] = "Файл '{$new_filename}' не найден в папке. Замена не выполнена.";
                }
            }
        }
    }

    // Успех
    $_SESSION['edit_product_success'] = 'Товар успешно обновлён.';
    wp_safe_redirect(site_url('/admin/catalog/' . $category));
    exit;
}

// ---------------------------
// Обработка удаления фото (AJAX) - убираем физическое удаление файла
// ---------------------------
if (isset($_POST['action']) && $_POST['action'] === 'delete_photo') {
    $photo_id = isset($_POST['photo_id']) ? intval($_POST['photo_id']) : 0;
    
    if ($photo_id > 0) {
        // Удаляем только из базы (файл НЕ удаляется с диска)
        $deleted = $wpdb->delete('ProductPhoto', ['photo_id' => $photo_id], ['%d']);
        
        if ($deleted) {
            // Пересчитываем display_order оставшихся фото
            $remaining_photos = $wpdb->get_results($wpdb->prepare(
                "SELECT photo_id FROM ProductPhoto WHERE product_id = %d ORDER BY display_order ASC", 
                $product_id
            ));
            
            foreach ($remaining_photos as $index => $photo) {
                $wpdb->update(
                    'ProductPhoto',
                    ['display_order' => $index + 1],
                    ['photo_id' => $photo->photo_id],
                    ['%d'],
                    ['%d']
                );
            }
            
            wp_die('OK');
        }
    }
    
    wp_die('ERROR');
}
?>
<?php include get_template_directory() . '/modal.php'; ?>
<link rel="stylesheet" href="<?php echo esc_url(get_template_directory_uri() . '/style/add-product.css'); ?>">

<main class="admin-main">
    <div class="container">
        <form method="POST" enctype="multipart/form-data" class="add-product-form" id="edit-product-form">
            <div class="tovar">
                <h1>Редактировать товар</h1>

                <?php if (!empty($_SESSION['edit_product_error'])): ?>
                    <div class="error"><?php echo esc_html($_SESSION['edit_product_error']); unset($_SESSION['edit_product_error']); ?></div>
                <?php endif; ?>

                <?php if (!empty($_SESSION['photo_warning'])): ?>
                    <div class="warning"><?php echo esc_html($_SESSION['photo_warning']); unset($_SESSION['photo_warning']); ?></div>
                <?php endif; ?>

                <input type="hidden" name="product_id" value="<?php echo esc_attr($product_id); ?>">

                <label>Название товара:</label>
                <input type="text" name="product_name" value="<?php echo esc_attr($current_product->product_name); ?>" required>

                <label>Описание:</label>
                <textarea name="description" rows="4"><?php echo esc_textarea($current_product->description); ?></textarea>

                <label>Цена:</label>
                <input type="number" step="0.01" name="price" value="<?php echo esc_attr($current_product->price); ?>" required>

                <label>Тип товара:</label>
                <select name="product_type_id" required>
                    <option value="">Выберите тип товара</option>
                    <?php foreach ($product_types as $type): ?>
                        <option value="<?php echo intval($type->product_type_id); ?>" 
                            <?php selected($current_product->product_type_id, $type->product_type_id); ?>>
                            <?php echo esc_html($type->product_type_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label>
                    <input type="checkbox" name="availability" value="1" 
                        <?php checked($current_product->availability, 1); ?>> В наличии
                </label>
                <br>

                <div class="sizes-section">
                    <h2>Размеры и наличие</h2>
                    <div class="size-grid" id="size-grid">
                        <?php
                        $sizes = $wpdb->get_results("SELECT * FROM Size ORDER BY size_id ASC");

                        // Получаем уже существующие значения для этого товара
                        $existing_sizes = $wpdb->get_results($wpdb->prepare(
                            "SELECT size_id, quantity_in_stock FROM ProductSize WHERE product_id = %d",
                            $product_id
                        ));
                        $existing_sizes_map = [];
                        foreach ($existing_sizes as $es) {
                            $existing_sizes_map[$es->size_id] = $es->quantity_in_stock;
                        }

                        foreach ($sizes as $size): 
                            $qty = isset($existing_sizes_map[$size->size_id]) ? $existing_sizes_map[$size->size_id] : '';
                            $active_class = $qty ? 'active' : 'inactive';
                        ?>
                            <div class="size-wrapper" data-size-id="<?php echo esc_attr($size->size_id); ?>">
                                <div class="size-box <?php echo $active_class; ?>" 
                                    style="background-color: <?php echo $qty ? 'white' : '#e0e0e0'; ?>">
                                    <span class="size-label"><?php echo esc_html(strtoupper($size->size_value)); ?></span>
                                </div>
                                <input type="number" 
                                    class="size-qty" 
                                    name="sizes[<?php echo esc_attr($size->size_id); ?>]" 
                                    value="<?php echo esc_attr($qty); ?>" 
                                    min="1" 
                                    style="display: <?php echo $qty ? 'block' : 'none'; ?>; margin-top: 6px;">
                            </div>
                        <?php endforeach; ?>
                    </div>
            </div>


                <div class="form-actions">
                    <button type="submit" class="btn-submit">Сохранить изменения</button>
                    <a href="<?php echo site_url('/admin/catalog/' . $category); ?>" class="btn-cancel">Отмена</a>

                </div>
            </div>

            <div class="photo">
                <div class="photo-header">
                    <h2>Фотографии</h2>
                    <button type="button" class="btn-add-photo" id="add-photo-btn">+ Добавить фото</button>
                </div>

                <div id="photo-container" class="photo-container">
                    <?php if ($existing_photos): ?>
                        <?php foreach ($existing_photos as $photo): ?>
                            <div class="photo-block" data-photo-id="<?php echo esc_attr($photo->photo_id); ?>" draggable="true">
                                <div class="photo-preview" onclick="this.querySelector('input[type=file"]').click()">
                                    <input type="file" name="replace_photos[]" accept="image/*" style="display:none">
                                    <img src="<?php echo get_template_directory_uri() . '/' . $photo->photo_url; ?>" 
                                         alt="" style="display: block; max-width:100%; max-height:100%; object-fit: contain;" 
                                         draggable="false" oncontextmenu="return false;" onmousedown="return false;">
                                    <input type="hidden" name="existing_photos[]" value="<?php echo esc_attr($photo->photo_id); ?>">
                                    <input type="hidden" name="replace_photo_id[]" value="<?php echo esc_attr($photo->photo_id); ?>">
                                    <input type="hidden" name="existing_photo_urls[]" value="<?php echo esc_attr($photo->photo_url); ?>">
                                </div>
                                <input type="text" name="photo_descriptions_existing[]" 
                                       value="<?php echo esc_attr($photo->description); ?>" 
                                       placeholder="Описание фото">
                                <input type="hidden" name="display_order_existing[]" value="<?php echo esc_attr($photo->display_order); ?>">
                                <button type="button" class="remove-photo" data-photo-id="<?php echo esc_attr($photo->photo_id); ?>">
                                    <img src="<?php echo esc_url(get_template_directory_uri() . '/images/close1.svg'); ?>" alt="Удалить" class="close-icon">
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // ---------------------------
    // Основные переменные
    // ---------------------------
    const productId = <?php echo json_encode($product_id); ?>;
    const container = document.getElementById('photo-container');
    const addBtn = document.getElementById('add-photo-btn');
    let dragged = null;

    const availabilityCheckbox = document.querySelector('input[name="availability"]');
    const sizeSection = document.querySelector('.sizes-section');
    const sizeGrid = document.getElementById("size-grid");

    /*const confirmAvailabilityModal = document.getElementById('confirm-availability-modal');
    const confirmYes = document.getElementById('confirm-availability-yes');
    const confirmNo = document.getElementById('confirm-availability-no');

    const noSizesModal = document.getElementById('no-sizes-modal');
    const noSizesOk = document.getElementById('no-sizes-ok');*/

    const form = document.getElementById('edit-product-form') || document.getElementById('add-product-form');

    // ---------------------------
    // Функции
    // ---------------------------

    // Удаление фото
    function deletePhoto(photoId) {
    console.log("=== deletePhoto() START ===", photoId, productId);

    openModal({
        title: "Подтверждение удаления",
        message: "Вы действительно хотите удалить фото?",
        buttons: [
            {
                text: "Да",
                class: "modal-confirm-btn",
                onClick: () => {
                    const nonce = "<?php echo wp_create_nonce('delete_photo_nonce'); ?>";

                    fetch("/wp-admin/admin-ajax.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: `action=delete_photo&nonce=${nonce}&photo_id=${photoId}&product_id=${productId}`
                    })
                    .then(r => r.text())
                    .then(t => {
                        try {
                            const data = JSON.parse(t);
                            console.log("AJAX response:", data);
                            if (data.success) {
                                const block = container.querySelector(`.photo-block[data-photo-id="${photoId}"]`);
                                if (block) block.remove();
                                updateOrders();
                            } else {
                                alert(data.data || "Ошибка при удалении фото");
                            }
                        } catch (e) {
                            console.error("Ошибка парсинга JSON:", e, t);
                        }
                    })
                    .catch(err => console.error("AJAX error:", err));

                    closeModal();
                }
            },
            {
                text: "Отмена",
                class: "modal-cancel-btn",
                onClick: () => closeModal()
            }
        ]
    });
}


    // Обновление порядка фото
    function updateOrders() {
        container.querySelectorAll('.photo-block').forEach((block, index) => {
            const orderInput = block.querySelector('input[name="display_order[]"]') ||
                               block.querySelector('input[name="display_order_existing[]"]');
            if (orderInput) orderInput.value = index + 1;
        });
    }

    // Получение элемента для Drag & Drop
    function getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('.photo-block:not(.dragging)')];
        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closest.offset) {
                return { offset, element: child };
            }
            return closest;
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }

    // Привязка событий к существующим фото
    function setupExistingPhotoHandlers() {
        container.querySelectorAll('.photo-block').forEach(block => {
            const photoPreview = block.querySelector('.photo-preview');
            const removeBtn = block.querySelector('.remove-photo');
            const photoId = block.getAttribute('data-photo-id');
            const fileInput = photoPreview.querySelector('input[type="file"]');

            // Клик по превью
            photoPreview.style.cursor = 'pointer';
            photoPreview.addEventListener('click', (e) => {
                if (e.target.tagName !== 'INPUT') fileInput.click();
            });

            // Изменение файла
            fileInput.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (!file) return;
                const img = photoPreview.querySelector('img');
                const reader = new FileReader();
                reader.onload = ev => img.src = ev.target.result;
                reader.readAsDataURL(file);
            });

            // Кнопка удаления
            if (removeBtn) {
                removeBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    deletePhoto(photoId);
                });
            }

            // Drag & Drop
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
    }

    // ---------------------------
    // Инициализация фото
    // ---------------------------
    setupExistingPhotoHandlers();

    // Добавление нового фото
    addBtn.addEventListener('click', () => {
        const block = document.createElement('div');
        block.className = 'photo-block';
        block.draggable = true;
        block.innerHTML = `
            <div class="photo-preview" onclick="this.querySelector('input[type=file]').click()">
                <input type="file" name="photos[]" accept="image/*" style="display:none">
                <img src="" alt="" style="display:none; max-width:100%; max-height:100%; object-fit:contain;" draggable="false">
            </div>
            <input type="text" name="photo_descriptions[]" placeholder="Описание фото">
            <input type="hidden" name="display_order[]" value="">
            <input type="hidden" name="selected_filename[]" value="">
            <button type="button" class="remove-photo">
                <img src="<?php echo esc_url(get_template_directory_uri() . '/images/close1.svg'); ?>" alt="Удалить" class="close-icon">
            </button>
        `;

        const input = block.querySelector('input[type=file]');
        const img = block.querySelector('img');
        const filenameInput = block.querySelector('input[name="selected_filename[]"]');
        const removeBtn = block.querySelector('.remove-photo');

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

        removeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
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

        // Drag & Drop для нового фото
        block.addEventListener('dragstart', () => {
            dragged = block;
            block.classList.add('dragging');
        });
        block.addEventListener('dragend', () => {
            block.classList.remove('dragging');
            dragged = null;
            updateOrders();
        });

        container.appendChild(block);
        updateOrders();
    });

    // Drag & Drop контейнера
    container.addEventListener('dragover', (e) => {
        e.preventDefault();
        const afterElement = getDragAfterElement(container, e.clientY);
        if (afterElement == null) {
            container.appendChild(dragged);
        } else {
            container.insertBefore(dragged, afterElement);
        }
    });

    container.addEventListener('drop', () => setupExistingPhotoHandlers());

    // Валидация цены
    const priceInput = document.querySelector('input[name="price"]');
    if (priceInput) {
        priceInput.addEventListener('input', () => {
            priceInput.value = priceInput.value.replace(/[^0-9.]/g, '');
        });
    }

    // Проверка фото при отправке формы
    // Проверка фото при отправке формы
    if (form) {
        form.addEventListener('submit', (e) => {
            let hasPhotoError = false;

            // Фото-блоки
            container.querySelectorAll('.photo-block').forEach(block => {
                const fileInput = block.querySelector('input[name="photos[]"]');
                const descInput = block.querySelector('input[name="photo_descriptions[]"]');

                // Если есть описание, но нет загруженного файла → ошибка
                if (descInput && fileInput && descInput.value.trim() !== '' && !fileInput.files[0]) {
                    hasPhotoError = true;
                }
            });

            // === МОДАЛКА 1: ошибка фото ===
            if (hasPhotoError) {
                e.preventDefault();
                openModal({
                    title: "Предупреждение",
                    message: "Вы добавили описание, но не загрузили фото.",
                    buttons: []
                });
                return; // прерываем, чтобы не показывать следующие ошибки одновременно
            }

            // === Проверка размеров ===
            if (availabilityCheckbox && availabilityCheckbox.checked) {
                const hasSize = [...sizeGrid.querySelectorAll('.size-qty')]
                    .some(input => input.value && parseInt(input.value) > 0);

                // === МОДАЛКА 2: ошибка размеров ===
                if (!hasSize) {
                    e.preventDefault();
                    openModal({
                        title: "Предупреждение",
                        message: "Вы выбрали 'В наличии', но не указали ни одного размера.",
                        buttons: []
                    });
                }
            }
        });
    }


    // ---------------------------
    // Инициализация размеров и галочки "В наличии"
    // ---------------------------
    if (availabilityCheckbox && sizeGrid) {

        const hasSizes = [...sizeGrid.querySelectorAll('.size-qty')]
            .some(input => input.value && parseInt(input.value) > 0);

        if (!hasSizes) {
            availabilityCheckbox.checked = false;
            sizeSection.style.display = 'none';
        } else {
            availabilityCheckbox.disabled = false;
            sizeSection.style.display = availabilityCheckbox.checked ? 'block' : 'none';
        }

        // Обработка изменения галочки "В наличии"
        availabilityCheckbox.addEventListener('change', () => {

            // Если галочку снимаем — открываем модальное окно
            if (!availabilityCheckbox.checked) {

                openModal({
                    title: "Подтверждение",
                    message: "Вы уверены, что хотите снять «В наличии»? Все размеры будут удалены.",
                    buttons: [
                        {
                            text: "Да",
                            class: "modal-confirm-btn",
                            onClick: () => {
                                // очищаем размеры
                                sizeGrid.querySelectorAll('.size-wrapper').forEach(wrapper => {
                                    const box = wrapper.querySelector('.size-box');
                                    const input = wrapper.querySelector('.size-qty');

                                    box.classList.remove('active');
                                    box.classList.add('inactive');
                                    box.style.backgroundColor = '#e0e0e0';

                                    input.value = '';
                                    input.style.display = 'none';
                                });

                                sizeSection.style.display = 'none';
                                availabilityCheckbox.checked = false;

                                closeModal();
                            }
                        },
                        {
                            text: "Нет",
                            class: "modal-cancel-btn",
                            onClick: () => {
                                availabilityCheckbox.checked = true;
                                closeModal();
                            }
                        }
                    ]
                });

            } else {
                // Если ставим галочку обратно — показываем размеры
                sizeSection.style.display = 'block';
            }
        });
    }


    // ---------------------------
    // Модалки размеров (универсальная модалка)
    // ---------------------------
    if (sizeGrid) {
        let targetBox = null;

        sizeGrid.addEventListener('click', (e) => {
            const box = e.target.closest(".size-box");
            if (!box) return;

            const wrapper = box.closest(".size-wrapper");
            const input = wrapper.querySelector(".size-qty");

            // Если размер не активен → активируем
            if (box.classList.contains("inactive")) {
                box.classList.remove("inactive");
                box.classList.add("active");
                box.style.backgroundColor = "white";
                input.style.display = "block";
                input.value = input.value || 1;
                return;
            }

            // Если активен → спрашиваем подтверждение снятия
            if (box.classList.contains("active")) {
                targetBox = box;

                openModal({
                    title: "Удалить размер?",
                    message: "Вы уверены, что хотите удалить этот размер?",
                    buttons: [
                        {
                            text: "Да",
                            class: "modal-confirm-btn",
                            onClick: () => {
                                if (targetBox) {
                                    const wrap = targetBox.closest('.size-wrapper');
                                    const inp = wrap.querySelector('.size-qty');

                                    inp.value = '';
                                    inp.style.display = 'none';

                                    targetBox.classList.remove('active');
                                    targetBox.classList.add('inactive');
                                    targetBox.style.backgroundColor = '#e0e0e0';
                                }

                                targetBox = null;
                                closeModal();
                            }
                        },
                        {
                            text: "Нет",
                            class: "modal-cancel-btn",
                            onClick: () => {
                                targetBox = null;
                                closeModal();
                            }
                        }
                    ]
                });
            }
        });
    }


    


});
</script>

