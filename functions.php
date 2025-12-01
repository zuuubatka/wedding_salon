<?php
/**
 * Wedding_salon functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Wedding_salon
 */

if ( ! defined( '_S_VERSION' ) ) {
	// Replace the version number of the theme on each release.
	define( '_S_VERSION', '1.0.0' );
}

/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook. The init hook is too late for some features, such
 * as indicating support for post thumbnails.
 */
function wedding_salon_setup() {
	/*
		* Make theme available for translation.
		* Translations can be filed in the /languages/ directory.
		* If you're building a theme based on Wedding_salon, use a find and replace
		* to change 'wedding_salon' to the name of your theme in all the template files.
		*/
	load_theme_textdomain( 'wedding_salon', get_template_directory() . '/languages' );

	// Add default posts and comments RSS feed links to head.
	add_theme_support( 'automatic-feed-links' );

	/*
		* Let WordPress manage the document title.
		* By adding theme support, we declare that this theme does not use a
		* hard-coded <title> tag in the document head, and expect WordPress to
		* provide it for us.
		*/
	add_theme_support( 'title-tag' );

	/*
		* Enable support for Post Thumbnails on posts and pages.
		*
		* @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		*/
	add_theme_support( 'post-thumbnails' );

	// This theme uses wp_nav_menu() in one location.
	register_nav_menus(
		array(
			'menu-1' => esc_html__( 'Primary', 'wedding_salon' ),
		)
	);

	/*
		* Switch default core markup for search form, comment form, and comments
		* to output valid HTML5.
		*/
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		)
	);

	// Set up the WordPress core custom background feature.
	add_theme_support(
		'custom-background',
		apply_filters(
			'wedding_salon_custom_background_args',
			array(
				'default-color' => 'ffffff',
				'default-image' => '',
			)
		)
	);

	// Add theme support for selective refresh for widgets.
	add_theme_support( 'customize-selective-refresh-widgets' );

	/**
	 * Add support for core custom logo.
	 *
	 * @link https://codex.wordpress.org/Theme_Logo
	 */
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 250,
			'width'       => 250,
			'flex-width'  => true,
			'flex-height' => true,
		)
	);
}
add_action( 'after_setup_theme', 'wedding_salon_setup' );

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function wedding_salon_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'wedding_salon_content_width', 640 );
}
add_action( 'after_setup_theme', 'wedding_salon_content_width', 0 );

/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */
function wedding_salon_widgets_init() {
	register_sidebar(
		array(
			'name'          => esc_html__( 'Sidebar', 'wedding_salon' ),
			'id'            => 'sidebar-1',
			'description'   => esc_html__( 'Add widgets here.', 'wedding_salon' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		)
	);
}
add_action( 'widgets_init', 'wedding_salon_widgets_init' );

/**
 * Enqueue scripts and styles.
 */
function wedding_salon_scripts() {
	wp_enqueue_style( 'wedding_salon-style', get_stylesheet_uri(), array(), _S_VERSION );
	wp_style_add_data( 'wedding_salon-style', 'rtl', 'replace' );

	wp_enqueue_script( 'wedding_salon-navigation', get_template_directory_uri() . '/js/navigation.js', array(), _S_VERSION, true );

	 // Подключаем JS для страницы карточки товара
    if (is_page_template('page-card.php')) {
        wp_enqueue_script('card-script', get_template_directory_uri() . '/js/card.js', array('jquery'), null, true);
    }


	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'wedding_salon_scripts' );

/**
 * Implement the Custom Header feature.
 */
require get_template_directory() . '/inc/custom-header.php';

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Functions which enhance the theme by hooking into WordPress.
 */
require get_template_directory() . '/inc/template-functions.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';

/**
 * Load Jetpack compatibility file.
 */
if ( defined( 'JETPACK__VERSION' ) ) {
	require get_template_directory() . '/inc/jetpack.php';
}


// Регистрируем меню для шапки (главное меню)
function wedding_salon_register_menus() {
    register_nav_menus(array(
        'main-menu' => 'Главное меню',
		'admin-menu' => 'Меню админа',
    ));
}
add_action('after_setup_theme', 'wedding_salon_register_menus');


// Отключаем верхнюю админ-панель WordPress на сайте
add_filter('show_admin_bar', '__return_false');

add_action('wp_ajax_delete_product', 'delete_product_callback');
function delete_product_callback() {
    global $wpdb;

    $product_id = intval($_POST['product_id']);

    // Сначала удаляем фото (из таблицы и физически)
    $photos = $wpdb->get_results($wpdb->prepare("SELECT photo_url FROM ProductPhoto WHERE product_id = %d", $product_id));
    foreach ($photos as $photo) {
        $file_path = get_template_directory() . '/' . $photo->photo_url;
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    $wpdb->delete('ProductPhoto', ['product_id' => $product_id]);

    // Потом сам товар
    $wpdb->delete('Product', ['product_id' => $product_id]);

    wp_die(); // важно для завершения AJAX
}


add_action('wp_ajax_delete_photo', 'ajax_delete_photo_handler');

function ajax_delete_photo_handler() {
    global $wpdb;

    check_ajax_referer('delete_photo_nonce', 'nonce');

    $photo_id = intval($_POST['photo_id']);
    $product_id = intval($_POST['product_id']);

    if (!$photo_id || !$product_id) {
        wp_send_json_error("Некорректные данные");
    }

    // Удаляем фото
    $deleted = $wpdb->delete('ProductPhoto', ['photo_id' => $photo_id], ['%d']);

    if (!$deleted) {
        wp_send_json_error("Удаление не выполнено");
    }

    // Пересчёт order
    $remaining = $wpdb->get_results($wpdb->prepare(
        "SELECT photo_id FROM ProductPhoto WHERE product_id = %d ORDER BY display_order ASC",
        $product_id
    ));

    foreach ($remaining as $i => $p) {
        $wpdb->update(
            'ProductPhoto',
            ['display_order' => $i + 1],
            ['photo_id' => $p->photo_id],
            ['%d'], ['%d']
        );
    }

    wp_send_json_success("Фото удалено");
}





function enqueue_auth_modal_assets() {
    wp_enqueue_style('auth-modal-css', get_template_directory_uri() . '/css/auth-modal.css');
    wp_enqueue_script('auth-modal-js', get_template_directory_uri() . '/js/auth-modal.js', array('jquery'), false, true);
}
add_action('wp_enqueue_scripts', 'enqueue_auth_modal_assets');

function include_auth_modal_html() {
    get_template_part('auth-modal'); // auth-modal.php
}
add_action('wp_footer', 'include_auth_modal_html');


// Генерация кода и отправка письма через wp_mail()
add_action('wp_ajax_nopriv_generate_auth_code', 'generate_auth_code');
add_action('wp_ajax_generate_auth_code', 'generate_auth_code');

function generate_auth_code() {
    $email = sanitize_email($_POST['email']);
    if (!is_email($email)) {
        wp_send_json(['success' => false, 'message' => 'Неверный email']);
    }

    $code = rand(100000, 999999);
    set_transient('auth_code_' . md5($email), $code, 5 * MINUTE_IN_SECONDS);

    // Тема и сообщение
    $subject = "Ваш код авторизации";
    $message = "Ваш код: " . $code;
    $headers = ['Content-Type: text/html; charset=UTF-8'];

    $sent = wp_mail($email, $subject, $message, $headers);

    if ($sent) {
        wp_send_json(['success' => true, 'message' => 'Код отправлен на email!']);
    } else {
        wp_send_json(['success' => false, 'message' => 'Ошибка отправки письма']);
    }
}

// Проверка кода и сохранение клиента
add_action('wp_ajax_nopriv_verify_auth_code', 'verify_auth_code');
add_action('wp_ajax_verify_auth_code', 'verify_auth_code');
function verify_auth_code() {
    global $wpdb;

    $email = sanitize_email($_POST['email']);
    $code = sanitize_text_field($_POST['code']);
    $first_name = sanitize_text_field($_POST['first_name']);
    $phone = sanitize_text_field($_POST['phone']);

    $stored = get_transient('auth_code_' . md5($email));

    if (!$stored) {
        wp_send_json(['success' => false, 'message' => 'Код просрочен или не найден']);
    }

    if ($stored != $code) {
        wp_send_json(['success' => false, 'message' => 'Неверный код']);
    }

    delete_transient('auth_code_' . md5($email));

    $table = 'Client'; // или $wpdb->prefix . 'client' если с префиксом
    $client = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE email = %s", $email));

    if ($client) {
        // обновляем имя/телефон
        $wpdb->update(
            $table,
            [
                'first_name' => $first_name,
                'phone' => $phone
            ],
            ['email' => $email]
        );
        $client_id = $client->client_id;
    } else {
        // создаем нового
        $wpdb->insert($table, [
            'first_name' => $first_name,
            'phone' => $phone,
            'email' => $email,
        ]);
        $client_id = $wpdb->insert_id;
    }

    // если был гость — переносим его записи
    if (isset($_COOKIE['guest_id'])) {
        $guest_id = sanitize_text_field($_COOKIE['guest_id']);

        // пример: Favorites и FittingRoom
        $wpdb->update('Favorites', ['client_id' => $client_id, 'guest_id' => null], ['guest_id' => $guest_id]);
        $wpdb->update('FittingRoom', ['client_id' => $client_id, 'guest_id' => null], ['guest_id' => $guest_id]);

        // очищаем гостевой cookie
        setcookie('guest_id', '', time() - 3600, '/');
    }

    // создаем cookie авторизованного клиента на 30 дней
    setcookie('client_id', $client_id, time() + 30 * DAY_IN_SECONDS, '/');
    wp_send_json(['success' => true]);
}



// Модалка выбора гостя
function enqueue_guest_choice_modal_assets() {
    wp_enqueue_style('choice-modal-css', get_template_directory_uri() . '/style/choice-modal.css', [], '1.0');
    wp_enqueue_script('choice-modal-js', get_template_directory_uri() . '/js/choice-modal.js', [], '1.0', true);
}
add_action('wp_enqueue_scripts', 'enqueue_guest_choice_modal_assets');




function enqueue_choice_modal_assets() {
    wp_enqueue_script(
        'choice-modal-js',
        get_template_directory_uri() . '/js/choice-modal.js', // путь к твоему файлу
        [], // зависимости
        '1.0',
        true // подключение в footer
    );

    wp_enqueue_style(
        'choice-modal-css',
        get_template_directory_uri() . '/style/choice-modal.css',
        [],
        '1.0'
    );
}
add_action('wp_enqueue_scripts', 'enqueue_choice_modal_assets');



// Подключаем JS и передаем ajax_url
function enqueue_favorite_script() {
    wp_enqueue_script(
        'favorite-js',
        get_template_directory_uri() . '/js/favorite.js',
        array(),
        '1.0',
        true
    );

    wp_localize_script('favorite-js', 'myAjax', [
        'ajax_url' => admin_url('admin-ajax.php')
    ]);
}
add_action('wp_enqueue_scripts', 'enqueue_favorite_script');


// ===== Добавление и удаление из избранного =====
add_action('wp_ajax_add_to_favorite', 'handle_add_to_favorite');
add_action('wp_ajax_nopriv_add_to_favorite', 'handle_add_to_favorite');

add_action('wp_ajax_remove_from_favorite', 'handle_remove_from_favorite');
add_action('wp_ajax_nopriv_remove_from_favorite', 'handle_remove_from_favorite');

function handle_add_to_favorite() {
    global $wpdb;

    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $client_id  = isset($_POST['client_id']) && $_POST['client_id'] !== '' ? intval($_POST['client_id']) : null;
    $guest_id   = isset($_POST['guest_id'])  && $_POST['guest_id'] !== ''  ? sanitize_text_field($_POST['guest_id']) : null;

    $debug = ['product_id'=>$product_id, 'client_id'=>$client_id, 'guest_id'=>$guest_id];

    if (!$product_id || (!$client_id && !$guest_id)) {
        $debug['error'] = 'Некорректные данные';
        wp_send_json($debug);
    }

    $table = 'Favorite';

    // Проверяем существующую запись
    $exists_query = "SELECT favorite_id FROM $table WHERE product_id=%d";
    $params = [$product_id];

    if ($client_id) { $exists_query .= " AND client_id=%d"; $params[] = $client_id; } else { $exists_query .= " AND client_id IS NULL"; }
    if ($guest_id)  { $exists_query .= " AND guest_id=%s"; $params[] = $guest_id; } else { $exists_query .= " AND guest_id IS NULL"; }

    $exists = $wpdb->get_var($wpdb->prepare($exists_query, ...$params));
    $debug['exists'] = $exists;

    if (!$exists) {
        $inserted = $wpdb->insert($table, [
            'product_id' => $product_id,
            'client_id'  => $client_id,
            'guest_id'   => $guest_id
        ]);

        if ($inserted) {
            $debug['inserted'] = $wpdb->insert_id;
            $debug['success'] = true;
        } else {
            $debug['error'] = $wpdb->last_error;
            $debug['success'] = false;
        }
    } else {
        $debug['success'] = true;
    }

    wp_send_json($debug);
}

function handle_remove_from_favorite() {
    global $wpdb;

    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $client_id  = isset($_POST['client_id']) && $_POST['client_id'] !== '' ? intval($_POST['client_id']) : null;
    $guest_id   = isset($_POST['guest_id'])  && $_POST['guest_id'] !== ''  ? sanitize_text_field($_POST['guest_id']) : null;

    $debug = ['product_id'=>$product_id, 'client_id'=>$client_id, 'guest_id'=>$guest_id];

    if (!$product_id || (!$client_id && !$guest_id)) {
        $debug['error'] = 'Некорректные данные';
        wp_send_json($debug);
    }

    $table = 'Favorite';

    // Формируем условия удаления
    $where = ['product_id' => $product_id];
    if ($client_id) { $where['client_id'] = $client_id; } else { $where['client_id'] = null; }
    if ($guest_id)  { $where['guest_id']  = $guest_id;  } else { $where['guest_id'] = null; }

    $deleted = $wpdb->delete($table, $where);

    if ($deleted !== false) {
        $debug['success'] = true;
        $debug['deleted'] = $deleted;
    } else {
        $debug['success'] = false;
        $debug['error'] = $wpdb->last_error;
    }

    wp_send_json($debug);
}



// AJAX: Добавление / удаление из примерочной
// ===== Добавление и удаление из примерочной =====
// ===== Примерочная: добавление / удаление =====

add_action('wp_ajax_add_to_fitting_room', 'add_to_fitting_room');
add_action('wp_ajax_nopriv_add_to_fitting_room', 'add_to_fitting_room');

function add_to_fitting_room() {
    global $wpdb;

    $client_id = isset($_COOKIE['client_id']) ? intval($_COOKIE['client_id']) : null;
    $guest_id  = isset($_COOKIE['guest_id'])  ? sanitize_text_field($_COOKIE['guest_id']) : null;

    $product_id = intval($_POST['product_id']);
    $size_id    = intval($_POST['product_size_id']); // приходит size_id

    $debug = [
        'client_id' => $client_id,
        'guest_id'  => $guest_id,
        'product_id' => $product_id,
        'size_id' => $size_id
    ];

    if (!$product_id || !$size_id) {
        $debug['error'] = 'Некорректные данные.';
        wp_send_json($debug);
    }

    // === Если нет client_id и guest_id — создаём гостя ===
    if (!$client_id && !$guest_id) {
        $guest_id = wp_generate_uuid4();
        setcookie('guest_id', $guest_id, time() + 365*DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
    }

    // === Ищем product_size_id ===
    $product_size_id = $wpdb->get_var($wpdb->prepare(
        "SELECT product_size_id FROM ProductSize 
         WHERE product_id=%d AND size_id=%d 
         LIMIT 1",
        $product_id, $size_id
    ));

    if (!$product_size_id) {
        $debug['error'] = 'Связка product_id + size_id не найдена.';
        wp_send_json($debug);
    }

    // === Ищем или создаём примерочную ===
    if ($client_id) {
        $fitting_room_id = $wpdb->get_var($wpdb->prepare(
            "SELECT fitting_room_id FROM FittingRoom 
             WHERE client_id=%d 
             ORDER BY fitting_room_id DESC LIMIT 1",
            $client_id
        ));
    } else {
        $fitting_room_id = $wpdb->get_var($wpdb->prepare(
            "SELECT fitting_room_id FROM FittingRoom 
             WHERE client_id IS NULL AND guest_id=%s 
             ORDER BY fitting_room_id DESC LIMIT 1",
            $guest_id
        ));
    }

    if (!$fitting_room_id) {
        $wpdb->insert('FittingRoom', [
            'client_id' => $client_id,
            'guest_id' => $client_id ? null : $guest_id
        ]);
        $fitting_room_id = $wpdb->insert_id;
    }

    // === Проверяем, есть ли уже этот product_size_id ===
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT cart_item_id FROM CartItem 
         WHERE fitting_room_id=%d AND product_size_id=%d",
        $fitting_room_id, $product_size_id
    ));

    if ($existing) {
        $wpdb->delete('CartItem', ['cart_item_id' => $existing]);

        $fitting_sizes = $wpdb->get_col($wpdb->prepare(
            "SELECT ps.size_id
            FROM CartItem ci
            INNER JOIN ProductSize ps ON ci.product_size_id = ps.product_size_id
            WHERE ci.fitting_room_id=%d AND ps.product_id=%d",
            $fitting_room_id, $product_id
        ));

        wp_send_json([
            'action' => 'removed',
            'product_size_id' => $product_size_id,
            'in_fitting' => $fitting_sizes
        ]);
    }


    // === Добавляем (дублей не будет) ===
    $wpdb->insert('CartItem', [
        'fitting_room_id' => $fitting_room_id,
        'product_size_id' => $product_size_id
    ]);

    // === Получаем все размеры этого товара в примерочной ===
    $fitting_sizes = $wpdb->get_col($wpdb->prepare(
        "SELECT ps.size_id
        FROM CartItem ci
        INNER JOIN ProductSize ps ON ci.product_size_id = ps.product_size_id
        WHERE ci.fitting_room_id=%d AND ps.product_id=%d",
        $fitting_room_id, $product_id
    ));

    wp_send_json([
        'action' => 'added',
        'product_size_id' => $product_size_id,
        'in_fitting' => $fitting_sizes
    ]);
}

function enqueue_fitting_room_scripts() {
    wp_enqueue_script('fitting-room-js', get_template_directory_uri() . '/js/fitting-room.js', ['jquery'], null, true);
    wp_localize_script('fitting-room-js', 'ajaxurl', admin_url('admin-ajax.php'));
}
add_action('wp_enqueue_scripts', 'enqueue_fitting_room_scripts');




// Регистрируем query_var 'category'
function register_category_query_var($vars) {
    $vars[] = 'category';
    return $vars;
}
add_filter('query_vars', 'register_category_query_var');

// Добавляем rewrite rule для /catalog/slug/
function add_category_rewrite_rule() {
    add_rewrite_rule(
        '^catalog/([^/]+)/?$',
        'index.php?pagename=catalog&category=$matches[1]',
        'top'
    );
}
add_action('init', 'add_category_rewrite_rule');


//Расписание примерок
// Добавляем обработчик для администратора
add_action('wp_ajax_add_fitting_slots', 'add_fitting_slots');
function add_fitting_slots() {
    global $wpdb;

    $date_value = sanitize_text_field($_POST['date_value'] ?? '');
    $time_value = sanitize_text_field($_POST['time_value'] ?? '');
    $num_rooms  = intval($_POST['num_rooms'] ?? 0);

    if(!$date_value || !$time_value || $num_rooms < 1) {
        wp_send_json_error('Неверные данные');
    }

    $table = 'FittingDateTime';
    $log = [];

    // 1. Находим максимальный номер комнаты для этой даты и времени
    $max_room = $wpdb->get_var($wpdb->prepare(
        "SELECT MAX(room_number) FROM $table WHERE fitting_date = %s AND fitting_time = %s",
        $date_value, $time_value
    ));
    if($max_room === null) $max_room = 0; // если еще нет записей

    // 2. Добавляем новые комнаты начиная с max_room+1
    for($i = 1; $i <= $num_rooms; $i++) {
        $room_number = $max_room + $i;

        $result = $wpdb->insert(
            $table,
            [
                'fitting_date'     => $date_value,
                'fitting_time'     => $time_value,
                'room_number'      => $room_number,
                'datatime_status'  => 'Свободно',
            ],
            ['%s','%s','%d','%s']
        );

        $log[] = "Комната $room_number: " . ($result!==false ? 'OK' : "Ошибка {$wpdb->last_error}");
    }

    wp_send_json_success(['added_rooms' => $num_rooms, 'log' => $log]);
}



add_action('wp_ajax_get_fitting_slots', 'get_fitting_slots');
function get_fitting_slots(){
    global $wpdb;

    $slots = $wpdb->get_results("
        SELECT fitting_datetime_id, fitting_date, fitting_time, room_number, datatime_status
        FROM FittingDateTime
        ORDER BY fitting_date ASC, fitting_time ASC, room_number ASC
    ");

    ob_start();
    foreach ($slots as $slot) {
        ?>
        <tr style="background: <?php echo ($slot->datatime_status === 'Забронировано' ? '#FDE8EC' : '#FFFFFF'); ?>;">
            <td><input type="checkbox" class="row-check" value="<?php echo $slot->fitting_datetime_id; ?>"></td>
            <td><?php echo esc_html($slot->fitting_date); ?></td>
            <td><?php echo esc_html($slot->fitting_time); ?></td>
            <td><?php echo esc_html($slot->room_number); ?></td>
            <td><?php echo esc_html($slot->datatime_status); ?></td>
        </tr>
        <?php
    }
    
    wp_send_json_success(['html' => ob_get_clean()]);
}


// Удаление слота — только если статус "Свободно"
add_action('wp_ajax_delete_fitting_slot', 'delete_fitting_slot');
function delete_fitting_slot() {
    global $wpdb;

    $ids = isset($_POST['ids']) ? json_decode(stripslashes($_POST['ids']), true) : [];

    if (!is_array($ids) || empty($ids)) {
        wp_send_json_error("Не переданы ID слотов.");
    }

    $errors = [];
    $deleted_any = false;

    foreach ($ids as $slot_id) {
        $slot_id = intval($slot_id);

        $status = $wpdb->get_var($wpdb->prepare("
            SELECT datatime_status
            FROM FittingDateTime
            WHERE fitting_datetime_id = %d
        ", $slot_id));

        if (!$status) {
            $errors[] = "Слот #$slot_id не найден.";
            continue;
        }

        if ($status !== 'Свободно') {
            $errors[] = "Слот #$slot_id нельзя удалить — статус '{$status}'.";
            continue;
        }

        $deleted = $wpdb->delete('FittingDateTime', [
            'fitting_datetime_id' => $slot_id
        ], ['%d']);

        if ($deleted !== false) {
            $deleted_any = true;
        } else {
            $errors[] = "Ошибка удаления слота #$slot_id.";
        }
    }

    if ($deleted_any) {
        wp_send_json_success("Удаление завершено.");
    }

    wp_send_json_error($errors);
}




// Получение свободных времен для выбранной даты
add_action('wp_ajax_get_available_times', 'get_available_times');
add_action('wp_ajax_nopriv_get_available_times', 'get_available_times');

function get_available_times() {
    global $wpdb;

    $date = sanitize_text_field($_POST['date']);

    // Берем уникальные времена
    $times = $wpdb->get_col($wpdb->prepare("
        SELECT DISTINCT fitting_time
        FROM FittingDateTime
        WHERE fitting_date = %s
          AND TRIM(datatime_status) = 'Свободно'
        ORDER BY fitting_time ASC
    ", $date));

    wp_send_json($times);
}

function client_has_active_fitting($client_id, $guest_id = null) {
    global $wpdb;

    $blocked_statuses = ['Новая', 'В обработке', 'Подтверждена'];

    $placeholders = implode(',', array_fill(0, count($blocked_statuses), '%s'));

    if ($client_id) {
        $where = $wpdb->prepare("fr.client_id = %d", $client_id);
    } else {
        $where = $wpdb->prepare("fr.guest_id = %s", $guest_id);
    }

    $sql = "
        SELECT f.fitting_id, f.fitting_status
        FROM Fitting f
        INNER JOIN FittingRoom fr ON f.fitting_room_id = fr.fitting_room_id
        WHERE $where
        AND f.fitting_status IN ($placeholders)
        LIMIT 1
    ";

    // ВАЖНО: теперь передаём параметры правильно
    $prepared = $wpdb->prepare($sql, ...$blocked_statuses);

    return $wpdb->get_row($prepared);
}



// Обработка отправки заявки
add_action('wp_ajax_submit_fitting_request', 'submit_fitting_request');
add_action('wp_ajax_nopriv_submit_fitting_request', 'submit_fitting_request');

function submit_fitting_request() {
    global $wpdb;

    $logs = []; // массив логов

    // Получаем данные из формы
    $clientName    = sanitize_text_field($_POST['clientName'] ?? '');
    $clientPhone   = sanitize_text_field($_POST['clientPhone'] ?? '');
    $clientEmail   = sanitize_email($_POST['clientEmail'] ?? '');
    $preferredDate = sanitize_text_field($_POST['preferredDate'] ?? '');
    $preferredTime = sanitize_text_field($_POST['preferredTime'] ?? '');
    $comments      = sanitize_textarea_field($_POST['comments'] ?? '');

    $client_id = isset($_COOKIE['client_id']) ? intval($_COOKIE['client_id']) : null;
    $guest_id  = isset($_COOKIE['guest_id']) ? sanitize_text_field($_COOKIE['guest_id']) : null;

    $logs[] = "Получены данные: clientName=$clientName, clientPhone=$clientPhone, clientEmail=$clientEmail";

    // ==== Логика гостя ====
    if (!$client_id && !$guest_id) {
        $wpdb->insert('Client', [
            'first_name' => $clientName,
            'phone'      => $clientPhone,
            'email'      => $clientEmail
        ]);
        $client_id = $wpdb->insert_id;
        $logs[] = "Создан новый клиент: client_id=$client_id";

        $guest_id = uniqid('guest_');
        setcookie('client_id', $client_id, time() + 3600*24*30, '/');
        setcookie('guest_id', $guest_id, time() + 3600*24*30, '/');
        $logs[] = "Сгенерирован guest_id=$guest_id";
    }


    /* =============================
   П Р О В Е Р К А  Н А  З А Я В К У
   ============================= */

    $existing = client_has_active_fitting($client_id, $guest_id);

    if ($existing) {
        wp_send_json([
            'success' => false,
            'type' => 'already_exists',
            'message' => 'У вас уже есть активная заявка на примерку.',
            'status' => $existing->fitting_status,
            'logs' => $logs
        ]);
    }


    // ==== Получаем свободный слот ====
    $fitting_datetime = $wpdb->get_row($wpdb->prepare(
        "SELECT fitting_datetime_id 
         FROM FittingDateTime 
         WHERE fitting_date=%s AND fitting_time=%s AND datatime_status='Свободно'
         LIMIT 1",
         $preferredDate, $preferredTime
    ));
    $logs[] = 'Найден fitting_datetime: ' . print_r($fitting_datetime, true);

    if (!$fitting_datetime) {
        wp_send_json([
            'success' => false,
            'message' => 'Выбранная дата и время уже заняты.',
            'logs' => $logs
        ]);
    }

    // ==== Получаем или создаём FittingRoom ====
    $fitting_room_id = $wpdb->get_var($wpdb->prepare(
        "SELECT fitting_room_id 
         FROM FittingRoom 
         WHERE (client_id=%d OR guest_id=%s) 
         ORDER BY fitting_room_id DESC LIMIT 1",
         $client_id ?? 0, $guest_id ?? ''
    ));

    if (!$fitting_room_id) {
        wp_send_json([
            'success' => false,
            'message' => 'В вашей примерочной нет товаров для примерки.',
            'logs' => $logs
        ]);
    }

    // ==== Создаем Fitting ====
    $inserted = $wpdb->insert('Fitting', [
        'fitting_room_id'     => $fitting_room_id,
        'fitting_datetime_id' => $fitting_datetime->fitting_datetime_id,
        'fitting_status'      => 'Новая',
        'employee_id'         => null,
        'client_comment'            => $comments
    ]);

    if ($inserted === false) {
        $logs[] = "Ошибка вставки Fitting: " . $wpdb->last_error;
        wp_send_json([
            'success' => false,
            'message' => 'Ошибка при оформлении заявки.',
            'logs' => $logs
        ]);
    } else {
        $logs[] = "Fitting успешно создан: ID=" . $wpdb->insert_id;
    }

    // ==== Блокируем слот ====
    $updated = $wpdb->update(
        'FittingDateTime',
        ['datatime_status' => 'Забронировано'],
        ['fitting_datetime_id' => $fitting_datetime->fitting_datetime_id]
    );

    if ($updated === false) $logs[] = "Ошибка обновления FittingDateTime: " . $wpdb->last_error;

    wp_send_json([
        'success' => true,
        'message' => 'Заявка успешно оформлена!',
        'logs' => $logs
    ]);
}

//Админская часть - разграничение страниц
// Регистрируем query_var 'category'
function register_admin_category_query_var($vars) {
    $vars[] = 'category';
    return $vars;
}
add_filter('query_vars', 'register_admin_category_query_var');

// Добавляем rewrite rule для /admin/catalog/<slug>/
function add_admin_category_rewrite_rule() {
   
    add_rewrite_rule(
        '^admin/catalog/([^/]+)/?$',
        'index.php?pagename=admin/catalog/category&category=$matches[1]',
        'top'
    );
    // Добавление товара
    add_rewrite_rule(
        '^admin/add-product/?$',
        'index.php?pagename=admin/add-product',
        'top'
    );

    // Редактирование товара
    add_rewrite_rule(
        '^admin/edit-product/?$',
        'index.php?pagename=admin/edit-product',
        'top'
    );
    
    
}
add_action('init', 'add_admin_category_rewrite_rule');


//ОТМЕНА ПРИМЕРКИ КЛИЕНТОМ
add_action('wp_ajax_cancel_fitting', 'cancel_fitting');
add_action('wp_ajax_nopriv_cancel_fitting', 'cancel_fitting');

function cancel_fitting() {
    global $wpdb;

    $fitting_id = intval($_POST['fitting_id'] ?? 0);

    if (!$fitting_id) {
        echo "ERROR: fitting_id not provided";
        wp_die();
    }

    $status = $wpdb->get_var($wpdb->prepare(
        "SELECT fitting_status FROM Fitting WHERE fitting_id = %d",
        $fitting_id
    ));

    if ($status === null) {
        echo "ERROR: fitting not found";
        wp_die();
    }

    if ($status !== 'Новая') {
        echo "ERROR: fitting status is not 'Новая' (current status: $status)";
        wp_die();
    }

    $updated = $wpdb->update(
        'Fitting',
        ['fitting_status' => 'Отменена'],
        ['fitting_id' => $fitting_id],
        ['%s'],
        ['%d']
    );

    if ($updated !== false) {
        echo "OK";
    } else {
        echo "ERROR: database update failed";
    }

    wp_die();
}

// Получение последней примерки пользователя через AJAX
add_action('wp_ajax_get_user_fitting', 'get_user_fitting');
add_action('wp_ajax_nopriv_get_user_fitting', 'get_user_fitting');

function get_user_fitting() {
    global $wpdb;

    $client_id = intval($_POST['client_id'] ?? 0);
    $guest_id  = intval($_POST['guest_id'] ?? 0);

    if (!$client_id && !$guest_id) {
        echo json_encode(['success' => false]);
        wp_die();
    }

    $where = '';
    $params = [];
    if ($client_id) {
        $where = 'FR.client_id = %d';
        $params[] = $client_id;
    } else {
        $where = 'FR.guest_id = %d';
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

    if (!$fitting) {
        echo json_encode(['success' => false]);
        wp_die();
    }

    $products = $wpdb->get_results($wpdb->prepare("
        SELECT P.product_name, S.size_value
        FROM CartItem CI
        JOIN ProductSize PS ON PS.product_size_id = CI.product_size_id
        JOIN Product P ON P.product_id = PS.product_id
        JOIN Size S ON S.size_id = PS.size_id
        WHERE CI.fitting_room_id = %d
    ", $fitting->fitting_room_id));

    echo json_encode([
        'success' => true,
        'fitting' => $fitting,
        'products' => $products
    ]);
    wp_die();
}

//ПОДТВЕРЖДЕНИЕ И ОТМЕНА ПРИМЕРОК МЕНЕДЖЕРОМ ИЛИ АДМИНОМ
/* ------------------ ПОДТВЕРЖДЕНИЕ ------------------ */
add_action('wp_ajax_approve_fitting', 'approve_fitting');
function approve_fitting(){

    check_ajax_referer('approve_fitting_nonce', 'nonce');

    global $wpdb;
    $id = intval($_POST['fitting_id']);

    $wpdb->update(
        "Fitting",
        ["fitting_status" => "Подтверждена"],
        ["fitting_id" => $id]
    );

    wp_send_json_success(["message" => "Заявка подтверждена"]);
}

/* ------------------ ОТМЕНА ------------------ */
add_action('wp_ajax_cancel_fitting_admin', 'cancel_fitting_admin');
function cancel_fitting_admin(){

    check_ajax_referer('cancel_fitting_nonce', 'nonce');

    global $wpdb;
    $id = intval($_POST['fitting_id']);

    $wpdb->update(
        "Fitting",
        ["fitting_status" => "Отменена"],
        ["fitting_id" => $id]
    );

    wp_send_json_success(["message" => "Заявка отменена"]);
}

//НАЗНАЧЕНИЕ СОТРУДНИКА
// Обработчик назначения сотрудника
add_action('wp_ajax_assign_employee', 'assign_employee_callback');

function assign_employee_callback() {
    // Проверка прав — например, нужно быть админом
   /* if (!current_user_can('manage_options')) {
        wp_send_json(['success' => false, 'message' => 'Нет прав']);
    }*/

    $fitting_id = isset($_POST['fitting_id']) ? intval($_POST['fitting_id']) : 0;
    $employee_id = isset($_POST['employee_id']) && $_POST['employee_id'] !== '' ? intval($_POST['employee_id']) : null;

    if (!$fitting_id) {
        wp_send_json(['success' => false, 'message' => 'Нет ID примерки']);
    }

    global $wpdb;
    $table = 'Fitting'; // твоя таблица примерок

    $updated = $wpdb->update(
        $table,
        ['employee_id' => $employee_id],
        ['fitting_id' => $fitting_id],
        ['%d'],
        ['%d']
    );

    if ($updated !== false) {
        wp_send_json(['success' => true]);
    } else {
        wp_send_json(['success' => false, 'message' => 'Ошибка обновления']);
    }

    wp_die();
}


add_action('wp_ajax_get_fittings', 'get_fittings_ajax');

function get_fittings_ajax() {
    global $wpdb;

    $type = sanitize_text_field($_GET['type'] ?? '');
    $date_raw = sanitize_text_field($_GET['date'] ?? date('d.m.Y'));

    // Преобразуем дату из формата d.m.Y в Y-m-d для SQL
    $date_parts = explode('.', $date_raw);
    if(count($date_parts) === 3){
        $date = $date_parts[2] . '-' . $date_parts[1] . '-' . $date_parts[0];
    } else {
        $date = date('Y-m-d');
    }

    // Определяем статусы
    if ($type === 'active') {
        $statuses = ['Подтверждена'];
    } elseif ($type === 'finished') {
        $statuses = ['Отменена', 'Завершена'];
    } else {
        wp_send_json_error('Неверный тип');
    }

    // Запрос с джойном на Employee
    if (count($statuses) === 1) {
        $query = $wpdb->prepare("
            SELECT 
                f.fitting_id,
                fr.fitting_room_id,
                c.first_name AS client_name,
                c.phone AS client_phone,
                fdt.fitting_date,
                fdt.fitting_time,
                f.fitting_status,
                e.first_name AS emp_first_name,
                e.last_name AS emp_last_name,
                e.middle_name AS emp_middle_name,
                (SELECT COUNT(*) 
                 FROM CartItem ci 
                 WHERE ci.fitting_room_id = fr.fitting_room_id) AS products_count
            FROM Fitting f
            JOIN FittingRoom fr ON f.fitting_room_id = fr.fitting_room_id
            LEFT JOIN Client c ON fr.client_id = c.client_id
            LEFT JOIN Employee e ON f.employee_id = e.employee_id
            JOIN FittingDateTime fdt ON f.fitting_datetime_id = fdt.fitting_datetime_id
            WHERE f.fitting_status = %s
              AND fdt.fitting_date = %s
            ORDER BY fdt.fitting_time ASC
        ", $statuses[0], $date);
    } else {
        $query = $wpdb->prepare("
            SELECT 
                f.fitting_id,
                fr.fitting_room_id,
                c.first_name AS client_name,
                c.phone AS client_phone,
                fdt.fitting_date,
                fdt.fitting_time,
                f.fitting_status,
                e.first_name AS emp_first_name,
                e.last_name AS emp_last_name,
                e.middle_name AS emp_middle_name,
                (SELECT COUNT(*) 
                 FROM CartItem ci 
                 WHERE ci.fitting_room_id = fr.fitting_room_id) AS products_count
            FROM Fitting f
            JOIN FittingRoom fr ON f.fitting_room_id = fr.fitting_room_id
            LEFT JOIN Client c ON fr.client_id = c.client_id
            LEFT JOIN Employee e ON f.employee_id = e.employee_id
            JOIN FittingDateTime fdt ON f.fitting_datetime_id = fdt.fitting_datetime_id
            WHERE f.fitting_status IN (%s, %s)
              AND fdt.fitting_date = %s
            ORDER BY fdt.fitting_time ASC
        ", $statuses[0], $statuses[1], $date);
    }

    $results = $wpdb->get_results($query);

    // Проверка и вывод
    if (empty($results)) {
        echo '<p>Нет примерок на эту дату.</p>';
    } else {
        render_fittings_list($results);
    }

    wp_die();
}

// Функция для вывода карточек примерок
function render_fittings_list($list) {
    if (empty($list)) {
        echo "<p>Нет примерок на эту дату.</p>";
        return;
    }

    foreach ($list as $item) {
        $employee_fio = trim($item->emp_last_name . ' ' . $item->emp_first_name . ' ' . $item->emp_middle_name);
        if (empty($employee_fio)) $employee_fio = 'Не назначен';
        ?>
        <div class="fitting-card" 
             data-id="<?= $item->fitting_id; ?>" 
             data-link="/admin/this-fitting/?id=<?= $item->fitting_id; ?>">
            <div class="fitting-card-content">
                <div class="fitting-info">
                    <div class="fitting-header">
                        <span class="fitting-status"><?= esc_html($item->fitting_status); ?></span>
                        <span class="fitting-id">ID: <?= esc_html($item->fitting_id); ?></span>
                    </div>
                    <p><strong>Время:</strong> <?= date('H:i', strtotime($item->fitting_time)); ?></p>
                    <p><strong>Клиент:</strong> <?= esc_html($item->client_name); ?></p>
                    <p><strong>Телефон:</strong> <?= esc_html($item->client_phone); ?></p>
                    <p><strong>Сотрудник:</strong> <?= esc_html($employee_fio); ?></p>
                    <p><strong>Товаров:</strong> <?= esc_html($item->products_count); ?></p>
                </div>
            </div>
        </div>
        <?php
    }
}

