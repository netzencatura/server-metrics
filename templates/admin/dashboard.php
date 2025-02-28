<?php
/**
 * Dashboard template
 *
 * @package Server_Metrics
 */

// Zabránění přímému přístupu
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap server-metrics-dashboard">
    <h1><?php _e('Server Metrics Dashboard', 'server-metrics'); ?></h1>
    
    <div class="dashboard-header">
        <div class="filters">
            <label for="period-filter"><?php _e('Period:', 'server-metrics'); ?></label>
<select id="period-filter">
    <option value="30min"><?php _e('Last 30 minutes', 'server-metrics'); ?></option>
    <option value="hour"><?php _e('Last hour', 'server-metrics'); ?></option>
    <option value="6hours"><?php _e('Last 6 hours', 'server-metrics'); ?></option>
    <option value="12hours"><?php _e('Last 12 hours', 'server-metrics'); ?></option>
    <option value="day" selected><?php _e('Last day', 'server-metrics'); ?></option>
    <option value="week"><?php _e('Last week', 'server-metrics'); ?></option>
    <option value="month"><?php _e('Last month', 'server-metrics'); ?></option>
</select>
        </div>
        <button id="dark-mode-toggle" class="button"><?php _e('Dark Mode', 'server-metrics'); ?></button>
    </div>
    
    <div class="dashboard-summary">
        <div class="summary-card">
            <h3><?php _e('Total websites', 'server-metrics'); ?></h3>
            <div class="summary-value">
                <?php 
                $total_websites = 0;
                foreach ($websites as $server_websites) {
                    $total_websites += count($server_websites);
                }
                echo $total_websites;
                ?>
            </div>
        </div>
        <div class="summary-card">
            <h3><?php _e('Average CPU usage', 'server-metrics'); ?></h3>
            <div class="summary-value">
                <?php 
                $total_cpu = 0;
                $total_count = 0;
                foreach ($websites as $server_websites) {
                    foreach ($server_websites as $website) {
                        $total_cpu += $website->cpu_usage;
                        $total_count++;
                    }
                }
                echo round($total_cpu / max(1, $total_count), 2) . '%';
                ?>
            </div>
            <div class="summary-chart-container">
                <canvas id="avg-cpu-chart"></canvas>
            </div>
        </div>
        <div class="summary-card">
            <h3><?php _e('Average RAM usage', 'server-metrics'); ?></h3>
            <div class="summary-value">
                <?php 
                $total_mem = 0;
                foreach ($websites as $server_websites) {
                    foreach ($server_websites as $website) {
                        $total_mem += $website->mem_usage;
                    }
                }
                echo round($total_mem / max(1, $total_count), 2) . '%';
                ?>
            </div>
            <div class="summary-chart-container">
                <canvas id="avg-ram-chart"></canvas>
            </div>
        </div>
    </div>
    
    <?php foreach ($websites as $server_name => $server_websites): ?>
    <div class="websites-table-container">
        <h2><?php echo esc_html(sprintf(__('Websites Overview - Server: %s', 'server-metrics'), $server_name)); ?></h2>
        <table class="wp-list-table widefat fixed striped websites-table">
            <thead>
                <tr>
                    <th><?php _e('Domain', 'server-metrics'); ?></th>
                    <th><?php _e('UUID', 'server-metrics'); ?></th>
                    <th><?php _e('CPU', 'server-metrics'); ?></th>
                    <th><?php _e('RAM', 'server-metrics'); ?></th>
                    <th><?php _e('I/O Read', 'server-metrics'); ?></th>
                    <th><?php _e('I/O Write', 'server-metrics'); ?></th>
                    <th><?php _e('Actions', 'server-metrics'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($server_websites as $website): ?>
                <tr>
                    <td><?php echo esc_html($website->domain); ?></td>
                    <td><?php echo esc_html($website->uuid); ?></td>
                    <td>
                        <?php echo round($website->cpu_usage, 2) . '%'; ?>
                        <div class="sparkline-container">
                            <canvas class="sparkline" data-type="cpu" data-uuid="<?php echo esc_attr($website->uuid); ?>"></canvas>
                        </div>
                    </td>
                    <td>
                        <?php echo round($website->mem_usage, 2) . '%'; ?>
                        <div class="sparkline-container">
                            <canvas class="sparkline" data-type="memory" data-uuid="<?php echo esc_attr($website->uuid); ?>"></canvas>
                        </div>
                    </td>
                    <td>
                        <?php echo $this->format_bytes($website->io_read_rate); ?>/s
                        <div class="sparkline-container">
                            <canvas class="sparkline" data-type="io_read" data-uuid="<?php echo esc_attr($website->uuid); ?>"></canvas>
                        </div>
                    </td>
                    <td>
                        <?php echo $this->format_bytes($website->io_write_rate); ?>/s
                        <div class="sparkline-container">
                            <canvas class="sparkline" data-type="io_write" data-uuid="<?php echo esc_attr($website->uuid); ?>"></canvas>
                        </div>
                    </td>
                    <td>
                        <a href="#" class="show-detail-btn" data-uuid="<?php echo esc_attr($website->uuid); ?>">
                            <?php _e('Detail', 'server-metrics'); ?>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endforeach; ?>
    
    <div id="website-detail-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 id="detail-domain"><?php _e('Website Detail', 'server-metrics'); ?></h2>
            
            <div class="detail-charts">
                <div class="chart-container">
                    <h3><?php _e('CPU Usage', 'server-metrics'); ?></h3>
                    <canvas id="cpu-chart"></canvas>
                </div>
                <div class="chart-container">
                    <h3><?php _e('RAM Usage', 'server-metrics'); ?></h3>
                    <canvas id="memory-chart"></canvas>
                </div>
                <div class="chart-container">
                    <h3><?php _e('I/O Read', 'server-metrics'); ?></h3>
                    <canvas id="io-read-chart"></canvas>
                </div>
                <div class="chart-container">
                    <h3><?php _e('I/O Write', 'server-metrics'); ?></h3>
                    <canvas id="io-write-chart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>