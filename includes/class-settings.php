<?php
/**
 * Settings class
 *
 * @package Server_Metrics
 */

// Zabránění přímému přístupu
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings class
 */
class Server_Metrics_Settings {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Registrace nastavení
        add_action('admin_init', [$this, 'register_settings']);
        
        // Inicializace API tokenu při aktivaci
        if (!get_option('server_metrics_api_token')) {
            $this->generate_new_api_token();
        }
    }
    
    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('server_metrics_settings', 'server_metrics_retention_days', [
            'type' => 'integer',
            'default' => 30,
            'sanitize_callback' => [$this, 'sanitize_integer']
        ]);
        
        register_setting('server_metrics_settings', 'server_metrics_delete_data', [
            'type' => 'boolean',
            'default' => false,
            'sanitize_callback' => [$this, 'sanitize_checkbox']
        ]);

        register_setting('server_metrics_settings', 'server_metrics_api_token', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field'
        ]);
    }
    
    /**
     * Generate new API token
     *
     * @return string|bool New token or false on failure
     */
    public function generate_new_api_token() {
        try {
            $token = bin2hex(random_bytes(32));
            update_option('server_metrics_api_token', $token);
            return $token;
        } catch (Exception $e) {
            error_log('Server Metrics: Error generating API token - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get API token
     *
     * @return string API token
     */
    public function get_api_token() {
        return get_option('server_metrics_api_token');
    }
    
    /**
     * Sanitize integer
     *
     * @param mixed $value Value to sanitize
     * @return int Sanitized value
     */
    public function sanitize_integer($value) {
        return intval($value);
    }
    
    /**
     * Sanitize checkbox
     *
     * @param mixed $value Value to sanitize
     * @return bool Sanitized value
     */
    public function sanitize_checkbox($value) {
        return (bool) $value;
    }
    
    /**
     * Get setting
     *
     * @param string $key Setting key
     * @param mixed $default Default value
     * @return mixed Setting value
     */
    public function get_setting($key, $default = null) {
        return get_option($key, $default);
    }

    /**
     * Regenerate API token
     *
     * @return string|bool New token or false on failure
     */
    public function regenerate_api_token() {
        $old_token = $this->get_api_token();
        $new_token = $this->generate_new_api_token();
        
        if ($new_token) {
            error_log('Server Metrics: API token regenerated. Old token: ' . substr($old_token, 0, 8) . '...');
            return $new_token;
        }
        
        return false;
    }
}