<?php
/**
 * Main plugin class
 *
 * @package Server_Metrics
 */

// Zabránění přímému přístupu
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main plugin class
 */
class Server_Metrics {
    /**
     * Plugin instance
     *
     * @var Server_Metrics
     */
    private static $instance = null;
    
    /**
     * Database handler instance
     *
     * @var Server_Metrics_DB_Handler
     */
    public $db_handler;
    
    /**
     * REST API controller instance
     *
     * @var Server_Metrics_REST_Controller
     */
    public $rest_controller;
    
    /**
     * Data processor instance
     *
     * @var Server_Metrics_Data_Processor
     */
    public $data_processor;
    
    /**
     * Settings instance
     *
     * @var Server_Metrics_Settings
     */
    public $settings;
    
    /**
     * Admin instance
     *
     * @var Server_Metrics_Admin
     */
    public $admin;
    
    /**
     * Get plugin instance
     *
     * @return Server_Metrics
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Inicializace překladu
        add_action('plugins_loaded', array($this, 'load_plugin_textdomain'));
        
        // Načtení hlavních tříd
        $this->load_dependencies();
        
        // Inicializace tříd
        $this->init_classes();
        
        // Aktivace a deaktivace hooku
        register_activation_hook(SERVER_METRICS_PLUGIN_FILE, array($this->db_handler, 'create_tables'));
        register_deactivation_hook(SERVER_METRICS_PLUGIN_FILE, array($this->db_handler, 'maybe_delete_data'));
        
        // Inicializace REST API
        add_action('rest_api_init', array($this->rest_controller, 'register_routes'));
    }
    
    /**
     * Load dependencies
     */
    private function load_dependencies() {
        // Data
        require_once SERVER_METRICS_PLUGIN_DIR . 'includes/data/class-db-handler.php';
        require_once SERVER_METRICS_PLUGIN_DIR . 'includes/data/class-data-processor.php';
        
        // API
        require_once SERVER_METRICS_PLUGIN_DIR . 'includes/api/class-rest-controller.php';
        
        // Settings
        require_once SERVER_METRICS_PLUGIN_DIR . 'includes/class-settings.php';
        
        // Admin
        require_once SERVER_METRICS_PLUGIN_DIR . 'includes/admin/class-admin.php';
        require_once SERVER_METRICS_PLUGIN_DIR . 'includes/admin/class-dashboard.php';
        require_once SERVER_METRICS_PLUGIN_DIR . 'includes/admin/class-servers.php';
        require_once SERVER_METRICS_PLUGIN_DIR . 'includes/admin/class-logs.php';
    }
    
    /**
     * Initialize classes
     */
    private function init_classes() {
        $this->db_handler = new Server_Metrics_DB_Handler();
        $this->rest_controller = new Server_Metrics_REST_Controller();
        $this->data_processor = new Server_Metrics_Data_Processor();
        $this->settings = new Server_Metrics_Settings();
        $this->admin = new Server_Metrics_Admin($this);
    }
    
    /**
     * Load text domain for translations
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain('server-metrics', false, dirname(plugin_basename(SERVER_METRICS_PLUGIN_FILE)) . '/languages');
    }
}