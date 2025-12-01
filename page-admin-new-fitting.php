<?php
/*
Template Name: Админ Новые заявки
*/
include get_template_directory() . '/admin-header.php';
global $wpdb;

// Загружаем новые заявки
$fittings = $wpdb->get_results("
    SELECT 
        f.fitting_id,
        fr.fitting_room_id,
        c.first_name AS client_name,
        c.phone AS client_phone,
        dt.fitting_date,
        dt.fitting_time,
        dt.room_number,
        (SELECT COUNT(*) 
         FROM CartItem ci 
         WHERE ci.fitting_room_id = fr.fitting_room_id) AS products_count
    FROM Fitting f
    LEFT JOIN FittingRoom fr ON fr.fitting_room_id = f.fitting_room_id
    LEFT JOIN Client c ON c.client_id = fr.client_id
    LEFT JOIN FittingDateTime dt ON dt.fitting_datetime_id = f.fitting_datetime_id
    WHERE f.fitting_status = 'Новая'
");
?>
<link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/style/admin-new-fitting.css">
<div class="admin-page">
    <h1 class="page-title">Новые заявки на примерки</h1>

    <div id="fittings-list" class="fittings-grid">

        <?php if (!empty($fittings)) : ?>
            <?php foreach ($fittings as $fit) : ?>
                <div class="fitting-card" 
                    data-id="<?php echo $fit->fitting_id; ?>"
                    data-link="/admin/this-fitting/?id=<?php echo $fit->fitting_id; ?>">
                    <div class="fitting-left">
                        <div class="fitting-header">
                            <span class="fitting-status new">Новая</span>
                            <span class="fitting-id">ID: <?= esc_html($fit->fitting_id); ?></span>
                        </div>

                        <div class="fitting-body">
                            <p><strong>Клиент:</strong> <?= esc_html($fit->client_name); ?></p>
                            <p><strong>Телефон:</strong> <?= esc_html($fit->client_phone); ?></p>
                            <p><strong>Дата:</strong> <?= esc_html($fit->fitting_date); ?></p>
                            <p><strong>Время:</strong> <?= esc_html($fit->fitting_time); ?></p>
                            <p><strong>Комната:</strong> №<?= esc_html($fit->room_number); ?></p>
                            <p><strong>Товаров:</strong> <?= esc_html($fit->products_count); ?></p>
                        </div>
                    </div>

                    <div class="fitting-right">
                        <div class="fitting-actions">
                            <button class="btn btn-approve" data-id="<?= esc_attr($fit->fitting_id); ?>">Подтвердить</button>
                            <button class="btn btn-cancel" data-id="<?= esc_attr($fit->fitting_id); ?>">Отменить</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php else : ?>
            <p class="no-items">Нет новых заявок</p>
        <?php endif; ?>

    </div>
</div>

<!-- Модальное окно -->
<div id="cancelModal" class="modal">
    <div class="modal-content">
        <p>Вы действительно хотите отменить заявку?</p>
        <div class="modal-actions">
            <button id="confirmCancelBtn" class="btn btn-cancel">Отменить</button>
            <button id="closeModalBtn" class="btn btn-close">Закрыть</button>
        </div>
    </div>
</div>

<script>
(function(){

    let cancelId = null;

    // Открытие модалки и кнопки
    document.addEventListener('click', function(e){

        // --- Переход при клике на карточку ---
        const card = e.target.closest('.fitting-card');

        if (card && !e.target.closest('.btn')) {
            const url = card.dataset.link;
            window.location.href = url;
            return;
        }

        const approve = e.target.closest('.btn-approve');
        const cancel = e.target.closest('.btn-cancel');

        // ----------- ПОДТВЕРДИТЬ ----------- //
        if (approve) {
            const id = approve.dataset.id;

            fetch('<?= admin_url("admin-ajax.php"); ?>', {
                method: "POST",
                headers: {"Content-Type": "application/x-www-form-urlencoded"},
                body:
                    "action=approve_fitting" +
                    "&fitting_id=" + id +
                    "&nonce=<?= wp_create_nonce("approve_fitting_nonce"); ?>"
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    reloadFittings();
                }
            });
        }

        // ----------- ОТМЕНА: ОТКРЫТЬ МОДАЛКУ ----------- //
        if (cancel) {
            cancelId = cancel.dataset.id;
            document.getElementById("cancelModal").classList.add("open");
        }
    });

    // Закрыть модалку
    document.getElementById("closeModalBtn").onclick = function(){
        document.getElementById("cancelModal").classList.remove("open");
        cancelId = null;
    };

    // Подтверждение отмены
    document.getElementById("confirmCancelBtn").onclick = function(){

        fetch('<?= admin_url("admin-ajax.php"); ?>', {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded"},
            body:
                "action=cancel_fitting_admin" +
                "&fitting_id=" + cancelId +
                "&nonce=<?= wp_create_nonce("cancel_fitting_nonce"); ?>"
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById("cancelModal").classList.remove("open");
                reloadFittings();
            }
        });
    };

    // ----------- ФУНКЦИЯ ПЕРЕЗАГРУЗКИ СПИСКА ----------- //
    function reloadFittings(){
        fetch(window.location.href, { method: "GET" })
            .then(r => r.text())
            .then(html => {
                let parser = new DOMParser();
                let doc = parser.parseFromString(html, "text/html");

                let newList = doc.querySelector("#fittings-list").innerHTML;
                document.querySelector("#fittings-list").innerHTML = newList;
            });
    }

})();
</script>
