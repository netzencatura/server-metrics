<?php
/**
 * Logs template
 *
 * @package Server_Metrics
 */

// Zabránění přímému přístupu
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap server-metrics-logs">
    <h1><?php _e('Server Logs', 'server-metrics'); ?></h1>
    
    <div class="logs-notice">
        <p><?php _e('The logs functionality is coming soon. This page will show server logs collected from your servers.', 'server-metrics'); ?></p>
    </div>
    
    <div class="logs-filters">
        <select id="logs-server-filter" disabled>
            <option value=""><?php _e('All Servers', 'server-metrics'); ?></option>
        </select>
        
        <select id="logs-level-filter" disabled>
            <option value=""><?php _e('All Levels', 'server-metrics'); ?></option>
            <option value="error"><?php _e('Error', 'server-metrics'); ?></option>
            <option value="warning"><?php _e('Warning', 'server-metrics'); ?></option>
            <option value="info"><?php _e('Info', 'server-metrics'); ?></option>
            <option value="debug"><?php _e('Debug', 'server-metrics'); ?></option>
        </select>
        
        <button id="logs-refresh" class="button" disabled><?php _e('Refresh', 'server-metrics'); ?></button>
    </div>
    
    <div class="logs-table-container">
        <table class="wp-list-table widefat fixed striped logs-table">
            <thead>
                <tr>
                    <th><?php _e('Timestamp', 'server-metrics'); ?></th>
                    <th><?php _e('Server', 'server-metrics'); ?></th>
                    <th><?php _e('Level', 'server-metrics'); ?></th>
                    <th><?php _e('Message', 'server-metrics'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="4"><?php _e('No logs available yet.', 'server-metrics'); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>