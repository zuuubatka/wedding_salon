<link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/style/favorite.css">
<link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/style/fitting-client.css">
<!-- CSS Flatpickr -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<!-- JS Flatpickr -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<?php
/*
Template Name: Примерочная-клиент
*/
get_header();
global $wpdb;

// ===== Получаем client_id и guest_id из куки =====
$client_id = isset($_COOKIE['client_id']) ? intval($_COOKIE['client_id']) : null;
$guest_id  = isset($_COOKIE['guest_id'])  ? sanitize_text_field($_COOKIE['guest_id']) : null;

// ===== Получаем поисковый запрос =====
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// ===== Получаем fitting_room_id для текущего пользователя =====
$fitting_room_id = null;

if ($client_id) {
    $fitting_room_id = $wpdb->get_var($wpdb->prepare(
        "SELECT fitting_room_id FROM FittingRoom WHERE client_id=%d ORDER BY fitting_room_id DESC LIMIT 1",
        $client_id
    ));
} elseif ($guest_id) {
    $fitting_room_id = $wpdb->get_var($wpdb->prepare(
        "SELECT fitting_room_id FROM FittingRoom WHERE client_id IS NULL AND guest_id=%s ORDER BY fitting_room_id DESC LIMIT 1",
        $guest_id
    ));
}

// ===== Получаем товары из примерочной =====
$products = [];

if ($fitting_room_id) {
    $cart_items = $wpdb->get_results($wpdb->prepare(
        "SELECT ci.product_size_id, ps.product_id
         FROM CartItem ci
         INNER JOIN ProductSize ps ON ci.product_size_id = ps.product_size_id
         WHERE ci.fitting_room_id=%d",
        $fitting_room_id
    ));

    $product_ids = array_unique(array_map(function($item) { return $item->product_id; }, $cart_items));

    if (!empty($product_ids)) {
        $placeholders = implode(',', array_fill(0, count($product_ids), '%d'));
        $query = "SELECT * FROM Product WHERE product_id IN ($placeholders) ORDER BY product_id DESC";
        $products = $wpdb->get_results($wpdb->prepare($query, ...$product_ids));
    }
}

// ===== Фильтруем по поиску =====
if ($search_query && !empty($products)) {
    $products = array_filter($products, function ($p) use ($search_query) {
        return stripos($p->product_name, $search_query) !== false;
    });
}
?>

<!-- Поиск по примерочной -->
<form method="GET" class="favorite-search">
    <input 
        type="text" 
        name="search" 
        placeholder="Поиск по примерочной..." 
        value="<?php echo esc_attr($search_query); ?>"
        class="search-input"
    >
    <button type="submit" class="btn-apply">Найти</button>
</form>

<!-- Список товаров -->
<main class="product-list" style="width:100%; grid-template-columns: repeat(4, 1fr); gap:20px;">
    <?php if (!empty($products)): ?>
        <?php foreach ($products as $product): ?>
            <?php
            
            $main_photo = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM ProductPhoto WHERE product_id = %d ORDER BY display_order ASC LIMIT 1",
                $product->product_id
            ));
            // Получаем размеры этого товара в примерочной
            $sizes_in_fitting = $wpdb->get_col($wpdb->prepare(
                "SELECT s.size_value
                FROM CartItem ci
                INNER JOIN ProductSize ps ON ci.product_size_id = ps.product_size_id
                INNER JOIN Size s ON ps.size_id = s.size_id
                WHERE ci.fitting_room_id=%d AND ps.product_id=%d",
                $fitting_room_id, $product->product_id
            ));

            $size_display = !empty($sizes_in_fitting) ? implode(', ', $sizes_in_fitting) : 'Не указан';

            
            ?>
            <div class="product-card">
                <a href="<?php echo site_url('/card/?product_id=' . $product->product_id); ?>">
                    <?php if ($main_photo): ?>
                        <img src="<?php echo get_template_directory_uri() . '/' . $main_photo->photo_url; ?>" alt="">
                    <?php else: ?>
                        <img src="<?php echo get_template_directory_uri(); ?>/images/no-image.jpg" alt="Нет изображения">
                    <?php endif; ?>
                    <h4><?php echo esc_html($product->product_name); ?></h4>
                    <p>Цена: <?php echo (int)$product->price; ?> Br</p>
                    
                    <p>Размер: <?php echo esc_html($size_display); ?></p>
                </a>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</main>

<?php
// Проверяем, авторизован ли клиент
$is_logged_in = !empty($client_id);
$client_data = null;

if ($is_logged_in) {
    $client_data = $wpdb->get_row($wpdb->prepare(
        "SELECT first_name, phone, email FROM Client WHERE client_id=%d",
        $client_id
    ));
}
?>




<section class="fitting-request">
    <h2>Оформить заявку на примерку</h2>
    <form id="fittingForm">
        <?php if (!$is_logged_in): ?>
            <div class="form-group">
                <label for="clientName">Ваше имя</label>
                <input type="text" id="clientName" name="clientName" required placeholder="Введите ваше имя">
            </div>
            <div class="form-group">
                <label for="clientPhone">Телефон</label>
                <input type="tel" id="clientPhone" name="clientPhone" required placeholder="+7 (___) ___-__-__">
            </div>
            <div class="form-group">
                <label for="clientEmail">Email</label>
                <input type="email" id="clientEmail" name="clientEmail" required placeholder="example@mail.com">
            </div>
        <?php else: ?>
            <div class="form-group">
                <label>Имя</label>
                <input type="text" value="<?php echo esc_attr($client_data->first_name); ?>" name="clientName" required>
            </div>
            <div class="form-group">
                <label>Телефон</label>
                <input type="tel" value="<?php echo esc_attr($client_data->phone); ?>" name="clientPhone" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" value="<?php echo esc_attr($client_data->email); ?>" disabled>
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label for="preferredDate">Дата примерки</label>
            <input type="text" id="preferredDate" name="preferredDate" placeholder="Выберите дату" required>
        </div>

        <div class="form-group">
            <label for="preferredTime">Время примерки</label>
            <select id="preferredTime" name="preferredTime" required>
                <option value="">Сначала выберите дату</option>
            </select>
        </div>


        <div class="form-group">
            <label for="comments">Комментарии к примерке</label>
            <textarea id="comments" name="comments" placeholder="Дополнительно..." rows="3"></textarea>
        </div>

        <button type="submit" class="btn-submit">Отправить заявку</button>
    </form>
    <div id="fittingFormMessage" class="form-message"></div>
</section>

<?php get_template_part('modal'); ?>

<style>
/*Стрелочка В CSS не переносить!!!!*/
#preferredTime {
    background: url('<?php echo get_template_directory_uri(); ?>/images/combobox.svg') no-repeat right 10px top 60%;
    background-size: 16px 16px;
}
</style>

<?php
// Получаем даты с хотя бы одним свободным временем
$available_dates = $wpdb->get_col("
    SELECT DISTINCT fitting_date
    FROM FittingDateTime
    WHERE TRIM(datatime_status) = 'Свободно'
      AND fitting_date >= CURDATE()
    ORDER BY fitting_date ASC
");

// Превращаем массив в JS-строки YYYY-MM-DD
$available_dates_js = json_encode(array_map(function($d){ return $d; }, $available_dates));
?>

<script>

// 🔥 Функция безопасного получения локальной даты
function toLocalYMD(dateObj) {
    return dateObj.getFullYear() + "-" +
        String(dateObj.getMonth() + 1).padStart(2, "0") + "-" +
        String(dateObj.getDate()).padStart(2, "0");
}

document.getElementById('fittingForm').addEventListener('submit', function(e) {
    e.preventDefault();

        // 🔥 Проверяем пустую примерочную
    const isEmpty = <?php echo empty($products) ? 'true' : 'false'; ?>;

    if (isEmpty) {
        openModal({
            title: "Примерочная пуста",
            message: "Добавьте товары, чтобы оформить заявку.",
            buttons: []
        });
        return; // ⛔ стоп — не отправляем форму
    }


    const formData = new FormData(this);
    const payload = {
        action: 'submit_fitting_request',
        clientName: formData.get('clientName'),
        clientPhone: formData.get('clientPhone'),
        clientEmail: formData.get('clientEmail'),
        preferredDate: formData.get('preferredDate'),
        preferredTime: formData.get('preferredTime'),
        comments: formData.get('comments')
    };

    console.log('Отправка данных на сервер:', payload); // 🔥 лог формы


    fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
        method: 'POST',
        body: new URLSearchParams(payload)
    })
    .then(response => response.json())
    .then(data => {
        console.log('Ответ сервера:', data);

        if (data.logs) {
            console.log('Логи сервера:', data.logs.join("\n"));
        }

        // 🔥 ПЕРЕХВАТ СУЩЕСТВУЮЩЕЙ ЗАЯВКИ
        if (data.type === 'already_exists') {

            openModal({
                title: "У вас уже есть активная заявка",
                message: "Заявку можно отменить по телефону +375-29-450-25-25 или в личном кабинете (если она не подтверждена)",
                buttons: []
            });

            return;
        }

        document.getElementById('fittingFormMessage').innerText = data.message;

        if (data.success) {
            document.getElementById('fittingForm').reset();
        }
    })


    .catch(error => console.error('Ошибка запроса:', error)); // 🔥 лог ошибок fetch
});



document.addEventListener('DOMContentLoaded', function () {
    const availableDates = <?php echo $available_dates_js; ?>;
    const timeSelect = document.getElementById('preferredTime');

    flatpickr("#preferredDate", {
        dateFormat: "Y-m-d",

        // 🔥 недоступные даты становятся disabled
        disable: [
            function(date) {
                const d = toLocalYMD(date); // ✔ ЛОКАЛЬНАЯ дата
                return !availableDates.includes(d);
            }
        ],

        // 🔥 Понедельник — первый день недели
        locale: {
            firstDayOfWeek: 1,
            weekdays: {
                shorthand: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
                longhand: ['Воскресенье', 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота'],
            },
            months: {
                shorthand: ['Янв','Фев','Мар','Апр','Май','Июн','Июл','Авг','Сен','Окт','Ноя','Дек'],
                longhand: ['Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'],
            }
        },

        // 🔥 Создание ячеек, добавляем наши классы
        onDayCreate: function(dObj, dStr, fp, dayElem) {
            const date = toLocalYMD(dayElem.dateObj); // ✔ ЛОКАЛЬНАЯ дата

            if (availableDates.includes(date)) {
                dayElem.classList.add("available-date");
            } else {
                dayElem.classList.add("disabled-date");
            }
        },

        // 🔥 Загружаем свободные времена после выбора даты
        onChange: function(selectedDates, dateStr) {
            fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
                method: 'POST',
                body: new URLSearchParams({
                    action: 'get_available_times',
                    date: dateStr
                })
            })
            .then(res => res.json())
            .then(times => {
                timeSelect.innerHTML = '';

                if (times.length === 0) {
                    timeSelect.innerHTML = '<option value="">Нет свободных времен</option>';
                } else {
                    timeSelect.innerHTML = '<option value="">Выберите время</option>';

                    times.forEach(timeStr => {
                        const option = document.createElement('option');
                        option.value = timeStr;
                        option.textContent = timeStr.substring(0,5); // делаем 14:00
                        timeSelect.appendChild(option);
                    });
                }
            });
        },

        // 🔥 не показывать лишние дни следующего/предыдущего месяца
        showDaysInNextAndPreviousMonths: false
    });
});

</script>



<?php get_footer(); ?>
