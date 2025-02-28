<?php
/**
 * Servers class
 *
 * @package Server_Metrics
 */

// Zabránění přímému přístupu
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Servers class
 */
class Server_Metrics_Servers {
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
     * Display servers page
     */
    public function display_page() {
        // Get data
        $servers = $this->plugin->db_handler->get_servers();
        
        // Display template
        include SERVER_METRICS_PLUGIN_DIR . 'templates/admin/servers.php';
    }
}