<?php
/**
 * Settings template
 *
 * @package Server_Metrics
 */

// Zabránění přímému přístupu
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap server-metrics-settings">
    <h1><?php _e('Settings', 'server-metrics'); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('server_metrics_settings'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="retention_days"><?php _e('Data retention period (days)', 'server-metrics'); ?></label>
                </th>
                <td>
                    <input type="number" id="retention_days" name="retention_days" value="<?php echo esc_attr($retention_days); ?>" min="1" max="365" />
                    <p class="description"><?php _e('After this period, older metrics will be automatically deleted', 'server-metrics'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="delete_on_deactivate"><?php _e('Delete data on deactivation', 'server-metrics'); ?></label>
                </th>
                <td>
                    <input type="checkbox" id="delete_on_deactivate" name="delete_on_deactivate" <?php checked($delete_on_deactivate); ?> />
                    <p class="description"><?php _e('If checked, all data will be deleted when the plugin is deactivated', 'server-metrics'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="api_token"><?php _e('API Token', 'server-metrics'); ?></label>
                </th>
                <td>
                    <div class="api-token-container">
                        <input type="text" id="api_token" value="<?php echo esc_attr(server_metrics()->settings->get_api_token()); ?>" readonly class="regular-text" />
                        <button type="button" class="button" id="copy_token"><?php _e('Copy', 'server-metrics'); ?></button>
                        <button type="button" class="button" id="regenerate_token"><?php _e('Regenerate', 'server-metrics'); ?></button>
                    </div>
                    <p class="description"><?php _e('Use this token in Vector configuration. Click "Copy" to copy to clipboard.', 'server-metrics'); ?></p>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <input type="submit" name="server_metrics_settings_submit" class="button button-primary" value="<?php _e('Save Settings', 'server-metrics'); ?>" />
        </p>
    </form>
</div>

<script>
document.getElementById('copy_token').addEventListener('click', function() {
    var tokenInput = document.getElementById('api_token');
    tokenInput.select();
    document.execCommand('copy');
    this.textContent = '<?php _e('Copied!', 'server-metrics'); ?>';
    setTimeout(() => {
        this.textContent = '<?php _e('Copy', 'server-metrics'); ?>';
    }, 2000);
});

document.getElementById('regenerate_token').addEventListener('click', function() {
    if (confirm('<?php _e('Are you sure you want to regenerate the API token? You will need to update Vector configuration on all servers.', 'server-metrics'); ?>')) {
        // Zde by měl být AJAX call pro regeneraci tokenu
        // Pro nyní jen refresh stránky
        location.reload();
    }
});
</script>

<style>
.api-token-container {
    display: flex;
    gap: 10px;
    margin-bottom: 5px;
}

.api-token-container input[type="text"] {
    font-family: monospace;
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    padding: 6px 12px;
}

.api-token-container input[type="text"]:focus {
    border-color: #80bdff;
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
}

.api-token-container .button {
    height: auto;
    min-width: 80px;
}

/* Dark mode support */
body.dark-mode .api-token-container input[type="text"] {
    background-color: #2d2d2d;
    border-color: #3d3d3d;
    color: #e0e0e0;
}
</style>