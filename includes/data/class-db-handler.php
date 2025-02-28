<?php
/**
 * Database handler class
 *
 * @package Server_Metrics
 */

// Zabránění přímému přístupu
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database handler class
 */
class Server_Metrics_DB_Handler {
    /**
     * Metrics table name
     *
     * @var string
     */
    private $metrics_table;
    
    /**
     * Servers table name
     *
     * @var string
     */
    private $servers_table;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->metrics_table = $wpdb->prefix . 'server_metrics';
        $this->servers_table = $wpdb->prefix . 'server_info';
    }
    
    /**
     * Create database tables on plugin activation
     */
    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $this->metrics_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            uuid varchar(36) NOT NULL,
            domain varchar(255) NOT NULL,
            cpu_usage float NOT NULL,
            mem_usage float NOT NULL,
            io_read_rate bigint(20) NOT NULL,
            io_write_rate bigint(20) NOT NULL,
            server varchar(255) NOT NULL,
            timestamp datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY uuid (uuid),
            KEY domain (domain),
            KEY server (server),
            KEY timestamp (timestamp)
        ) $charset_collate;
        
        CREATE TABLE $this->servers_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            server_name varchar(255) NOT NULL,
            server_ip varchar(45) NOT NULL,
            is_master tinyint(1) NOT NULL DEFAULT 0,
            last_seen datetime NOT NULL,
            status varchar(50) NOT NULL DEFAULT 'online',
            PRIMARY KEY  (id),
            UNIQUE KEY server_name (server_name),
            UNIQUE KEY server_ip (server_ip)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Save metrics to database
     *
     * @param array $metrics Metrics data
     * @return bool|int False on failure, number of rows affected on success
     */
    public function save_metrics($metrics) {
        global $wpdb;
        
        // Logování pro debugging
        error_log('Saving metrics to database: ' . print_r($metrics, true));
        
        // Převést vector.dev timestamp na MySQL datetime formát, pokud existuje
        $timestamp = current_time('mysql');
        if (isset($metrics['timestamp'])) {
            try {
                // Vector.dev timestamp je obvykle ve formátu ISO 8601 včetně nanosekundy
                $dt = new DateTime($metrics['timestamp']);
                $timestamp = $dt->format('Y-m-d H:i:s');
                error_log('Converted timestamp: ' . $timestamp);
            } catch (Exception $e) {
                error_log('Error converting timestamp: ' . $e->getMessage());
                // Použijeme aktuální čas jako fallback
            }
        }
        
        $result = $wpdb->insert(
            $this->metrics_table,
            [
                'uuid' => $metrics['uuid'],
                'domain' => $metrics['domain'],
                'cpu_usage' => $metrics['cpu_usage'],
                'mem_usage' => $metrics['mem_usage'],
                'io_read_rate' => $metrics['io_read_rate'],
                'io_write_rate' => $metrics['io_write_rate'],
                'server' => $metrics['server'],
                'timestamp' => $timestamp
            ]
        );
        
        if ($result === false) {
            error_log('DB insert error: ' . $wpdb->last_error);
            return false;
        }
        
        // Aktualizace informací o serveru
        $this->update_server_info($metrics['server']);
        
        return $result;
    }
    
    /**
     * Update server information
     *
     * @param string $server_name Server name
     */
    private function update_server_info($server_name) {
        global $wpdb;
        
        $server = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $this->servers_table WHERE server_name = %s",
                $server_name
            )
        );
        
        if (!$server) {
            // Přidání nového serveru
            $wpdb->insert(
                $this->servers_table,
                [
                    'server_name' => $server_name,
                    'server_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'is_master' => 0, // Předpokládáme, že není master
                    'last_seen' => current_time('mysql'),
                    'status' => 'online'
                ]
            );
        } else {
            // Aktualizace existujícího serveru
            $wpdb->update(
                $this->servers_table,
                [
                    'last_seen' => current_time('mysql'),
                    'status' => 'online'
                ],
                ['server_name' => $server_name]
            );
        }
    }
    
    /**
     * Get website metrics
     *
     * @param string $uuid Website UUID
     * @param string $period Time period (hour, day, week, month)
     * @param string $time_range Time range (15min, 30min, 1hour, 2hours)
     * @return array Website metrics
     */
    public function get_website_metrics($uuid, $period = 'day', $time_range = '15min') {
        global $wpdb;
        
        $time_condition = $this->get_time_condition($period);
        
        // Nastavit max_points podle časového intervalu
        $max_points = 30; // výchozí pro 15 minut (2 body na minutu × 15 minut)
        switch ($time_range) {
            case '30min':
                $max_points = 60; // pro 30 minut (2 body na minutu × 30 minut)
                break;
            case '1hour':
                $max_points = 120; // pro 1 hodinu (2 body na minutu × 60 minut)
                break;
            case '2hours':
                $max_points = 240; // pro 2 hodiny (2 body na minutu × 120 minut)
                break;
        }
        
        // Nejprve získáme celkový počet záznamů
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $this->metrics_table 
                WHERE uuid = %s AND $time_condition",
                $uuid
            )
        );
        
        // Pokud je záznamů více než max_points, použijeme vzorkování
        if ($count > $max_points) {
            $interval = ceil($count / $max_points);
            
            // Použití SQL pro výběr každého n-tého záznamu
            return $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM (
                        SELECT *, (@row_number:=@row_number+1) AS row_num 
                        FROM $this->metrics_table, (SELECT @row_number:=0) AS t 
                        WHERE uuid = %s AND $time_condition
                        ORDER BY timestamp ASC
                    ) AS numbered 
                    WHERE row_num % %d = 0 OR row_num = 1 OR row_num = @row_number
                    ORDER BY timestamp DESC
                    LIMIT %d",
                    $uuid,
                    $interval,
                    $max_points
                )
            );
        } else {
            // Pokud je záznamů méně než limit, vrátíme všechny
            return $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM $this->metrics_table 
                    WHERE uuid = %s AND $time_condition
                    ORDER BY timestamp DESC",
                    $uuid
                )
            );
        }
    }
    
    /**
     * Get latest metrics for all websites
     *
     * @return array Latest metrics grouped by server
     */
    public function get_latest_metrics() {
        global $wpdb;
        
        // Získat nejnovější záznamy pro každý web, grupované podle serveru
        $metrics = $wpdb->get_results(
            "SELECT m1.* 
            FROM $this->metrics_table m1
            JOIN (
                SELECT uuid, server, MAX(timestamp) as latest_time
                FROM $this->metrics_table
                GROUP BY uuid, server
            ) m2 ON m1.uuid = m2.uuid 
                AND m1.server = m2.server 
                AND m1.timestamp = m2.latest_time
            ORDER BY m1.server, m1.mem_usage DESC"
        );

        // Seskupit podle serverů
        $grouped_metrics = [];
        foreach ($metrics as $metric) {
            if (!isset($grouped_metrics[$metric->server])) {
                $grouped_metrics[$metric->server] = [];
            }
            $grouped_metrics[$metric->server][] = $metric;
        }
        
        return $grouped_metrics;
    }
    
    /**
     * Get all servers
     *
     * @return array All servers
     */
    public function get_servers() {
        global $wpdb;
        
        // Aktualizace statusu serverů (offline po 10 minutách neaktivity)
        $wpdb->query("
            UPDATE $this->servers_table
            SET status = 'offline'
            WHERE last_seen < DATE_SUB(NOW(), INTERVAL 10 MINUTE)
        ");
        
        return $wpdb->get_results(
            "SELECT * FROM $this->servers_table
            ORDER BY is_master DESC, server_name ASC"
        );
    }
    
    /**
     * Get time condition for SQL queries
     *
     * @param string $period Time period (hour, day, week, month)
     * @return string SQL time condition
     */
/**
 * Get time condition for SQL queries
 *
 * @param string $period Time period (30min, hour, 6hours, 12hours, day, week, month)
 * @return string SQL time condition
 */
private function get_time_condition($period) {
    $now = current_time('mysql');
    
    switch ($period) {
        case '30min':
            return "timestamp >= DATE_SUB('$now', INTERVAL 30 MINUTE)";
        case 'hour':
            return "timestamp >= DATE_SUB('$now', INTERVAL 1 HOUR)";
        case '6hours':
            return "timestamp >= DATE_SUB('$now', INTERVAL 6 HOUR)";
        case '12hours':
            return "timestamp >= DATE_SUB('$now', INTERVAL 12 HOUR)";
        case 'day':
            return "timestamp >= DATE_SUB('$now', INTERVAL 1 DAY)";
        case 'week':
            return "timestamp >= DATE_SUB('$now', INTERVAL 1 WEEK)";
        case 'month':
            return "timestamp >= DATE_SUB('$now', INTERVAL 1 MONTH)";
        default:
            return "timestamp >= DATE_SUB('$now', INTERVAL 1 DAY)";
    }
}
    
    /**
     * Clean up old data
     *
     * @param int $retention_days Number of days to keep data
     * @return int|bool Number of rows deleted or false on error
     */
    public function cleanup_old_data($retention_days = 30) {
        global $wpdb;
        
        return $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $this->metrics_table 
                WHERE timestamp < DATE_SUB(%s, INTERVAL %d DAY)",
                current_time('mysql'),
                $retention_days
            )
        );
    }
    
    /**
     * Maybe delete data on plugin deactivation
     */
    public function maybe_delete_data() {
        $delete_on_deactivate = get_option('server_metrics_delete_data', false);
        
        if ($delete_on_deactivate) {
            global $wpdb;
            $wpdb->query("DROP TABLE IF EXISTS $this->metrics_table");
            $wpdb->query("DROP TABLE IF EXISTS $this->servers_table");
        }
    }

    /**
     * Get server IP by name
     *
     * @param string $server_name Server name
     * @return string Server IP
     */
    public function get_server_ip($server_name) {
        global $wpdb;
        
        $ip = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT server_ip FROM $this->servers_table WHERE server_name = %s",
                $server_name
            )
        );
        
        return $ip ? $ip : $server_name; // Pokud nenajdeme IP, vrátíme jméno serveru jako fallback
    }

    /**
     * Get average historical data
     *
     * @param int $hours Number of hours to get data for
     * @param int $points Number of data points
     * @return array Average historical data
     */
    public function get_average_historical_data($hours = 24, $points = 12) {
        global $wpdb;
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT 
                    AVG(cpu_usage) as avg_cpu,
                    AVG(mem_usage) as avg_mem,
                    DATE_FORMAT(timestamp, '%%Y-%%m-%%d %%H:00:00') as hour_timestamp
                FROM $this->metrics_table
                WHERE timestamp >= DATE_SUB(%s, INTERVAL %d HOUR)
                GROUP BY hour_timestamp
                ORDER BY hour_timestamp ASC
                LIMIT %d",
                current_time('mysql'),
                $hours,
                $points
            )
        );
        
        return $results;
    }

    /**
     * Get website metrics since last timestamp
     *
     * @param string $uuid Website UUID
     * @param string $lastTimestamp Last timestamp
     * @param int $limit Maximum number of records to return
     * @return array Website metrics
     */
    public function get_website_metrics_live($uuid, $lastTimestamp = null, $limit = 10) {
        global $wpdb;
        
        $params = [$uuid];
        $timestampCondition = "";
        
        if (!empty($lastTimestamp)) {
            $timestampCondition = "AND timestamp > %s";
            $params[] = $lastTimestamp;
        }
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $this->metrics_table 
                WHERE uuid = %s $timestampCondition
                ORDER BY timestamp ASC
                LIMIT %d",
                array_merge($params, [$limit])
            )
        );
    }

   /**
 * Get sparkline data
 *
 * @param string $uuid Website UUID
 * @param string $period Časový rozsah (30min, hour, 6hours, atd.)
 * @param int $points Number of data points
 * @return array Sparkline data
 */
public function get_sparkline_data($uuid, $period = 'day', $points = 15) {
    global $wpdb;
    
    // Získáme podmínku pro časový rozsah
    $time_condition = $this->get_time_condition($period);
    
    // Určení maximálního počtu bodů podle časového rozsahu
    $max_points = 15;
    switch ($period) {
        case '30min':
            $max_points = 10;
            break;
        case 'hour':
            $max_points = 12;
            break;
        case '6hours':
            $max_points = 15;
            break;
        case '12hours':
            $max_points = 20;
            break;
        case 'day':
            $max_points = 24;
            break;
        case 'week':
            $max_points = 28;
            break;
        case 'month':
            $max_points = 30;
            break;
    }
    
    // Nejprve získáme celkový počet záznamů pro dané období
    $count = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM $this->metrics_table 
            WHERE uuid = %s AND $time_condition",
            $uuid
        )
    );
    
    // Pokud je záznamů více než max_points, použijeme vzorkování
    if ($count > $max_points) {
        $interval = ceil($count / $max_points);
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM (
                    SELECT *, (@row_number:=@row_number+1) AS row_num 
                    FROM $this->metrics_table, (SELECT @row_number:=0) AS t 
                    WHERE uuid = %s AND $time_condition
                    ORDER BY timestamp ASC
                ) AS numbered 
                WHERE row_num % %d = 0 OR row_num = 1 OR row_num = @row_number
                ORDER BY timestamp DESC
                LIMIT %d",
                $uuid,
                $interval,
                $max_points
            )
        );
    } else {
        // Pokud je záznamů méně než limit, vrátíme všechny
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $this->metrics_table 
                WHERE uuid = %s AND $time_condition
                ORDER BY timestamp DESC
                LIMIT %d",
                $uuid,
                $max_points
            )
        );
    }
}
}