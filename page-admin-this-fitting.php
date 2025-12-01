<?php
/*
Template Name: Админ определенная примерка
*/

include get_template_directory() . '/admin-header.php';
global $wpdb;

// Получаем ID примерки
$fitting_id = intval($_GET['id'] ?? 0);

if (!$fitting_id) {
    echo "<h2>Ошибка: Не передан ID примерки.</h2>";
    exit;
}

$fitting = $wpdb->get_row("
    SELECT f.*, 
           c.first_name AS client_name,
           c.email, 
           c.phone AS client_phone,
           d.fitting_date, 
           d.fitting_time, 
           d.room_number,
           e.last_name AS emp_last, 
           e.first_name AS emp_first, 
           e.middle_name AS emp_middle
    FROM Fitting f
    LEFT JOIN FittingRoom r ON r.fitting_room_id = f.fitting_room_id
    LEFT JOIN Client c ON c.client_id = r.client_id
    LEFT JOIN FittingDateTime d ON d.fitting_datetime_id = f.fitting_datetime_id
    LEFT JOIN Employee e ON e.employee_id = f.employee_id
    WHERE f.fitting_id = $fitting_id
");

if (!$fitting) {
    echo "<h2>Примерка не найдена.</h2>";
    exit;
}

// Загружаем сотрудников-консультантов
$consultants = $wpdb->get_results("
    SELECT e.employee_id,
           e.first_name,
           e.last_name,
           e.middle_name
    FROM Employee e
    JOIN Role r ON r.role_id = e.role_id
    WHERE r.role_name = 'Консультант'
    ORDER BY e.last_name, e.first_name
");

// Загружаем все товары примерочной корзины
$rows = $wpdb->get_results( $wpdb->prepare("
    SELECT 
        p.product_id,
        p.product_name,
        p.article,
        p.price,
        s.size_value,
        ph.photo_url
    FROM CartItem ci
    JOIN ProductSize ps ON ps.product_size_id = ci.product_size_id
    JOIN Product p ON p.product_id = ps.product_id
    JOIN Size s ON s.size_id = ps.size_id
    LEFT JOIN ProductPhoto ph 
        ON ph.product_id = p.product_id 
       AND ph.display_order = 1
    WHERE ci.fitting_room_id = %d
", $fitting->fitting_room_id ) );

// === ГРУППИРУЕМ ПО ТОВАРУ ===
$products = [];
foreach ($rows as $r) {
    if (!isset($products[$r->product_id])) {
        $products[$r->product_id] = [
            'product_id' => $r->product_id,
            'name'       => $r->product_name,
            'article'    => $r->article,
            'price'      => $r->price,
            'photo'      => $r->photo_url ?: 'images/no-image.png',
            'sizes'      => []
        ];
    }
    $products[$r->product_id]['sizes'][] = $r->size_value;
}

// Форматирование даты/времени
$fitting_date = $fitting->fitting_date ? (new DateTime($fitting->fitting_date))->format('d.m.Y') : '';
$fitting_time = $fitting->fitting_time ? (new DateTime($fitting->fitting_time))->format('H:i') : '';

?>

<link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/style/admin-this-fitting.css">

<div class="fitting-page">

    <div class="product-grid">

        <!-- === ИНФОБЛОК === -->
        <div class="info-block">
            <h2>Информация о примерке</h2>

            <div class="info-section">
                <p class="info-row"><strong>Статус:</strong> <span id="fitting-status"><?= $fitting->fitting_status ?></span></p>
            </div>

            <div class="info-section">
                <p class="info-row"><strong>Клиент:</strong> <?= $fitting->client_name ?></p>
                <p class="info-row"><strong>Телефон:</strong> <?= $fitting->client_phone ?></p>
                <p class="info-row"><strong>Email:</strong> <?= $fitting->email ?></p>

                <div class="info-section">
                    <p class="info-row">
                        <strong>Комментарий:</strong>
                        <?php if (!empty(trim($fitting->client_comment))): ?>
                            <span class="comment-preview" data-full="<?= esc_attr($fitting->client_comment) ?>">
                                <?= esc_html($fitting->client_comment) ?>
                            </span>
                            <button class="show-full-comment-btn" style="display:none;">Показать полностью</button>
                        <?php else: ?>
                            <span>нет комментария</span>
                        <?php endif; ?>
                    </p>
                </div>



                
            </div>

            <!-- Назначение сотрудника -->
            <div class="info-section">
                <p class="info-row"><strong>Сотрудник:</strong></p>

                <select id="employee-select" class="employee-select">
                    <option value="">Не назначен</option>
                    <?php foreach ($consultants as $emp): ?>
                        <option value="<?= $emp->employee_id ?>"
                            <?= ($fitting->employee_id == $emp->employee_id ? 'selected' : '') ?>>
                            <?= esc_html(trim($emp->last_name . ' ' . $emp->first_name . ' ' . $emp->middle_name)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <?php $button_text = $fitting->employee_id ? 'Переназначить сотрудника' : 'Назначить сотрудника'; ?>
                <button id="assign-employee-btn" class="assign-btn"><?= $button_text ?></button>
            </div>

            <div class="info-section">
                <p class="info-row"><strong>Дата:</strong> <?= $fitting_date ?></p>
                <p class="info-row"><strong>Время:</strong> <?= $fitting_time ?></p>
                <p class="info-row"><strong>Комната:</strong> <?= $fitting->room_number ?></p>
            </div>

            <div class="cancel-block">
                <button id="cancel-fitting-btn" class="cancel-btn">Отменить примерку</button>
            </div>
        </div>

        <!-- === КАРТОЧКИ ТОВАРОВ === -->
        <?php foreach ($products as $p): ?>
            <a href="<?= site_url('/card/?product_id=' . $p['product_id']); ?>" class="product-card">
                <img src="<?= esc_url(get_template_directory_uri() . '/' . $p['photo']); ?>" alt="<?= esc_attr($p['name']) ?>">
                <div class="product-info">
                    <p><strong><?= esc_html($p['name']) ?></strong></p>
                    <p>Артикул: <?= esc_html($p['article']) ?></p>

                    <?php $label = (count($p['sizes']) > 1) ? "Размеры" : "Размер"; ?>
                    <p><?= $label ?>: <?= esc_html(implode(", ", $p['sizes'])) ?></p>
                    
                    <p>Цена: <?= number_format($p['price'], 0, '.', ' ') ?> ₽</p>
                </div>
            </a>
        <?php endforeach; ?>

    </div>
</div>

<script>
// === Отмена примерки (оставил как было) ===
document.getElementById('cancel-fitting-btn').addEventListener('click', () => {
    openModal({
        title: "Отмена примерки",
        message: "Вы действительно хотите отменить примерку?",
        buttons: [
            {
                text: "Да",
                class: "modal-confirm-btn",
                onClick: async () => {
                    const response = await fetch("<?= admin_url('admin-ajax.php'); ?>", {
                        method: "POST",
                        headers: {"Content-Type": "application/x-www-form-urlencoded"},
                        body: new URLSearchParams({
                            action: "cancel_fitting_admin",
                            fitting_id: "<?= $fitting_id; ?>",
                            nonce: "<?= wp_create_nonce('cancel_fitting_nonce'); ?>"
                        })
                    });

                    const data = await response.json();
                    if (data.success) {
                        document.getElementById('fitting-status').textContent = "Отменена";
                        closeModal();
                        openModal({
                            title: "Готово",
                            message: "Примерка отменена",
                            buttons: [{ text: "Ок", class: "modal-confirm-btn", onClick: () => closeModal() }]
                        });
                    }
                }
            },
            { text: "Нет", class: "modal-cancel-btn", onClick: () => closeModal() }
        ]
    });
});

// === Назначение/переназначение сотрудника ===
document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('assign-employee-btn');
    const select = document.getElementById('employee-select');

    btn.addEventListener('click', function() {
        const employeeId = select.value;
        const fittingId = <?= $fitting->fitting_id ?>;

        fetch("<?= admin_url('admin-ajax.php') ?>", {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'assign_employee',
                fitting_id: fittingId,
                employee_id: employeeId
            })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success){
                btn.textContent = employeeId ? 'Переназначить сотрудника' : 'Назначить сотрудника';
                alert('Сотрудник успешно назначен!');
            } else {
                alert('Ошибка: ' + (data.message || 'неизвестная'));
            }
        })
        .catch(err => {
            console.error(err);
            alert('Ошибка запроса');
        });
    });
});

// === Поповер для комментария ===
// === Поповер для комментария ===
document.querySelectorAll('.comment-preview').forEach(span => {
    const btn = span.nextElementSibling; // кнопка "Показать полностью"

    if (span.scrollHeight > span.clientHeight) {
        btn.style.display = 'inline';
    }

    btn.addEventListener('click', () => {
        const fullText = span.dataset.full;

        // Скрываем превью и кнопку
        span.style.display = 'none';
        btn.style.display = 'none';

        // Находим родителя info-row
        const infoRow = span.closest('.info-row');

        // Создаем поповер
        const popover = document.createElement('div');
        popover.className = 'comment-popover';
        popover.textContent = fullText;

        // Позиционируем относительно info-row
        infoRow.style.position = 'relative';
        popover.style.position = 'absolute';
        popover.style.top = '100%';
        popover.style.left = '0';

        infoRow.appendChild(popover);

        // Закрытие при клике вне поповера
        const closePopover = (e) => {
            if (!popover.contains(e.target) && e.target !== btn) {
                popover.remove();
                span.style.display = '-webkit-box';
                btn.style.display = 'inline';
                document.removeEventListener('click', closePopover);
            }
        };
        document.addEventListener('click', closePopover);
    });
});





</script>
