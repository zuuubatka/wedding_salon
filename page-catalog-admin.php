<?php
/*
Template Name: Каталог-Админ
*/
include get_template_directory() . '/admin-header.php';
global $wpdb;

$category_slug = get_query_var('category', '');
if ($category_slug) {
    // Если slug есть — подключаем шаблон категории и прекращаем выполнение
    include get_template_directory() . '/page-category-template.php'; // путь к твоему шаблону "Категория товара"
    exit;
}

// ===== Если slug пустой — показываем обычный каталог =====
$product_types = $wpdb->get_results("SELECT * FROM ProductType ORDER BY product_type_id ASC");
?>



<div class="catalog-wrapper">
  <h1 class="catalog-title">Каталог товаров</h1>
  <div class="catalog-page">
    <div class="catalog-grid">
      <?php foreach ($product_types as $type): 
        $slug = sanitize_title($type->slug);
        $link = site_url('/catalog/' . $slug . '/');
      ?>
        <a href="<?php echo esc_url($link); ?>" class="catalog-card">
          <div class="catalog-card-inner">
            <div class="catalog-card-title"><?php echo esc_html($type->product_type_name); ?></div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<style>
/* Основная обёртка для фиксации футера внизу */
html, body {
  height: 100%;
  margin: 0;
}

body {
  display: flex;
  flex-direction: column;
}

/* Контентная часть (чтобы футер был внизу при малом контенте) */
.catalog-wrapper {
  flex: 1; /* растягивается, чтобы занять всё свободное место */
  display: flex;
  flex-direction: column;
  align-items: center;
}

/* Твоя сетка каталога */
.catalog-page {
  width: 95%;
  text-align: center;
  flex: 1;
}

.catalog-title {
  font-size: 28px;
  font-weight: bold;
  margin-top: 10px;
}

.catalog-grid {
  width: 100%;
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 25px;
  justify-items: center;
}

.catalog-card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
  transition: transform 0.25s ease, box-shadow 0.25s ease;
  text-decoration: none;
  color: inherit;
  width: 95%;
}

.catalog-card-inner {
  /*padding: 60px 20px;*/
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;   
}

.catalog-card-title {
  font-size: 20px;
  font-weight: 600;
}

.catalog-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 8px 16px rgba(0,0,0,0.15);
}

/* Адаптив */
@media (max-width: 900px) {
  .catalog-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
@media (max-width: 600px) {
  .catalog-grid {
    grid-template-columns: 1fr;
  }
}
</style>

<?php get_footer(); ?>
