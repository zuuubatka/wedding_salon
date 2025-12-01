<?php
/*
Template Name: Все примерки
*/

include get_template_directory() . '/admin-header.php';
global $wpdb;

$today = date('Y-m-d');

// Функция получения примерок по статусу
function get_fittings_by_status($date, $statuses) {
    global $wpdb;

    if (empty($statuses)) return [];

    $placeholders = implode(',', array_fill(0, count($statuses), '%s'));
    $params = [...$statuses, $date];

    $sql = "
        SELECT 
            f.fitting_id,
            fr.fitting_room_id,
            c.first_name AS client_name,
            c.phone AS client_phone,
            fdt.fitting_date,
            fdt.fitting_time,
            f.fitting_status,
            (SELECT COUNT(*) 
             FROM CartItem ci 
             WHERE ci.fitting_room_id = fr.fitting_room_id) AS products_count
        FROM Fitting f
        JOIN FittingRoom fr ON f.fitting_room_id = fr.fitting_room_id
        LEFT JOIN Client c ON fr.client_id = c.client_id
        JOIN FittingDateTime fdt ON f.fitting_datetime_id = fdt.fitting_datetime_id
        WHERE f.fitting_status IN ($placeholders)
          AND fdt.fitting_date = %s
        ORDER BY fdt.fitting_time ASC
    ";

    return $wpdb->get_results($wpdb->prepare($sql, ...$params));
}

// Получаем примерки на сегодня
$active_fittings = get_fittings_by_status($today, ['Подтверждена']);
$finished_fittings = get_fittings_by_status($today, ['Отменена', 'Завершена']);
?>



<link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/style/admin-all-fittings.css">
<!-- CSS Flatpickr -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<!-- JS Flatpickr -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>



<div class="page-all-fittings">

    <h1 class="page-title">Все примерки</h1>

    <!-- Активные примерки -->
    <section class="fittings-block active-fittings">
    <div class="block-header-wrapper">
        <div class="block-header">
            <h2>Активные примерки</h2>
            <div class="fittings-datepicker-wrapper">
                <input type="text" id="active-date" class="fittings-datepicker" value="<?php echo date('d.m.Y'); ?>" placeholder="Выберите дату">
                <img src="<?php echo get_template_directory_uri(); ?>/images/calendar.svg" alt="Календарь" class="calendar-icon">
            </div>
        </div>
    </div>

    <div id="active-list" class="fittings-grid">
        <?php render_fittings_list($active_fittings); ?>
    </div>
</section>

<section class="fittings-block finished-fittings">
    <div class="block-header-wrapper">
        <div class="block-header">
            <h2>Завершенные примерки</h2>
            <input type="text" id="finished-date" class="fittings-datepicker" value="<?php echo $today; ?>">
        </div>
    </div>

    <div id="finished-list" class="fittings-grid">
        <?php render_fittings_list($finished_fittings); ?>
    </div>
</section>


</div>


<!-- JS для AJAX обновления -->
<script>

document.addEventListener('DOMContentLoaded', function() {

    // === элементы ===
    const activeDate = document.getElementById('active-date');
    const finishedDate = document.getElementById('finished-date');

    // === flatpickr ===
    flatpickr("#active-date", {
        dateFormat: "d.m.Y",
        defaultDate: "<?php echo date('d.m.Y'); ?>",
        locale: {
            firstDayOfWeek: 1,
            weekdays: {
                shorthand: ['Вс','Пн','Вт','Ср','Чт','Пт','Сб'],
                longhand: ['Воскресенье','Понедельник','Вторник','Среда','Четверг','Пятница','Суббота']
            },
            months: {
                shorthand: ['Янв','Фев','Мар','Апр','Май','Июн','Июл','Авг','Сен','Окт','Ноя','Дек'],
                longhand: ['Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь']
            }
        }
    });

    flatpickr("#finished-date", {
        dateFormat: "d.m.Y"
    });

    // === загрузка примерок ===
    activeDate.addEventListener('change', () => loadFittings('active', activeDate.value));
    finishedDate.addEventListener('change', () => loadFittings('finished', finishedDate.value));

    function loadFittings(type, date) {
        const listId = type === 'active' ? 'active-list' : 'finished-list';
        const listContainer = document.getElementById(listId);

        fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=get_fittings&type=' + type + '&date=' + date)
            .then(response => response.text())
            .then(html => listContainer.innerHTML = html);
    }

    // === переход в карточку примерки ===
    document.addEventListener('click', function(e) {
        const card = e.target.closest('.fitting-card');
        if (card && !e.target.closest('button')) {
            window.location.href = card.dataset.link;
        }
    });

});



</script>
