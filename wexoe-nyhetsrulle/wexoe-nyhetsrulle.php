<?php
/**
 * Plugin Name: Wexoe Nyhetsrulle
 * Description: En nyhetsrulle widget för att visa de senaste nyheterna från WordPress posts
 * Version: 1.1.0
 * Author: Wexoe
 * Text Domain: wexoe-news
 */

// Förhindra direkt åtkomst
if (!defined('ABSPATH')) {
    exit;
}

// Plugin-konstanter
define('WEXOE_NEWS_VERSION', '1.1.0');
define('WEXOE_NEWS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WEXOE_NEWS_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Huvudklass för pluginen
 */
class WexoeNewsTicker {
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('wexoe_nyhetsrulle', array($this, 'render_shortcode'));
        add_action('wp_ajax_wexoe_get_news', array($this, 'ajax_get_news'));
        add_action('wp_ajax_nopriv_wexoe_get_news', array($this, 'ajax_get_news'));
    }
    
    /**
     * Ladda in CSS och JavaScript
     */
    public function enqueue_scripts() {
        wp_enqueue_style('wexoe-news-style', 
                        WEXOE_NEWS_PLUGIN_URL . 'assets/style.css', 
                        array(), 
                        WEXOE_NEWS_VERSION);
        
        wp_enqueue_script('wexoe-news-script', 
                         WEXOE_NEWS_PLUGIN_URL . 'assets/script.js', 
                         array('jquery'), 
                         WEXOE_NEWS_VERSION, 
                         true);
        
        wp_localize_script('wexoe-news-script', 'wexoe_news_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wexoe_news_nonce')
        ));
    }
    
    /**
     * Rendera shortcode
     */
    public function render_shortcode($atts) {
        $atts = shortcode_atts(array(
            'count' => 5, // Ändrat från 4 till 5
            'category' => '', // Tom = alla kategorier
            'category_slug' => '', // Använd slug istället för namn
            'style' => 'default',
            'auto_rotate' => 'true',
            'rotate_interval' => '5000',
            'post_type' => 'post', // Använd vanliga posts som standard
            'exclude_categories' => '', // Uteslut vissa kategorier
            'show_excerpt' => 'true',
            'excerpt_length' => 15
        ), $atts);
        
        // Bygg query arguments
        $args = array(
            'post_type' => $atts['post_type'],
            'posts_per_page' => intval($atts['count']),
            'orderby' => 'date',
            'order' => 'DESC',
            'post_status' => 'publish'
        );
        
        // Hantera kategorier
        if (!empty($atts['category_slug'])) {
            // Använd slug (tex "aktuellt" eller "cases")
            $args['category_name'] = $atts['category_slug'];
        } elseif (!empty($atts['category'])) {
            // Använd kategori ID eller namn
            if (is_numeric($atts['category'])) {
                $args['cat'] = intval($atts['category']);
            } else {
                $args['category_name'] = $atts['category'];
            }
        }
        
        // Uteslut kategorier om angivet
        if (!empty($atts['exclude_categories'])) {
            $exclude_ids = array_map('intval', explode(',', $atts['exclude_categories']));
            $args['category__not_in'] = $exclude_ids;
        }
        
        $news_query = new WP_Query($args);
        
        ob_start();
        ?>
        <div class="wexoe-news-ticker <?php echo esc_attr($atts['style']); ?>" 
             data-auto-rotate="<?php echo esc_attr($atts['auto_rotate']); ?>"
             data-rotate-interval="<?php echo esc_attr($atts['rotate_interval']); ?>">
            
            <div class="wexoe-news-header">
                <span class="wexoe-news-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <h3>Senaste nytt</h3>
            </div>
            
            <div class="wexoe-news-container">
                <?php if ($news_query->have_posts()) : ?>
                    <div class="wexoe-news-list">
                        <?php while ($news_query->have_posts()) : $news_query->the_post(); 
                            $categories = get_the_category();
                            $category_name = !empty($categories) ? $categories[0]->name : '';
                            $is_external = get_post_meta(get_the_ID(), 'external_link', true);
                            $link = !empty($is_external) ? $is_external : get_permalink();
                            
                            // Bestäm prioritet baserat på kategori eller meta
                            $priority = '';
                            if ($category_name === 'Brådskande' || $category_name === 'Urgent') {
                                $priority = 'urgent';
                            } elseif ($category_name === 'Viktigt' || $category_name === 'Important') {
                                $priority = 'high';
                            }
                        ?>
                            <article class="wexoe-news-item <?php echo esc_attr($priority); ?>" 
                                     data-news-id="<?php echo get_the_ID(); ?>">
                                
                                <div class="wexoe-news-date">
                                    <span class="day"><?php echo get_the_date('d'); ?></span>
                                    <span class="month"><?php echo get_the_date('M'); ?></span>
                                </div>
                                
                                <div class="wexoe-news-content">
                                    <h4 class="wexoe-news-title">
                                        <a href="<?php echo esc_url($link); ?>" 
                                           <?php echo !empty($is_external) ? 'target="_blank" rel="noopener"' : ''; ?>>
                                            <?php the_title(); ?>
                                        </a>
                                    </h4>
                                </div>
                                
                                <div class="wexoe-news-arrow">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path d="M9 18l6-6-6-6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                            </article>
                        <?php endwhile; ?>
                    </div>
                    
                    <?php if ($news_query->found_posts > intval($atts['count'])) : ?>
                    <div class="wexoe-news-navigation">
                        <button class="wexoe-news-nav prev" aria-label="Föregående">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M15 18l-6-6 6-6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                        <div class="wexoe-news-dots"></div>
                        <button class="wexoe-news-nav next" aria-label="Nästa">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M9 18l6-6-6-6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>
                    <?php endif; ?>
                    
                <?php else : ?>
                    <div class="wexoe-news-empty">
                        <p>Inga nyheter att visa just nu.</p>
                        <small>Tips: Kontrollera att du har publicerade inlägg i rätt kategori.</small>
                    </div>
                <?php endif; ?>
                
                <?php wp_reset_postdata(); ?>
            </div>
            
            <?php 
            // Visa länk till arkivsida beroende på kategori
            $archive_link = '';
            if (!empty($atts['category_slug'])) {
                $category = get_category_by_slug($atts['category_slug']);
                if ($category) {
                    $archive_link = get_category_link($category->term_id);
                }
            } else {
                $archive_link = get_permalink(get_option('page_for_posts'));
            }
            
            if ($archive_link) : ?>
            <div class="wexoe-news-footer">
                <a href="<?php echo esc_url($archive_link); ?>" class="wexoe-news-all-link">
                    Visa alla nyheter →
                </a>
            </div>
            <?php endif; ?>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * AJAX handler för att hämta nyheter
     */
    public function ajax_get_news() {
        check_ajax_referer('wexoe_news_nonce', 'nonce');
        
        $count = isset($_POST['count']) ? intval($_POST['count']) : 4;
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
        
        $args = array(
            'post_type' => 'post',
            'posts_per_page' => $count,
            'offset' => $offset,
            'orderby' => 'date',
            'order' => 'DESC',
            'post_status' => 'publish'
        );
        
        if (!empty($category)) {
            $args['category_name'] = $category;
        }
        
        $news_query = new WP_Query($args);
        $news_data = array();
        
        if ($news_query->have_posts()) {
            while ($news_query->have_posts()) {
                $news_query->the_post();
                
                $categories = get_the_category();
                $category_name = !empty($categories) ? $categories[0]->name : '';
                $is_external = get_post_meta(get_the_ID(), 'external_link', true);
                
                $news_data[] = array(
                    'id' => get_the_ID(),
                    'title' => get_the_title(),
                    'excerpt' => has_excerpt() ? get_the_excerpt() : wp_trim_words(get_the_content(), 15),
                    'link' => !empty($is_external) ? $is_external : get_permalink(),
                    'date' => get_the_date('Y-m-d'),
                    'date_formatted' => get_the_date('d M'),
                    'time_ago' => human_time_diff(get_the_time('U'), current_time('timestamp')) . ' sedan',
                    'category' => $category_name,
                    'is_external' => !empty($is_external)
                );
            }
            wp_reset_postdata();
        }
        
        wp_send_json_success($news_data);
    }
}

// Initiera pluginen
new WexoeNewsTicker();

// Aktiverings-hook
register_activation_hook(__FILE__, 'wexoe_news_activate');
function wexoe_news_activate() {
    flush_rewrite_rules();
}

// Avaktiverings-hook
register_deactivation_hook(__FILE__, 'wexoe_news_deactivate');
function wexoe_news_deactivate() {
    flush_rewrite_rules();
}

// Debug-funktion för att lista alla kategorier (kan tas bort senare)
add_shortcode('wexoe_debug_categories', 'wexoe_debug_categories');
function wexoe_debug_categories() {
    $categories = get_categories(array(
        'orderby' => 'name',
        'order' => 'ASC',
        'hide_empty' => false
    ));
    
    $output = '<div style="background: #f5f5f5; padding: 20px; margin: 20px 0; border-radius: 5px;">';
    $output .= '<h3>Tillgängliga kategorier:</h3>';
    $output .= '<ul>';
    foreach ($categories as $category) {
        $output .= '<li>';
        $output .= '<strong>' . $category->name . '</strong>';
        $output .= ' (ID: ' . $category->term_id . ', ';
        $output .= 'Slug: ' . $category->slug . ', ';
        $output .= 'Antal inlägg: ' . $category->count . ')';
        $output .= '</li>';
    }
    $output .= '</ul>';
    $output .= '<p><em>Använd slug i shortcode: [wexoe_nyhetsrulle category_slug="aktuellt"]</em></p>';
    $output .= '</div>';
    
    return $output;
}