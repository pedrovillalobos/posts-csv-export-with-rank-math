<?php
/**
 * Plugin Name: Posts CSV Export with Rank Math
 * Plugin URI: https://github.com/pedrovillalobos/posts-csv-export-with-rank-math
 * Description: Export WordPress posts with Rank Math SEO data to CSV format including scores, keywords, structured data, and link information.
 * Version: 1.0.14
 * Author: Pedro Villalobos
 * Author URI: https://villalobos.com.br
 * License: GPL v3 or later
 * Requires at least: 5.0
 * Tested up to: 6.8
 * Requires PHP: 7.4
 * Text Domain: posts-csv-export-with-rank-math
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants only if not already defined
if (!defined('PCERM_VERSION')) {
    define('PCERM_VERSION', '1.0.14');
}
if (!defined('PCERM_PLUGIN_URL')) {
    define('PCERM_PLUGIN_URL', plugin_dir_url(__FILE__));
}
if (!defined('PCERM_PLUGIN_PATH')) {
    define('PCERM_PLUGIN_PATH', plugin_dir_path(__FILE__));
}

/**
 * Main plugin class for Posts CSV Export with Rank Math
 * 
 * Handles all plugin functionality including admin interface, AJAX requests,
 * CSV generation, and Rank Math data retrieval.
 * 
 * @since 1.0.0
 */
if (!class_exists('Posts_CSV_Export_Rank_Math')) {
    class Posts_CSV_Export_Rank_Math {
    
    /**
     * Constructor
     * 
     * Sets up all necessary hooks and actions for the plugin.
     * 
     * @since 1.0.0
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_wperm_export_csv', array($this, 'handle_export_csv'));
        add_action('wp_ajax_wperm_debug_data', array($this, 'handle_debug_data'));
        
        // Clear cache when posts are updated
        add_action('save_post', array($this, 'clear_post_cache'));
        add_action('deleted_post', array($this, 'clear_post_cache'));
    }
    
    /**
     * Initialize plugin
     * 
     * Loads text domain for internationalization.
     * 
     * @since 1.0.0
     */
    public function init() {
        // WordPress.org automatically loads translations for plugins hosted there
        // No need to manually call load_plugin_textdomain() since WordPress 4.6
    }
    
    /**
     * Add admin menu
     * 
     * Adds the plugin menu item to the Tools menu in WordPress admin.
     * 
     * @since 1.0.0
     */
    public function add_admin_menu() {
        add_management_page(
            __('Export Rank Math Data', 'posts-csv-export-with-rank-math'),
            __('Export Rank Math', 'posts-csv-export-with-rank-math'),
            'manage_options',
            'posts-csv-export-with-rank-math',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Enqueue admin scripts
     * 
     * Enqueues JavaScript and CSS files for the admin interface.
     * Only loads on the plugin's admin page.
     * 
     * @since 1.0.0
     * @param string $hook The current admin page hook.
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'tools_page_posts-csv-export-with-rank-math') {
            return;
        }
        
        wp_enqueue_script(
            'wperm-admin',
            PCERM_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            PCERM_VERSION,
            true
        );
        
        wp_localize_script('wperm-admin', 'wperm_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wperm_export_nonce'),
            'strings' => array(
                'exporting' => __('Exporting...', 'posts-csv-export-with-rank-math'),
                'export_complete' => __('Export complete!', 'posts-csv-export-with-rank-math'),
                'export_error' => __('Export failed. Please try again.', 'posts-csv-export-with-rank-math')
            )
        ));
        
        wp_enqueue_style(
            'wperm-admin',
            PCERM_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            PCERM_VERSION
        );
    }
    
    /**
     * Admin page content
     * 
     * Renders the main admin interface for the export functionality.
     * 
     * @since 1.0.0
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Export Rank Math Data', 'posts-csv-export-with-rank-math'); ?></h1>
            
            <div class="wperm-container">
                <div class="wperm-card">
                    <h2><?php echo esc_html__('Export Settings', 'posts-csv-export-with-rank-math'); ?></h2>
                    
                    <form id="wperm-export-form">
                        <div class="notice notice-info" style="margin-bottom: 20px;">
                            <p><?php echo esc_html__('If you don\'t choose a start and end date, it\'ll default to export everything.', 'posts-csv-export-with-rank-math'); ?></p>
                        </div>
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="post_type"><?php echo esc_html__('Post Type', 'posts-csv-export-with-rank-math'); ?></label>
                                </th>
                                <td>
                                    <select name="post_type" id="post_type">
                                        <option value="post"><?php echo esc_html__('Posts', 'posts-csv-export-with-rank-math'); ?></option>
                                        <option value="page"><?php echo esc_html__('Pages', 'posts-csv-export-with-rank-math'); ?></option>
                                        <option value="all"><?php echo esc_html__('All Post Types', 'posts-csv-export-with-rank-math'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="post_status"><?php echo esc_html__('Post Status', 'posts-csv-export-with-rank-math'); ?></label>
                                </th>
                                <td>
                                    <select name="post_status" id="post_status">
                                        <option value="publish"><?php echo esc_html__('Published', 'posts-csv-export-with-rank-math'); ?></option>
                                        <option value="draft"><?php echo esc_html__('Draft', 'posts-csv-export-with-rank-math'); ?></option>
                                        <option value="pending"><?php echo esc_html__('Pending Review', 'posts-csv-export-with-rank-math'); ?></option>
                                        <option value="all"><?php echo esc_html__('All Statuses', 'posts-csv-export-with-rank-math'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="date_from"><?php echo esc_html__('Date From', 'posts-csv-export-with-rank-math'); ?></label>
                                </th>
                                <td>
                                    <input type="date" name="date_from" id="date_from" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="date_to"><?php echo esc_html__('Date To', 'posts-csv-export-with-rank-math'); ?></label>
                                </th>
                                <td>
                                    <input type="date" name="date_to" id="date_to" />
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" class="button button-primary" id="wperm-export-btn">
                                <?php echo esc_html__('Export to CSV', 'posts-csv-export-with-rank-math'); ?>
                            </button>
                            <span class="spinner" style="float: none; margin-left: 10px;"></span>
                        </p>
                        
                        <p>
                            <button type="button" class="button button-secondary" id="wperm-debug-btn">
                                <?php echo esc_html__('Debug Rank Math Data', 'posts-csv-export-with-rank-math'); ?>
                            </button>
                            <small style="margin-left: 10px; color: #666;">
                                <?php echo esc_html__('Click to see what Rank Math data is available for a sample post', 'posts-csv-export-with-rank-math'); ?>
                            </small>
                        </p>
                    </form>
                </div>
                
                <div class="wperm-card">
                    <h2><?php echo esc_html__('Export Information', 'posts-csv-export-with-rank-math'); ?></h2>
                    <p><?php echo esc_html__('This export will include the following data:', 'posts-csv-export-with-rank-math'); ?></p>
                    <ul>
                        <li><?php echo esc_html__('Post Title', 'posts-csv-export-with-rank-math'); ?></li>
                        <li><?php echo esc_html__('Post URL', 'posts-csv-export-with-rank-math'); ?></li>
                        <li><?php echo esc_html__('Author', 'posts-csv-export-with-rank-math'); ?></li>
                        <li><?php echo esc_html__('Status (Published, Draft, etc.)', 'posts-csv-export-with-rank-math'); ?></li>
                        <li><?php echo esc_html__('Last Edit Date', 'posts-csv-export-with-rank-math'); ?></li>
                        <li><?php echo esc_html__('Categories', 'posts-csv-export-with-rank-math'); ?></li>
                        <li><?php echo esc_html__('Rank Math Score', 'posts-csv-export-with-rank-math'); ?></li>
                        <li><?php echo esc_html__('Rank Math Main Keyword', 'posts-csv-export-with-rank-math'); ?></li>
                        <li><?php echo esc_html__('Rank Math Additional Keywords', 'posts-csv-export-with-rank-math'); ?></li>
                        <li><?php echo esc_html__('Rank Math Structured Data Type', 'posts-csv-export-with-rank-math'); ?></li>
                        <li><?php echo esc_html__('Rank Math Internal Links', 'posts-csv-export-with-rank-math'); ?></li>
                        <li><?php echo esc_html__('Rank Math External Links', 'posts-csv-export-with-rank-math'); ?></li>
                        <li><?php echo esc_html__('Rank Math Incoming Links', 'posts-csv-export-with-rank-math'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Handle CSV export via AJAX
     * 
     * Processes AJAX requests for CSV export with security checks
     * and input validation.
     * 
     * @since 1.0.0
     */
    public function handle_export_csv() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'wperm_export_nonce')) {
            wp_die(esc_html__('Security check failed.', 'posts-csv-export-with-rank-math'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'posts-csv-export-with-rank-math'));
        }
        
        // Get export parameters with proper sanitization and validation
        $post_type = isset($_POST['post_type']) ? sanitize_text_field(wp_unslash($_POST['post_type'])) : '';
        $post_status = isset($_POST['post_status']) ? sanitize_text_field(wp_unslash($_POST['post_status'])) : '';
        $date_from = isset($_POST['date_from']) ? sanitize_text_field(wp_unslash($_POST['date_from'])) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field(wp_unslash($_POST['date_to'])) : '';
        
        // Validate post type
        $allowed_post_types = array('post', 'page', 'all');
        if (!in_array($post_type, $allowed_post_types)) {
            wp_send_json_error(esc_html__('Invalid post type specified.', 'posts-csv-export-with-rank-math'));
        }
        
        // Validate post status
        $allowed_post_statuses = array('publish', 'draft', 'pending', 'all');
        if (!in_array($post_status, $allowed_post_statuses)) {
            wp_send_json_error(esc_html__('Invalid post status specified.', 'posts-csv-export-with-rank-math'));
        }
        
        // Validate date format
        if (!empty($date_from) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) {
            wp_send_json_error(esc_html__('Invalid date format for "Date From".', 'posts-csv-export-with-rank-math'));
        }
        
        if (!empty($date_to) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)) {
            wp_send_json_error(esc_html__('Invalid date format for "Date To".', 'posts-csv-export-with-rank-math'));
        }
        
        // Generate CSV
        $csv_data = $this->generate_csv_data($post_type, $post_status, $date_from, $date_to);
        
        if ($csv_data === false) {
            wp_send_json_error(esc_html__('Failed to generate CSV data.', 'posts-csv-export-with-rank-math'));
        }
        
        // Set headers for file download
        $filename = 'rank-math-export-' . gmdate('Y-m-d-H-i-s') . '.csv';
        
        wp_send_json_success(array(
            'csv_data' => $csv_data,
            'filename' => $filename
        ));
    }
    
    /**
     * Handle debug data request via AJAX
     * 
     * Processes AJAX requests for debugging Rank Math data
     * with security checks.
     * 
     * @since 1.0.0
     */
    public function handle_debug_data() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'wperm_export_nonce')) {
            wp_die(esc_html__('Security check failed.', 'posts-csv-export-with-rank-math'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'posts-csv-export-with-rank-math'));
        }
        
        // Get a sample post to debug
        $posts = get_posts(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'numberposts' => 1
        ));
        
        if (empty($posts)) {
            wp_send_json_error(esc_html__('No posts found to debug.', 'posts-csv-export-with-rank-math'));
        }
        
        $post = $posts[0];
        $debug_data = $this->debug_rank_math_data($post->ID);
        
        wp_send_json_success(array(
            'post_title' => $post->post_title,
            'post_id' => $post->ID,
            'debug_data' => $debug_data
        ));
    }
    
    /**
     * Generate CSV data
     * 
     * Generates CSV data for the specified posts with Rank Math information.
     * 
     * @since 1.0.0
     * @param string $post_type   The post type to export.
     * @param string $post_status The post status to export.
     * @param string $date_from   The start date for filtering.
     * @param string $date_to     The end date for filtering.
     * @return string|false CSV data as string or false on failure.
     */
    private function generate_csv_data($post_type, $post_status, $date_from, $date_to) {
        global $wpdb;
        
        // Build query
        $where_conditions = array();
        $where_values = array();
        
        // Post type condition
        if ($post_type !== 'all') {
            $where_conditions[] = 'p.post_type = %s';
            $where_values[] = $post_type;
        }
        
        // Post status condition
        if ($post_status !== 'all') {
            $where_conditions[] = 'p.post_status = %s';
            $where_values[] = $post_status;
        }
        
        // Date conditions - only apply if both dates are specified
        if (!empty($date_from) && !empty($date_to)) {
            $where_conditions[] = 'p.post_modified >= %s';
            $where_values[] = $date_from . ' 00:00:00';
            
            $where_conditions[] = 'p.post_modified <= %s';
            $where_values[] = $date_to . ' 23:59:59';
        } elseif (!empty($date_from)) {
            // Only from date specified
            $where_conditions[] = 'p.post_modified >= %s';
            $where_values[] = $date_from . ' 00:00:00';
        } elseif (!empty($date_to)) {
            // Only to date specified
            $where_conditions[] = 'p.post_modified <= %s';
            $where_values[] = $date_to . ' 23:59:59';
        }
        // If no dates specified, export all posts (no date filter applied)
        
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        // Build the main query with proper table name handling
        $posts_table = $wpdb->posts;
        $users_table = $wpdb->users;
        
        $query = "
            SELECT 
                p.ID,
                p.post_title,
                p.post_status,
                p.post_modified,
                p.post_author,
                p.post_type,
                u.display_name as author_name
            FROM {$posts_table} p
            LEFT JOIN {$users_table} u ON p.post_author = u.ID
            {$where_clause}
            ORDER BY p.post_modified DESC
        ";
        
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }
        
        // Use caching for the query results
        $posts = $this->get_cached_posts($query, $where_values);
        
        if ($posts === null) {
            return false;
        }
        
        if (empty($posts)) {
            return false;
        }
        
        // Prepare CSV data
        $csv_data = array();
        
        // CSV headers
        $headers = array(
            'Post Title',
            'Post URL',
            'Author',
            'Status',
            'Last Edit Date',
            'Categories',
            'Rank Math Score',
            'Rank Math Main Keyword',
            'Rank Math Structured Data Type',
            'Rank Math Internal Links',
            'Rank Math External Links',
            'Rank Math Incoming Links'
        );
        
        $csv_data[] = $headers;
        
        // Process each post
        foreach ($posts as $post) {
            $row = array();
            
            // Post Title
            $row[] = $this->escape_csv_value($post->post_title);
            
            // Post URL
            $row[] = get_permalink($post->ID);
            
            // Author
            $row[] = $this->escape_csv_value($post->author_name);
            
            // Status
            $row[] = $this->escape_csv_value($post->post_status);
            
            // Last Edit Date
            $row[] = $this->escape_csv_value($post->post_modified);
            
            // Categories
            $categories = get_the_category($post->ID);
            $category_names = array();
            foreach ($categories as $category) {
                $category_names[] = $category->name;
            }
            $row[] = $this->escape_csv_value(implode(', ', $category_names));
            
            // Rank Math Score - try multiple possible meta keys
            $rank_math_score = $this->get_rank_math_score($post->ID);
            $row[] = $this->escape_csv_value($rank_math_score);
            
            // Rank Math Main Keyword - try multiple possible meta keys
            $rank_math_keyword = $this->get_rank_math_keyword($post->ID);
            $row[] = $this->escape_csv_value($rank_math_keyword);
            
            // Rank Math Structured Data Type - try multiple possible meta keys
            $rank_math_schema = $this->get_rank_math_schema($post->ID);
            $row[] = $this->escape_csv_value($rank_math_schema);
            
            // Rank Math Internal Links - try multiple possible meta keys
            $rank_math_internal_links = $this->get_rank_math_internal_links($post->ID);
            $row[] = $this->escape_csv_value($rank_math_internal_links);
            
            // Rank Math External Links - try multiple possible meta keys
            $rank_math_external_links = $this->get_rank_math_external_links($post->ID);
            $row[] = $this->escape_csv_value($rank_math_external_links);
            
            // Rank Math Incoming Links - try multiple possible meta keys
            $rank_math_incoming_links = $this->get_rank_math_incoming_links($post->ID);
            $row[] = $this->escape_csv_value($rank_math_incoming_links);
            
            $csv_data[] = $row;
        }
        
        // Convert to CSV string
        $csv_string = '';
        foreach ($csv_data as $row) {
            $csv_string .= implode(',', $row) . "\n";
        }
        
        return $csv_string;
    }
    
    /**
     * Get cached posts from database
     * 
     * Uses WordPress caching to avoid direct database queries.
     * 
     * @since 1.0.0
     * @param string $query The SQL query to execute.
     * @param array $where_values The values used in the query.
     * @return array|false The posts array or false on failure.
     */
    private function get_cached_posts($query, $where_values) {
        global $wpdb;
        
        $cache_key = 'pcer_posts_' . md5($query . serialize($where_values));
        $posts = wp_cache_get($cache_key);
        
        if (false === $posts) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $posts = $wpdb->get_results($query);
            wp_cache_set($cache_key, $posts, '', 300); // Cache for 5 minutes
        }
        
        return $posts;
    }
    
    /**
     * Get cached table existence check
     * 
     * Uses WordPress caching to avoid direct database queries.
     * 
     * @since 1.0.0
     * @param string $table_name The table name to check.
     * @return bool True if table exists, false otherwise.
     */
    private function get_cached_table_exists($table_name) {
        global $wpdb;
        
        $cache_key = 'pcer_table_exists_' . md5($table_name);
        $table_exists = wp_cache_get($cache_key);
        
        if (false === $table_exists) {
            // Use a more WordPress-friendly approach to check table existence
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $result = $wpdb->get_results($wpdb->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema = %s AND table_name = %s", DB_NAME, $table_name));
            $table_exists = !empty($result);
            wp_cache_set($cache_key, $table_exists, '', 3600); // Cache for 1 hour
        }
        
        return $table_exists;
    }
    
    /**
     * Get cached analytics value from database
     * 
     * Uses WordPress caching to avoid direct database queries.
     * 
     * @since 1.0.0
     * @param int $post_id The post ID.
     * @param string $type The type of data (score, keyword, etc.).
     * @param string $query The SQL query to execute.
     * @return mixed The cached value or false on failure.
     */
    private function get_cached_analytics_value($post_id, $type, $query) {
        global $wpdb;
        
        $cache_key = 'pcer_' . $type . '_' . $post_id;
        $value = wp_cache_get($cache_key);
        
        if (false === $value) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $value = $wpdb->get_var($query);
            wp_cache_set($cache_key, $value, '', 1800); // Cache for 30 minutes
        }
        
        return $value;
    }
    
    /**
     * Get cached link value from database
     * 
     * Uses WordPress caching to avoid direct database queries.
     * 
     * @since 1.0.0
     * @param int $post_id The post ID.
     * @param string $type The type of link data.
     * @param string $query The SQL query to execute.
     * @return mixed The cached value or false on failure.
     */
    private function get_cached_link_value($post_id, $type, $query) {
        global $wpdb;
        
        $cache_key = 'pcer_' . $type . '_' . $post_id;
        $value = wp_cache_get($cache_key);
        
        if (false === $value) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $value = $wpdb->get_var($query);
            wp_cache_set($cache_key, $value, '', 1800); // Cache for 30 minutes
        }
        
        return $value;
    }
    
    /**
     * Get cached debug data from database
     * 
     * Uses WordPress caching to avoid direct database queries.
     * 
     * @since 1.0.0
     * @param int $post_id The post ID.
     * @param string $type The type of debug data.
     * @param string $query The SQL query to execute.
     * @return mixed The cached value or false on failure.
     */
    private function get_cached_debug_data($post_id, $type, $query) {
        global $wpdb;
        
        $cache_key = 'pcer_debug_' . $type . '_' . $post_id;
        $value = wp_cache_get($cache_key);
        
        if (false === $value) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $value = $wpdb->get_row($query, ARRAY_A);
            wp_cache_set($cache_key, $value, '', 1800); // Cache for 30 minutes
        }
        
        return $value;
    }
    
    /**
     * Check if a table exists safely
     * 
     * @since 1.0.0
     * @param string $table_name The table name to check.
     * @return bool True if table exists, false otherwise.
     */
    private function table_exists($table_name) {
        global $wpdb;
        // Validate table name format to prevent SQL injection
        if (!preg_match('/^[a-zA-Z0-9_]+$/', str_replace($wpdb->prefix, '', $table_name))) {
            return false;
        }
        
        return $this->get_cached_table_exists($table_name);
    }
    
    /**
     * Get Rank Math score from analytics table with fallbacks
     * 
     * Retrieves the Rank Math SEO score for a given post ID.
     * Tries multiple sources including analytics table and post meta.
     * 
     * @since 1.0.0
     * @param int $post_id The post ID to get the score for.
     * @return string The SEO score or empty string if not found.
     */
    private function get_rank_math_score($post_id) {
        global $wpdb;
        
        // First try the Rank Math analytics table
        $table_name = $wpdb->prefix . 'rank_math_analytics_objects';
        if ($this->table_exists($table_name)) {
            $analytics_table = $table_name;
            $query = $wpdb->prepare("
                SELECT seo_score 
                FROM {$analytics_table} 
                WHERE object_id = %d
            ", $post_id);
            
            $score = $this->get_cached_analytics_value($post_id, 'score', $query);
            
            if ($score !== null && $score !== '') {
                return $score;
            }
        }
        
        // Fallback to post meta
        $score_keys = array(
            'rank_math_seo_score',
            'rank_math_score',
            'rank_math_analytics_score',
            'rank_math_advanced_seo_score'
        );
        
        foreach ($score_keys as $key) {
            $score = get_post_meta($post_id, $key, true);
            if (!empty($score)) {
                return $score;
            }
        }
        
        return '';
    }
    
    /**
     * Get Rank Math keyword from analytics table with fallbacks
     * 
     * Retrieves the main focus keyword for a given post ID.
     * Tries multiple sources including analytics table and post meta.
     * 
     * @since 1.0.0
     * @param int $post_id The post ID to get the keyword for.
     * @return string The main keyword or empty string if not found.
     */
    private function get_rank_math_keyword($post_id) {
        global $wpdb;
        
        // First try the Rank Math analytics table
        $table_name = $wpdb->prefix . 'rank_math_analytics_objects';
        if ($this->table_exists($table_name)) {
            $analytics_table = $table_name;
            $query = $wpdb->prepare("
                SELECT primary_key 
                FROM {$analytics_table} 
                WHERE object_id = %d
            ", $post_id);
            
            $keyword = $this->get_cached_analytics_value($post_id, 'keyword', $query);
            
            if (!empty($keyword)) {
                // If it contains commas, take only the first keyword
                $keywords = explode(',', $keyword);
                return trim($keywords[0]);
            }
        }
        
        // Fallback to post meta
        $keyword_keys = array(
            'rank_math_focus_keyword',
            'rank_math_keyword',
            'rank_math_primary_keyword',
            'rank_math_target_keyword'
        );
        
        foreach ($keyword_keys as $key) {
            $keyword = get_post_meta($post_id, $key, true);
            if (!empty($keyword)) {
                // If it contains commas, take only the first keyword
                $keywords = explode(',', $keyword);
                return trim($keywords[0]);
            }
        }
        
        return '';
    }
    
    /**
     * Get Rank Math additional keywords from analytics table with fallbacks
     * 
     * Retrieves additional keywords (excluding the main keyword) for a given post ID.
     * Tries multiple sources including analytics table and post meta.
     * 
     * @since 1.0.0
     * @param int $post_id The post ID to get the keywords for.
     * @return string The additional keywords or empty string if not found.
     */
    private function get_rank_math_additional_keywords($post_id) {
        global $wpdb;
        
        // First try the Rank Math analytics table
        $table_name = $wpdb->prefix . 'rank_math_analytics_objects';
        if ($this->table_exists($table_name)) {
            $analytics_table = $table_name;
            $query = $wpdb->prepare("
                SELECT primary_key 
                FROM {$analytics_table} 
                WHERE object_id = %d
            ", $post_id);
            
            $full_keyword = $this->get_cached_analytics_value($post_id, 'additional_keywords', $query);
            
            if (!empty($full_keyword)) {
                $keywords = explode(',', $full_keyword);
                if (count($keywords) > 1) {
                    // Remove the first keyword and return the rest
                    array_shift($keywords);
                    return trim(implode(', ', $keywords));
                }
            }
        }
        
        // Fallback to post meta
        $keyword_keys = array(
            'rank_math_focus_keyword',
            'rank_math_keyword',
            'rank_math_primary_keyword',
            'rank_math_target_keyword'
        );
        
        foreach ($keyword_keys as $key) {
            $full_keyword = get_post_meta($post_id, $key, true);
            if (!empty($full_keyword)) {
                $keywords = explode(',', $full_keyword);
                if (count($keywords) > 1) {
                    // Remove the first keyword and return the rest
                    array_shift($keywords);
                    return trim(implode(', ', $keywords));
                }
            }
        }
        
        // Try to get additional keywords from Rank Math secondary field
        $additional_keywords = get_post_meta($post_id, 'rank_math_focus_keyword_secondary', true);
        if (!empty($additional_keywords)) {
            return $additional_keywords;
        }
        
        return '';
    }
    
    /**
     * Get Rank Math schema type from analytics table with fallbacks
     * 
     * Retrieves the structured data schema type for a given post ID.
     * Tries multiple sources including analytics table and post meta.
     * 
     * @since 1.0.0
     * @param int $post_id The post ID to get the schema for.
     * @return string The schema type or empty string if not found.
     */
    private function get_rank_math_schema($post_id) {
        global $wpdb;
        
        // First try the Rank Math analytics table
        $table_name = $wpdb->prefix . 'rank_math_analytics_objects';
        if ($this->table_exists($table_name)) {
            $analytics_table = $table_name;
            $query = $wpdb->prepare("
                SELECT schemas_in_use 
                FROM {$analytics_table} 
                WHERE object_id = %d
            ", $post_id);
            
            $schema = $this->get_cached_analytics_value($post_id, 'schema', $query);
            
            if (!empty($schema)) {
                return $this->format_schema_type($schema);
            }
        }
        
        // Fallback to post meta if not found in analytics table
        $schema_type = get_post_meta($post_id, 'rank_math_rich_snippet_type', true);
        if (!empty($schema_type)) {
            return $this->format_schema_type($schema_type);
        }
        
        // Try alternative meta keys
        $schema_keys = array(
            'rank_math_schema_type',
            'rank_math_structured_data_type'
        );
        
        foreach ($schema_keys as $key) {
            $schema = get_post_meta($post_id, $key, true);
            if (!empty($schema)) {
                return $this->format_schema_type($schema);
            }
        }
        
        // Only use Rank Math options if they are explicitly set and not 'off'
        $rank_math_options = get_option('rank_math_options', array());
        $post_type = get_post_type($post_id);
        
        if ($post_type === 'post' && !empty($rank_math_options['titles']['pt_post_default_rich_snippet']) && $rank_math_options['titles']['pt_post_default_rich_snippet'] !== 'off') {
            return $this->format_schema_type($rank_math_options['titles']['pt_post_default_rich_snippet']);
        }
        
        if ($post_type === 'page' && !empty($rank_math_options['titles']['pt_page_default_rich_snippet']) && $rank_math_options['titles']['pt_page_default_rich_snippet'] !== 'off') {
            return $this->format_schema_type($rank_math_options['titles']['pt_page_default_rich_snippet']);
        }
        
        // Try general default only if it's not 'off'
        if (!empty($rank_math_options['titles']['pt_default_rich_snippet']) && $rank_math_options['titles']['pt_default_rich_snippet'] !== 'off') {
            return $this->format_schema_type($rank_math_options['titles']['pt_default_rich_snippet']);
        }
        
        // Return empty if no schema is configured
        return '';
    }
    
    /**
     * Format schema type for display
     * 
     * Converts schema type identifiers to human-readable names.
     * 
     * @since 1.0.0
     * @param string $schema_type The raw schema type identifier.
     * @return string The formatted schema type name.
     */
    private function format_schema_type($schema_type) {
        // Map common schema types to display names
        $schema_map = array(
            'article' => 'Article',
            'webpage' => 'WebPage',
            'blogposting' => 'Blog Posting',
            'newsarticle' => 'News Article',
            'product' => 'Product',
            'review' => 'Review',
            'localbusiness' => 'Local Business',
            'organization' => 'Organization',
            'person' => 'Person',
            'event' => 'Event',
            'recipe' => 'Recipe',
            'video' => 'Video',
            'book' => 'Book',
            'course' => 'Course',
            'faq' => 'FAQ',
            'howto' => 'How To',
            'jobposting' => 'Job Posting',
            'movie' => 'Movie',
            'music' => 'Music',
            'restaurant' => 'Restaurant',
            'service' => 'Service',
            'softwareapplication' => 'Software Application',
            'website' => 'Website'
        );
        
        $schema_type = strtolower($schema_type);
        
        // Check if we have a mapping for this type
        if (isset($schema_map[$schema_type])) {
            return $schema_map[$schema_type];
        }
        
        // Return original if no mapping found
        return ucfirst($schema_type);
    }
    
    /**
     * Get Rank Math internal links from database table with fallbacks
     * 
     * Retrieves the count of internal links for a given post ID.
     * Tries multiple sources including internal meta table and post meta.
     * 
     * @since 1.0.0
     * @param int $post_id The post ID to get the internal links for.
     * @return string The internal link count or empty string if not found.
     */
    private function get_rank_math_internal_links($post_id) {
        global $wpdb;
        
        // First try the Rank Math internal meta table
        $table_name = $wpdb->prefix . 'rank_math_internal_meta';
        if ($this->table_exists($table_name)) {
            $internal_meta_table = $table_name;
            $query = $wpdb->prepare("
                SELECT internal_link_count 
                FROM {$internal_meta_table} 
                WHERE object_id = %d
            ", $post_id);
            
            $internal_links = $this->get_cached_link_value($post_id, 'internal_links', $query);
            
            if ($internal_links !== null && $internal_links !== '') {
                return $internal_links;
            }
        }
        
        // Fallback to post meta
        $link_keys = array(
            'rank_math_internal_links',
            'rank_math_internal_link_count',
            'rank_math_internal_links_count',
            'rank_math_inlinks',
            'rank_math_internal_backlinks'
        );
        
        foreach ($link_keys as $key) {
            $links = get_post_meta($post_id, $key, true);
            if ($links !== null && $links !== '') {
                return $links;
            }
        }
        
        return 0;
    }
    
    /**
     * Get Rank Math external links from database table with fallbacks
     * 
     * Retrieves the count of external links for a given post ID.
     * Tries multiple sources including internal meta table and post meta.
     * 
     * @since 1.0.0
     * @param int $post_id The post ID to get the external links for.
     * @return string The external link count or empty string if not found.
     */
    private function get_rank_math_external_links($post_id) {
        global $wpdb;
        
        // First try the Rank Math internal meta table
        $table_name = $wpdb->prefix . 'rank_math_internal_meta';
        if ($this->table_exists($table_name)) {
            $internal_meta_table = $table_name;
            $query = $wpdb->prepare("
                SELECT external_link_count 
                FROM {$internal_meta_table} 
                WHERE object_id = %d
            ", $post_id);
            
            $external_links = $this->get_cached_link_value($post_id, 'external_links', $query);
            
            if ($external_links !== null && $external_links !== '') {
                return $external_links;
            }
        }
        
        // Fallback to post meta
        $link_keys = array(
            'rank_math_external_links',
            'rank_math_external_link_count',
            'rank_math_external_links_count',
            'rank_math_outlinks',
            'rank_math_backlinks'
        );
        
        foreach ($link_keys as $key) {
            $links = get_post_meta($post_id, $key, true);
            if ($links !== null && $links !== '') {
                return $links;
            }
        }
        
        return 0;
    }
    
    /**
     * Get Rank Math incoming links from database table with fallbacks
     * 
     * Retrieves the count of incoming links for a given post ID.
     * Tries multiple sources including internal meta table and post meta.
     * 
     * @since 1.0.0
     * @param int $post_id The post ID to get the incoming links for.
     * @return string The incoming link count or empty string if not found.
     */
    private function get_rank_math_incoming_links($post_id) {
        global $wpdb;
        
        // First try the Rank Math internal meta table
        $table_name = $wpdb->prefix . 'rank_math_internal_meta';
        if ($this->table_exists($table_name)) {
            $internal_meta_table = $table_name;
            $query = $wpdb->prepare("
                SELECT incoming_link_count 
                FROM {$internal_meta_table} 
                WHERE object_id = %d
            ", $post_id);
            
            $incoming_links = $this->get_cached_link_value($post_id, 'incoming_links', $query);
            
            if ($incoming_links !== null && $incoming_links !== '') {
                return $incoming_links;
            }
        }
        
        // Fallback to post meta
        $link_keys = array(
            'rank_math_incoming_links',
            'rank_math_incoming_link_count',
            'rank_math_incoming_links_count',
            'rank_math_backlinks',
            'rank_math_internal_backlinks'
        );
        
        foreach ($link_keys as $key) {
            $links = get_post_meta($post_id, $key, true);
            if ($links !== null && $links !== '') {
                return $links;
            }
        }
        
        return 0;
    }
    
    /**
     * Debug method to check what Rank Math data is available
     * 
     * Retrieves comprehensive debug information about Rank Math data
     * for a given post ID. Used for troubleshooting and development.
     * 
     * @since 1.0.0
     * @param int $post_id The post ID to debug.
     * @return array Debug data array containing all available Rank Math information.
     */
    private function debug_rank_math_data($post_id) {
        global $wpdb;
        $debug_data = array();
        
        // Check Rank Math internal meta table
        $table_name = $wpdb->prefix . 'rank_math_internal_meta';
        if ($this->table_exists($table_name)) {
            $internal_meta_table = $table_name;
            $query = $wpdb->prepare("
                SELECT internal_link_count, external_link_count, incoming_link_count 
                FROM {$internal_meta_table} 
                WHERE object_id = %d
            ", $post_id);
            
            $link_data = $this->get_cached_debug_data($post_id, 'links', $query);
            
            if ($link_data) {
                $debug_data['INTERNAL_META_TABLE'] = $link_data;
            }
        }
        
        // Check Rank Math analytics table
        $analytics_table = $wpdb->prefix . 'rank_math_analytics_objects';
        if ($this->table_exists($analytics_table)) {
            $analytics_table_name = $analytics_table;
            $analytics_query = $wpdb->prepare("
                SELECT id, created, title, page, object_type, object_subtype, object_id, primary_key, seo_score, schemas_in_use 
                FROM {$analytics_table_name} 
                WHERE object_id = %d
            ", $post_id);
            
            $analytics_data = $this->get_cached_debug_data($post_id, 'analytics', $analytics_query);
            
            if ($analytics_data) {
                $debug_data['ANALYTICS_TABLE'] = $analytics_data;
            }
        }
        
        // Primary Rank Math meta keys (most important)
        $primary_keys = array(
            'rank_math_seo_score',
            'rank_math_focus_keyword',
            'rank_math_rich_snippet_type',
            'rank_math_internal_links',
            'rank_math_external_links',
            'rank_math_incoming_links'
        );
        
        // Secondary/alternative meta keys
        $secondary_keys = array(
            'rank_math_score',
            'rank_math_analytics_score',
            'rank_math_keyword',
            'rank_math_primary_keyword',
            'rank_math_target_keyword',
            'rank_math_schema_type',
            'rank_math_structured_data_type',
            'rank_math_rich_snippet',
            'rank_math_schema',
            'rank_math_schema_metadata',
            'rank_math_inlinks',
            'rank_math_outlinks',
            'rank_math_internal_link_count',
            'rank_math_external_link_count',
            'rank_math_incoming_link_count',
            'rank_math_backlinks',
            'rank_math_internal_backlinks',
            'rank_math_internal_links_count',
            'rank_math_external_links_count',
            'rank_math_incoming_links_count'
        );
        
        // Check primary keys first
        foreach ($primary_keys as $key) {
            $value = get_post_meta($post_id, $key, true);
            if (!empty($value)) {
                $debug_data['PRIMARY_' . $key] = $value;
            }
        }
        
        // Check secondary keys
        foreach ($secondary_keys as $key) {
            $value = get_post_meta($post_id, $key, true);
            if (!empty($value)) {
                $debug_data['SECONDARY_' . $key] = $value;
            }
        }
        
        // Also check Rank Math options
        $rank_math_options = get_option('rank_math_options', array());
        if (!empty($rank_math_options)) {
            $debug_data['RANK_MATH_OPTIONS'] = $rank_math_options;
        }
        
        return $debug_data;
    }
    
    /**
     * Clear cache for a specific post
     * 
     * Clears all cached data for a given post ID when the post is updated or deleted.
     * 
     * @since 1.0.0
     * @param int $post_id The post ID to clear cache for.
     */
    public function clear_post_cache($post_id) {
        // Clear all cached data for this post using the new cache key patterns
        $cache_keys = array(
            'pcer_score_' . $post_id,
            'pcer_keyword_' . $post_id,
            'pcer_additional_keywords_' . $post_id,
            'pcer_schema_' . $post_id,
            'pcer_internal_links_' . $post_id,
            'pcer_external_links_' . $post_id,
            'pcer_incoming_links_' . $post_id,
            'pcer_debug_links_' . $post_id,
            'pcer_debug_analytics_' . $post_id
        );
        
        foreach ($cache_keys as $cache_key) {
            wp_cache_delete($cache_key);
        }
        
        // Clear the posts query cache as well
        wp_cache_delete_group('pcer_posts_');
    }
    
    /**
     * Escape CSV value
     * 
     * Properly escapes a value for CSV format, handling quotes, commas, and newlines.
     * 
     * @since 1.0.0
     * @param mixed $value The value to escape for CSV.
     * @return string The escaped CSV value.
     */
    private function escape_csv_value($value) {
        // Handle numeric 0 values - they should show as "0" not empty
        if ($value === 0 || $value === '0') {
            return '0';
        }
        
        if (empty($value)) {
            return '';
        }
        
        // Remove any existing quotes and escape internal quotes
        $value = str_replace('"', '""', $value);
        
        // Wrap in quotes if contains comma, newline, or quote
        if (strpos($value, ',') !== false || strpos($value, "\n") !== false || strpos($value, '"') !== false) {
            $value = '"' . $value . '"';
        }
        
        return $value;
    }
    }
}

// Initialize the plugin only if class exists
if (class_exists('Posts_CSV_Export_Rank_Math')) {
    new Posts_CSV_Export_Rank_Math();
}
