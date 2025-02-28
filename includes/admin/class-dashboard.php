<?php
/**
 * Dashboard class
 *
 * @package Server_Metrics
 */

// Zabránění přímému přístupu
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Dashboard class
 */
class Server_Metrics_Dashboard {
    /**
     * Main plugin instance
     *
     * @var Server_Metrics
     */
    private $plugin;
    
    /**
     * Constructor
     *
     * @param Server_Metrics $plugin Main plugin instance
     */
    public function __construct($plugin) {
        $this->plugin = $plugin;
    }
    
    /**
     * Display dashboard page
     */
    public function display_page() {
        // Get data
        $websites = $this->plugin->db_handler->get_latest_metrics();
        
        // Display template
        include SERVER_METRICS_PLUGIN_DIR . 'templates/admin/dashboard.php';
    }
    
    /**
     * Format bytes to human readable format
     *
     * @param int $bytes Bytes to format
     * @param int $precision Decimal precision
     * @return string Formatted bytes
     */
    public function format_bytes($bytes, $precision = 2) {
        return $this->plugin->admin->format_bytes($bytes, $precision);
    }
}