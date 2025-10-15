<?php
/**
 * Plugin Name: Wexoe Nyhetsrulle
 * Description: En kompakt nyhetsrulle widget för att visa de senaste nyheterna från WordPress posts
 * Version: 2.0.0
 * Author: Wexoe
 * Text Domain: wexoe-news
 */

// Förhindra direkt åtkomst
if (!defined('ABSPATH')) {
    exit;
}

// Plugin-konstanter
define('WEXOE_NEWS_VERSION', '2.0.0');
define('WEXOE_NEWS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WEXOE_NEWS_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Huvudklass för pluginen
 */
class WexoeNewsTicker {
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('wexoe_nyhetsrulle', array($this, 'render_shortcode'));
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
    }
    
    /**
     * Rendera shortcode
     */
    public function render_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
            'category_slug' => '',
            'post_type' => 'post',
            'exclude_categories' => '',
            'show_excerpt' => 'true',
            'excerpt_length' => 20
        ), $atts);
        
        // Bygg query arguments - hämta 5 nyheter (en featured + fyra standard)
        $args = array(
            'post_type' => $atts['post_type'],
            'posts_per_page' => 5,
            'orderby' => 'date',
            'order' => 'DESC',
            'post_status' => 'publish'
        );
        
        // Hantera kategorier
        if (!empty($atts['category_slug'])) {
            $args['category_name'] = $atts['category_slug'];
        } elseif (!empty($atts['category'])) {
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
        <div class="wexoe-news-ticker-compact">
            <div class="wexoe-news-container">
                <?php if ($news_query->have_posts()) : ?>
                    <?php
                        $featured_output = '';
                        $regular_output = '';
                        $post_index = 0;

                        while ($news_query->have_posts()) :
                            $news_query->the_post();
                            $is_external = get_post_meta(get_the_ID(), 'external_link', true);
                            $link = !empty($is_external) ? $is_external : get_permalink();

                            ob_start();
                            ?>
                            <article class="wexoe-news-item <?php echo $post_index === 0 ? 'featured' : ''; ?>"
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
                                    <?php if ($post_index === 0 && $atts['show_excerpt'] === 'true') : ?>
                                        <p class="wexoe-news-excerpt">
                                            <?php echo wp_trim_words(get_the_excerpt(), intval($atts['excerpt_length'])); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </article>
                            <?php
                            $item_html = ob_get_clean();

                            if ($post_index === 0) {
                                $featured_output = $item_html;
                            } else {
                                $regular_output .= $item_html;
                            }

                            $post_index++;
                        endwhile;
                    ?>

                    <?php if (!empty($featured_output)) : ?>
                        <div class="wexoe-news-featured">
                            <?php echo $featured_output; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($regular_output)) : ?>
                        <div class="wexoe-news-list">
                            <?php echo $regular_output; ?>
                        </div>
                    <?php endif; ?>
                <?php else : ?>
                    <div class="wexoe-news-empty">
                        <p>Inga nyheter att visa just nu.</p>
                    </div>
                <?php endif; ?>
                
                <?php wp_reset_postdata(); ?>
            </div>
        </div>
        <?php
        
        return ob_get_clean();
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