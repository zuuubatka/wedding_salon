<?php
/*
Template Name: Расписание примерок
*/
include get_template_directory() . '/admin-header.php';
global $wpdb;
?>
<link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/style/admin-datatime.css">
<?php
// Получаем все слоты
$slots = $wpdb->get_results("
    SELECT fd.fitting_datetime_id, fd.fitting_date, fd.fitting_time, fd.room_number,
           fd.datatime_status
    FROM FittingDateTime fd
    ORDER BY fd.fitting_date ASC, fd.fitting_time ASC, fd.room_number ASC
");
?>



<div class="container">
    <h1>Расписание примерок</h1>

    <!-- Добавление и удаление -->
    <div class="addanddelete">

        <!-- Форма добавления -->
        <div class="add-slot">
            <h3>Добавить новое время</h3>
            <form id="add-slot-form">
                <label>
                    Дата:
                    <input type="date" name="date_value" id="date_value" required>
                </label>
                <label>
                    Время:
                    <input type="time" name="time_value" id="time_value" required>
                </label>
                <label>
                    Кол-во комнат:
                    <input type="number" name="num_rooms" id="num_rooms" min="1" max="20" value="5" required>
                </label>
                <button type="submit" class="btn-book">Добавить слоты</button>
            </form>
        </div>

        <!-- Кнопка удаления -->
        <div style="width:25%; text-align:right; padding-top:20px;">
            <button id="delete-selected">Удалить выбранные</button>
        </div>

    </div>

    <!-- Таблица -->
    <table style="width:100%; border-collapse:collapse; text-align:center;">
        <thead>
            <tr style="background:#FAD4DA;">
                <th>✓</th>
                <th>Дата</th>
                <th>Время</th>
                <th>Комната</th>
                <th>Статус</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($slots as $slot): ?>
            <tr style="background: <?php echo ($slot->datatime_status === 'Забронировано' ? '#FDE8EC' : '#FFFFFF'); ?>;">
                <td>
                    <input type="checkbox" class="row-check" value="<?php echo $slot->fitting_datetime_id; ?>">
                </td>
                <td><?php echo esc_html($slot->fitting_date); ?></td>
                <td><?php echo esc_html($slot->fitting_time); ?></td>
                <td><?php echo esc_html($slot->room_number); ?></td>
                <td><?php echo esc_html($slot->datatime_status); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<!-- Модальное окно предупреждения -->
<div id="warning-modal" class="modal">
    <div class="modal-content">
        <span class="modal-close">&times;</span>
        <p id="warning-text"></p>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {

   

    // ============= AJAX добавление =============
    const addForm = document.getElementById('add-slot-form');

    addForm.addEventListener('submit', function(e){
        e.preventDefault();

        const data = new FormData();
        data.append('action', 'add_fitting_slots');
        data.append('date_value', document.getElementById('date_value').value);
        data.append('time_value', document.getElementById('time_value').value + ':00');
        data.append('num_rooms', document.getElementById('num_rooms').value);

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: data,
        })
        .then(r => r.json())
        .then(r => {
            if(r.success){
                updateTable(); // обновляем таблицу
            } else {
                showModal('Ошибка: ' + (r.data || 'неизвестно'));
            }
        });
    });

    // ============= AJAX удаление =============
    const deleteBtn = document.getElementById("delete-selected");

    deleteBtn.addEventListener("click", function() {
        const ids = [...document.querySelectorAll(".row-check:checked")].map(el => el.value);

        if (ids.length === 0) {
            showModal("Выберите хотя бы один слот!");
            return;
        }

        // Проверяем, есть ли среди выбранных слотов "Забронировано"
        let bookedSelected = false;
        [...document.querySelectorAll(".row-check:checked")].forEach(el => {
            const row = el.closest("tr");
            if (row && row.cells[4].innerText.trim() === "Забронировано") {
                bookedSelected = true;
            }
        });

        if (bookedSelected) {
            showModal("Нельзя удалить забронированные слоты!");
            return;
        }

        // Удаление через AJAX
        const data = new FormData();
        data.append("action", "delete_fitting_slot");
        data.append("ids", JSON.stringify(ids));

        fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
            method: "POST",
            body: data
        })
        .then(r => r.json())
        .then(r => {
            if (r.success) {
                updateTable();
            } else {
                showModal('Ошибка удаления: ' + (r.data || 'неизвестно'));
            }
        });
    });

    // ============= Функция показа модалки ============
    function showModal(text) {
        const modal = document.getElementById("warning-modal");
        const modalText = document.getElementById("warning-text");
        modalText.textContent = text;
        modal.style.display = "block";

        modal.querySelector(".modal-close").onclick = () => modal.style.display = "none";

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        };
    }

    // ============= Функция обновления таблицы ============
    function updateTable() {
        fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=get_fitting_slots')
            .then(r => r.json())
            .then(r => {
                if (r.success) {
                    document.querySelector("tbody").innerHTML = r.data.html;
                }
            });
    }

});


</script>


<?php
get_footer();
?>
