<?php
/**
 * REST API controller class
 *
 * @package Server_Metrics
 */

// Zabránění přímému přístupu
if (!defined('ABSPATH')) {
    exit;
}

/**
 * REST API controller class
 */
class Server_Metrics_REST_Controller {
    
    /**
     * Register REST routes
     */
    public function register_routes() {
        register_rest_route('server-metrics/v1', '/collect', [
            'methods' => 'POST',
            'callback' => [$this, 'collect_metrics'],
            'permission_callback' => [$this, 'check_permission']
        ]);
        
        register_rest_route('server-metrics/v1', '/websites', [
            'methods' => 'GET',
            'callback' => [$this, 'get_websites'],
            'permission_callback' => [$this, 'check_admin_permission']
        ]);
        
        register_rest_route('server-metrics/v1', '/website/(?P<uuid>[a-zA-Z0-9-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_website_metrics'],
            'permission_callback' => [$this, 'check_admin_permission'],
            'args' => [
                'uuid' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_string($param) && !empty($param);
                    }
                ]
            ]
        ]);
        
        // Nové API endpointy pro logy
        register_rest_route('server-metrics/v1', '/logs', [
            'methods' => 'GET',
            'callback' => [$this, 'get_logs'],
            'permission_callback' => [$this, 'check_admin_permission']
        ]);
    }
    
    /**
     * Check API permission
     *
     * @param WP_REST_Request $request REST request
     * @return bool Whether user has permission
     */
    public function check_permission($request) {
        $auth_header = $request->get_header('X-API-Key');
        $valid_token = server_metrics()->settings->get_api_token();
        
        if (empty($auth_header) || empty($valid_token)) {
            error_log('Server Metrics: Missing or empty API token');
            return false;
        }
        
        $result = hash_equals($valid_token, $auth_header);
        if (!$result) {
            error_log('Server Metrics: Invalid API token provided');
        }
        
        return $result;
    }
    
    /**
     * Check admin permission
     *
     * @return bool Whether user has permission
     */
    public function check_admin_permission() {
        return current_user_can('manage_options');
    }
    
    /**
     * Collect metrics endpoint
     *
     * @param WP_REST_Request $request REST request
     * @return WP_REST_Response|WP_Error Response or error
     */
    public function collect_metrics($request) {
        // Ověření autorizace už proběhlo v check_permission
        $params = $request->get_params();
        
        // Logování pro debugging
        error_log('Server Metrics: Received metrics data from ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown IP'));
        
        // Pokud data přicházejí jako pole
        if (is_array($params) && isset($params[0]['container_metrics'])) {
            $container = $params[0]['container_metrics'];
            
            // Doplnění serveru, pokud chybí
            if (!isset($container['server'])) {
                $container['server'] = $_SERVER['SERVER_NAME'] ?? gethostname();
            }
            
            $result = server_metrics()->data_processor->process_metrics($container);
            
            if ($result) {
                return new WP_REST_Response(['status' => 'success'], 200);
            } else {
                error_log('Server Metrics: Failed to save metrics data');
                return new WP_Error('db_error', 'Failed to save data', ['status' => 500]);
            }
        }
        // Původní formát s container_metrics na nejvyšší úrovni
        else if (isset($params['container_metrics'])) {
            $container = $params['container_metrics'];
            
            // Přidáme server, pokud chybí
            if (!isset($container['server']) && isset($params['server'])) {
                $container['server'] = $params['server'];
            } else if (!isset($container['server'])) {
                $container['server'] = $_SERVER['SERVER_NAME'] ?? gethostname();
            }
            
            $result = server_metrics()->data_processor->process_metrics($container);
            
            if ($result) {
                return new WP_REST_Response(['status' => 'success'], 200);
            } else {
                error_log('Server Metrics: Failed to save metrics data');
                return new WP_Error('db_error', 'Failed to save data', ['status' => 500]);
            }
        }
        // Kontrola pro starší formát dat nebo jiné zdroje
        else if (isset($params['uuid']) && isset($params['domain'])) {
            // Doplnění serveru, pokud chybí
            if (!isset($params['server'])) {
                $params['server'] = $_SERVER['SERVER_NAME'] ?? gethostname();
            }
            
            $result = server_metrics()->data_processor->process_metrics($params);
            
            if ($result) {
                return new WP_REST_Response(['status' => 'success'], 200);
            } else {
                error_log('Server Metrics: Failed to save metrics data');
                return new WP_Error('db_error', 'Failed to save data', ['status' => 500]);
            }
        }
        
        error_log('Server Metrics: Missing required fields in request');
        return new WP_Error('missing_fields', __('Missing required fields: uuid and domain', 'server-metrics'), ['status' => 400]);
    }
    
    /**
     * Get websites endpoint
     *
     * @param WP_REST_Request $request REST request
     * @return WP_REST_Response Response
     */
    public function get_websites($request) {
        $websites = server_metrics()->db_handler->get_latest_metrics();
        return new WP_REST_Response($websites, 200);
    }
    
    /**
     * Get website metrics endpoint
     *
     * @param WP_REST_Request $request REST request
     * @return WP_REST_Response Response
     */
    public function get_website_metrics($request) {
        $uuid = $request->get_param('uuid');
        $period = $request->get_param('period') ?: 'day';
        
        $metrics = server_metrics()->db_handler->get_website_metrics($uuid, $period);
        return new WP_REST_Response($metrics, 200);
    }
    
    /**
     * Get logs endpoint
     *
     * @param WP_REST_Request $request REST request
     * @return WP_REST_Response Response
     */
    public function get_logs($request) {
        // Toto je zatím prázdná implementace pro budoucí použití
        return new WP_REST_Response(['status' => 'not_implemented', 'message' => 'Logs feature coming soon'], 200);
    }
}