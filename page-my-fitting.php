<?php
/*
Template Name: My Fitting
*/

global $wpdb;

// Получаем client_id и guest_id из куки
$client_id = isset($_COOKIE['client_id']) ? intval($_COOKIE['client_id']) : null;
$guest_id  = isset($_COOKIE['guest_id']) ? intval($_COOKIE['guest_id']) : null;

get_header();
?>

<main class="client-main">
    <div class="container">
        <h1>Моя примерка</h1>

        <div id="fittingContainer">
            <?php
            // Выводим карточку примерки сразу при загрузке страницы
            $fitting = null;
            $no_fitting = true;

            if ($client_id || $guest_id) {
                $where = "";
                $params = [];
                if ($client_id) {
                    $where = "FR.client_id = %d";
                    $params[] = $client_id;
                } else {
                    $where = "FR.guest_id = %d";
                    $params[] = $guest_id;
                }

                $query = $wpdb->prepare("
                    SELECT 
                        F.*,
                        FD.fitting_date,
                        FD.fitting_time,
                        FD.room_number
                    FROM Fitting F
                    JOIN FittingRoom FR ON FR.fitting_room_id = F.fitting_room_id
                    JOIN FittingDateTime FD ON FD.fitting_datetime_id = F.fitting_datetime_id
                    WHERE $where
                      AND F.fitting_status IN ('Новая', 'Подтверждена')
                    ORDER BY F.fitting_id DESC
                    LIMIT 1
                ", $params);

                $fitting = $wpdb->get_row($query);

                if ($fitting) {
                    $no_fitting = false;
                    $products = $wpdb->get_results($wpdb->prepare("
                        SELECT P.product_name, S.size_value
                        FROM CartItem CI
                        JOIN ProductSize PS ON PS.product_size_id = CI.product_size_id
                        JOIN Product P ON P.product_id = PS.product_id
                        JOIN Size S ON S.size_id = PS.size_id
                        WHERE CI.fitting_room_id = %d
                    ", $fitting->fitting_room_id));

                    ?>
                    <div class="fitting-card" data-id="<?php echo $fitting->fitting_id; ?>">
                        <p><strong>Дата:</strong> <?php echo esc_html($fitting->fitting_date); ?></p>
                        <p><strong>Время:</strong> <?php echo esc_html(substr($fitting->fitting_time, 0, 5)); ?></p>
                        <p><strong>Комната:</strong> <?php echo esc_html($fitting->room_number); ?></p>
                        <p><strong>Статус:</strong> <?php echo esc_html($fitting->fitting_status); ?></p>

                        <h3>Товары на примерку:</h3>
                        <ul>
                            <?php foreach ($products as $p): ?>
                                <li><?php echo esc_html($p->product_name . ' — ' . strtoupper($p->size_value)); ?></li>
                            <?php endforeach; ?>
                        </ul>

                        <?php if ($fitting->fitting_status === 'Новая'): ?>
                            <button class="cancelFittingBtn" data-id="<?php echo $fitting->fitting_id; ?>">Отменить примерку</button>
                        <?php endif; ?>
                    </div>
                    <?php
                } else {
                    echo '<p>У вас нет активной примерки.</p>';
                }
            } else {
                echo '<p>У вас нет активной примерки.</p>';
            }
            ?>
        </div>
    </div>
</main>

<!-- Модальное окно подтверждения -->
<div id="cancelModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
     background: rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:1000;">
    <div style="background:white; padding:20px; border-radius:8px; max-width:400px; width:90%;">
        <p>Вы уверены, что хотите отменить примерку?</p>
        <button id="confirmCancel">Да, отменить</button>
        <button id="closeModal">Отмена</button>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {

    const modal = document.getElementById("cancelModal");
    const confirmBtn = document.getElementById("confirmCancel");
    const closeBtn = document.getElementById("closeModal");
    let currentFittingId = null;

    function attachCancelHandlers() {
        const buttons = document.querySelectorAll(".cancelFittingBtn");
        buttons.forEach(btn => {
            btn.addEventListener("click", () => {
                currentFittingId = btn.dataset.id;
                modal.style.display = "flex";
            });
        });
    }

    closeBtn.addEventListener("click", () => modal.style.display = "none");

    confirmBtn.addEventListener("click", () => {
        if (!currentFittingId) return;

        fetch("<?php echo admin_url('admin-ajax.php'); ?>", {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded"},
            body: "action=cancel_fitting&fitting_id=" + currentFittingId
        })
        .then(r => r.text())
        .then(response => {
            modal.style.display = "none";
            if (response === "OK") {
                if (typeof openModal === "function") {
                    openModal("Примерка отменена", "Вы успешно отменили примерку.");
                }

                // Перезагружаем карточку примерки через AJAX
                refreshFitting();
            } else {
                alert("Ошибка отмены! Подробнее в консоли.");
            }
        })
        .catch(err => console.error("AJAX error:", err));
    });

    function refreshFitting() {
        const container = document.getElementById("fittingContainer");
        fetch("<?php echo admin_url('admin-ajax.php'); ?>", {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded"},
            body: "action=get_user_fitting&client_id=<?php echo $client_id ?: 0; ?>&guest_id=<?php echo $guest_id ?: 0; ?>"
        })
        .then(r => r.json())
        .then(data => {
            container.innerHTML = ""; // очищаем контейнер
            if (!data.success) {
                container.innerHTML = "<p>У вас нет активной примерки.</p>";
                return;
            }

            const fitting = data.fitting;
            const products = data.products;

            let html = `
                <div class="fitting-card" data-id="${fitting.fitting_id}">
                    <p><strong>Дата:</strong> ${fitting.fitting_date}</p>
                    <p><strong>Время:</strong> ${fitting.fitting_time.substring(0,5)}</p>
                    <p><strong>Комната:</strong> ${fitting.room_number}</p>
                    <p><strong>Статус:</strong> ${fitting.fitting_status}</p>
                    <h3>Товары на примерку:</h3>
                    <ul>
                        ${products.map(p => `<li>${p.product_name} — ${p.size_value.toUpperCase()}</li>`).join('')}
                    </ul>
                    ${fitting.fitting_status === 'Новая' ? `<button class="cancelFittingBtn" data-id="${fitting.fitting_id}">Отменить примерку</button>` : ''}
                </div>
            `;
            container.innerHTML = html;
            attachCancelHandlers(); // снова вешаем обработчики на кнопку отмены
        });
    }

    attachCancelHandlers();
});
</script>

<?php
// Подключаем универсальное модальное окно
include get_template_directory() . '/modal.php';
?>
<?php get_footer(); ?>
