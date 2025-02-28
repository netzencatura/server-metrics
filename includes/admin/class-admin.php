<?php
/**
 * Admin class
 *
 * @package Server_Metrics
 */

// Zabránění přímému přístupu
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin class
 */
class Server_Metrics_Admin {
    /**
     * Main plugin instance
     *
     * @var Server_Metrics
     */
    private $plugin;
    
    /**
     * Dashboard instance
     *
     * @var Server_Metrics_Dashboard
     */
    private $dashboard;
    
    /**
     * Servers instance
     * 
     * @var Server_Metrics_Servers
     */
    private $servers;
    
    /**
     * Logs instance
     * 
     * @var Server_Metrics_Logs
     */
    private $logs;
    
    /**
     * Constructor
     *
     * @param Server_Metrics $plugin Main plugin instance
     */
    public function __construct($plugin) {
        $this->plugin = $plugin;
        
        // Initialize sub-modules
        $this->dashboard = new Server_Metrics_Dashboard($plugin);
        $this->servers = new Server_Metrics_Servers($plugin);
        $this->logs = new Server_Metrics_Logs($plugin);
        
        // Setup menu and assets
        add_action('admin_menu', [$this, 'add_menu_pages']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        
        // Setup AJAX handlers
        $this->setup_ajax_handlers();
        
        // Cron for cleaning old data
        if (!wp_next_scheduled('server_metrics_cleanup')) {
            wp_schedule_event(time(), 'daily', 'server_metrics_cleanup');
        }
        add_action('server_metrics_cleanup', [$this, 'cleanup_old_data']);
    }
    
    /**
     * Setup AJAX handlers
     */
    private function setup_ajax_handlers() {
        add_action('wp_ajax_get_website_metrics', [$this, 'ajax_get_website_metrics']);
        add_action('wp_ajax_get_sparkline_data', [$this, 'ajax_get_sparkline_data']);
        add_action('wp_ajax_get_average_historical_data', [$this, 'ajax_get_average_historical_data']);
        add_action('wp_ajax_get_website_metrics_live', [$this, 'ajax_get_website_metrics_live']);
    }
    
    /**
     * Add admin menu pages
     */
    public function add_menu_pages() {
        add_menu_page(
            __('Server Metrics', 'server-metrics'), 
            __('Server Metrics', 'server-metrics'), 
            'manage_options', 
            'server-metrics', 
            [$this->dashboard, 'display_page'], 
            'dashicons-chart-area', 
            30
        );
        
        add_submenu_page(
            'server-metrics',
            __('Servers Overview', 'server-metrics'),
            __('Servers', 'server-metrics'),
            'manage_options',
            'server-metrics-servers',
            [$this->servers, 'display_page']
        );
        
        // Logs page (new)
        add_submenu_page(
            'server-metrics',
            __('Logs', 'server-metrics'),
            __('Logs', 'server-metrics'),
            'manage_options',
            'server-metrics-logs',
            [$this->logs, 'display_page']
        );
        
        add_submenu_page(
            'server-metrics',
            __('Settings', 'server-metrics'),
            __('Settings', 'server-metrics'),
            'manage_options',
            'server-metrics-settings',
            [$this, 'display_settings_page']
        );
    }
    
    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue_assets($hook) {
        // Enqueue only on plugin pages
        if (strpos($hook, 'server-metrics') === false) {
            return;
        }
        
        // Get current admin page
        $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        
        // Common CSS
        wp_enqueue_style(
            'server-metrics-admin',
            SERVER_METRICS_PLUGIN_URL . 'assets/css/admin.min.css',
            [],
            SERVER_METRICS_VERSION
        );
        
        // JS for charts
        wp_enqueue_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js',
            [],
            '3.9.1'
        );
        
        // Common JS
        wp_enqueue_script(
            'server-metrics-admin',
            SERVER_METRICS_PLUGIN_URL . 'assets/js/admin.min.js',
            ['jquery', 'chart-js'],
            SERVER_METRICS_VERSION,
            true
        );
        
        // Page specific JS
        switch ($page) {
            case 'server-metrics':
                wp_enqueue_script(
                    'server-metrics-dashboard',
                    SERVER_METRICS_PLUGIN_URL . 'assets/js/dashboard.min.js',
                    ['jquery', 'chart-js', 'server-metrics-admin'],
                    SERVER_METRICS_VERSION,
                    true
                );
                break;
                
            case 'server-metrics-servers':
                wp_enqueue_script(
                    'server-metrics-servers',
                    SERVER_METRICS_PLUGIN_URL . 'assets/js/servers.min.js',
                    ['jquery', 'server-metrics-admin'],
                    SERVER_METRICS_VERSION,
                    true
                );
                break;
                
            case 'server-metrics-logs':
                wp_enqueue_script(
                    'server-metrics-logs',
                    SERVER_METRICS_PLUGIN_URL . 'assets/js/logs.min.js',
                    ['jquery', 'server-metrics-admin'],
                    SERVER_METRICS_VERSION,
                    true
                );
                break;
        }
        
// Data for JS
wp_localize_script('server-metrics-admin', 'serverMetricsData', [
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('server_metrics_nonce'),
    'strings' => [
        'websiteDetail' => __('Website Detail', 'server-metrics'),
        'failedToLoadData' => __('Failed to load data', 'server-metrics'),
        'serverError' => __('Server communication error occurred', 'server-metrics'),
        'cpuUsage' => __('CPU Usage (%)', 'server-metrics'),
        'memoryUsage' => __('Memory Usage (%)', 'server-metrics'),
        'ioRead' => __('I/O Read (bytes/s)', 'server-metrics'),
        'ioWrite' => __('I/O Write (bytes/s)', 'server-metrics'),
        'time' => __('Time', 'server-metrics'),
        'liveUpdates' => __('Live Updates', 'server-metrics'),
        'pauseUpdates' => __('Pause Updates', 'server-metrics'),
        'resumeUpdates' => __('Resume Updates', 'server-metrics'),
        // Přidané nové řetězce
        'last30min' => __('Last 30 minutes', 'server-metrics'),
        'lastHour' => __('Last hour', 'server-metrics'),
        'last6Hours' => __('Last 6 hours', 'server-metrics'),
        'last12Hours' => __('Last 12 hours', 'server-metrics'),
        'lastDay' => __('Last day', 'server-metrics'),
        'lastWeek' => __('Last week', 'server-metrics'),
        'lastMonth' => __('Last month', 'server-metrics')
    ]
]);
}   
    /**
     * Display settings page
     */
    public function display_settings_page() {
        // Zpracování formuláře
        if (isset($_POST['server_metrics_settings_submit'])) {
            check_admin_referer('server_metrics_settings');
            
            $retention_days = intval($_POST['retention_days']);
            update_option('server_metrics_retention_days', $retention_days);
            
            $delete_on_deactivate = isset($_POST['delete_on_deactivate']) ? 1 : 0;
            update_option('server_metrics_delete_data', $delete_on_deactivate);
            
            echo '<div class="notice notice-success"><p>' . __('Settings have been saved.', 'server-metrics') . '</p></div>';
        }
        
        // Získání nastavení
        $retention_days = get_option('server_metrics_retention_days', 30);
        $delete_on_deactivate = get_option('server_metrics_delete_data', 0);
        
        // Zobrazení šablony
        include SERVER_METRICS_PLUGIN_DIR . 'templates/admin/settings.php';
    }
    
    /**
     * AJAX handler for website metrics
     */
    public function ajax_get_website_metrics() {
        check_ajax_referer('server_metrics_nonce', 'nonce');
        
        $uuid = isset($_GET['uuid']) ? sanitize_text_field($_GET['uuid']) : '';
        $period = isset($_GET['period']) ? sanitize_text_field($_GET['period']) : 'day';
        $time_range = isset($_GET['time_range']) ? sanitize_text_field($_GET['time_range']) : '15min';
        
        if (empty($uuid)) {
            wp_send_json_error(__('Website UUID is missing', 'server-metrics'));
        }
        
        $metrics = $this->plugin->db_handler->get_website_metrics($uuid, $period, $time_range);
        wp_send_json_success($metrics);
    }
    
/**
 * AJAX handler for sparkline data
 */
public function ajax_get_sparkline_data() {
    check_ajax_referer('server_metrics_nonce', 'nonce');
    
    $uuid = isset($_GET['uuid']) ? sanitize_text_field($_GET['uuid']) : '';
    $period = isset($_GET['period']) ? sanitize_text_field($_GET['period']) : 'day';
    
    if (empty($uuid)) {
        wp_send_json_error(__('Website UUID is missing', 'server-metrics'));
    }
    
    $data = $this->plugin->db_handler->get_sparkline_data($uuid, $period);
    wp_send_json_success($data);
}
    
    /**
     * AJAX handler for average historical data
     */
    public function ajax_get_average_historical_data() {
        check_ajax_referer('server_metrics_nonce', 'nonce');
        
        $period = isset($_GET['period']) ? sanitize_text_field($_GET['period']) : 'day';
        $data = $this->plugin->db_handler->get_average_historical_data();
        
        if (empty($data)) {
            wp_send_json_error('No data found');
        } else {
            wp_send_json_success($data);
        }
    }
    
    /**
     * AJAX handler for live metrics data
     */
    public function ajax_get_website_metrics_live() {
        check_ajax_referer('server_metrics_nonce', 'nonce');
        
        $uuid = isset($_GET['uuid']) ? sanitize_text_field($_GET['uuid']) : '';
        $lastTimestamp = isset($_GET['last_timestamp']) ? sanitize_text_field($_GET['last_timestamp']) : '';
        
        if (empty($uuid)) {
            wp_send_json_error(__('Website UUID is missing', 'server-metrics'));
        }
        
        $metrics = $this->plugin->db_handler->get_website_metrics_live($uuid, $lastTimestamp);
        wp_send_json_success($metrics);
    }
    
    /**
     * Clean up old data
     */
    public function cleanup_old_data() {
        $retention_days = get_option('server_metrics_retention_days', 30);
        $this->plugin->db_handler->cleanup_old_data($retention_days);
    }
    
    /**
     * Format bytes to human readable format
     *
     * @param int $bytes Bytes to format
     * @param int $precision Decimal precision
     * @return string Formatted bytes
     */
    public function format_bytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}