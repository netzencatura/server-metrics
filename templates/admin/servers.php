<?php
/**
 * Servers template
 *
 * @package Server_Metrics
 */

// Zabránění přímému přístupu
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap server-metrics-servers">
    <h1><?php _e('Servers Overview', 'server-metrics'); ?></h1>
    
    <div class="servers-table-container">
        <table class="wp-list-table widefat fixed striped servers-table">
            <thead>
                <tr>
                    <th><?php _e('Server Name', 'server-metrics'); ?></th>
                    <th><?php _e('IP Address', 'server-metrics'); ?></th>
                    <th><?php _e('Role', 'server-metrics'); ?></th>
                    <th><?php _e('Last Activity', 'server-metrics'); ?></th>
                    <th><?php _e('Status', 'server-metrics'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($servers as $server): ?>
                <tr>
                    <td><?php echo esc_html($server->server_name); ?></td>
                    <td><?php echo esc_html($server->server_ip); ?></td>
                    <td><?php echo $server->is_master ? __('Master', 'server-metrics') : __('Slave', 'server-metrics'); ?></td>
                    <td><?php echo esc_html($server->last_seen); ?></td>
                    <td>
                        <span class="status-badge status-<?php echo strtolower($server->status); ?>">
                            <?php echo esc_html($server->status); ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>