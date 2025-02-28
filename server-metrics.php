<?php
/**
 * Plugin Name: Server Metrics
 * Description: Display server and website metrics from Enhance CP cluster
 * Version: 1.0.19
 * Author: Your name
 * Text Domain: server-metrics
 * Domain Path: /languages
 *
 * @package Server_Metrics
 */

// Zabránění přímému přístupu
if (!defined('ABSPATH')) {
    exit;
}

// Definice konstant
define('SERVER_METRICS_VERSION', '1.0.19');
define('SERVER_METRICS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SERVER_METRICS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SERVER_METRICS_PLUGIN_FILE', __FILE__);

// Načtení hlavní třídy
require_once SERVER_METRICS_PLUGIN_DIR . 'includes/class-server-metrics.php';

/**
 * Returns the main instance of Server_Metrics
 *
 * @return Server_Metrics
 */
function server_metrics() {
    return Server_Metrics::get_instance();
}

// Inicializace pluginu
$GLOBALS['server_metrics'] = server_metrics();

/**
 * Registrace systému automatických aktualizací z GitHubu
 */
function sm_register_plugin_updater() {
    // Kontrola, zda je již knihovna načtena
    if (!class_exists('YahnisElsts\PluginUpdateChecker\v5p5\PucFactory')) {
        // Načtení knihovny Plugin Update Checker
        require_once SERVER_METRICS_PLUGIN_DIR . 'includes/lib/plugin-update-checker/plugin-update-checker.php';
    }
    
    // Inicializace kontroly aktualizací
    $updateChecker = \YahnisElsts\PluginUpdateChecker\v5p5\PucFactory::buildUpdateChecker(
        'https://github.com/netzencatura/server-metrics/',
        SERVER_METRICS_PLUGIN_FILE,
        'server-metrics'
    );
    
    // Nastavení větve, pokud používáte jinou než master/main
    $updateChecker->setBranch('main');
    
    // Aktivace použití release assetů - důležité pro správnou aktualizaci
    $updateChecker->getVcsApi()->enableReleaseAssets();
}

// Spuštění kontroly aktualizací při načtení WordPressu
add_action('init', 'sm_register_plugin_updater');