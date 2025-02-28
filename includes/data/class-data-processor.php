<?php
/**
 * Data processor class
 *
 * @package Server_Metrics
 */

// Zabránění přímému přístupu
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Data processor class
 */
class Server_Metrics_Data_Processor {
    
    /**
     * Process incoming metrics
     *
     * @param array $metrics Raw metrics data
     * @return bool|int False on failure, number of rows affected on success
     */
    public function process_metrics($metrics) {
        // Logování přijatých dat pro debugging
        error_log('Processing metrics: ' . print_r($metrics, true));
        
        // Validace a čištění dat
        $clean_metrics = $this->sanitize_metrics($metrics);
        
        // Kontrola, zda máme všechna povinná data
        if (empty($clean_metrics['uuid']) || empty($clean_metrics['domain'])) {
            error_log('Missing required metrics fields: uuid or domain');
            return false;
        }
        
        // Uložení do databáze
        return server_metrics()->db_handler->save_metrics($clean_metrics);
    }
    
    /**
     * Sanitize metrics data
     *
     * @param array $metrics Raw metrics data
     * @return array Sanitized metrics data
     */
    private function sanitize_metrics($metrics) {
        $clean_metrics = [];
        
        // Základní údaje
        $clean_metrics['uuid'] = isset($metrics['uuid']) ? sanitize_text_field($metrics['uuid']) : '';
        $clean_metrics['domain'] = isset($metrics['domain']) ? sanitize_text_field($metrics['domain']) : '';
        $clean_metrics['server'] = isset($metrics['server']) ? sanitize_text_field($metrics['server']) : ($_SERVER['SERVER_NAME'] ?? gethostname());
        
        // Číselné hodnoty
        $clean_metrics['cpu_usage'] = isset($metrics['cpu_usage']) ? floatval($metrics['cpu_usage']) : 0;
        $clean_metrics['mem_usage'] = isset($metrics['mem_usage']) ? floatval($metrics['mem_usage']) : 0;
        $clean_metrics['io_read_rate'] = isset($metrics['io_read_rate']) ? intval($metrics['io_read_rate']) : 0;
        $clean_metrics['io_write_rate'] = isset($metrics['io_write_rate']) ? intval($metrics['io_write_rate']) : 0;
        
        // Zachovat timestamp, pokud existuje
        if (isset($metrics['timestamp'])) {
            $clean_metrics['timestamp'] = $metrics['timestamp'];
        }
        
        return $clean_metrics;
    }
    
    /**
     * Aggregate metrics by time interval
     *
     * @param array $metrics Metrics data
     * @param string $interval Time interval (hour, day, week, month)
     * @return array Aggregated metrics
     */
    public function aggregate_metrics_by_interval($metrics, $interval = 'hour') {
        if (empty($metrics)) {
            return [];
        }
        
        $aggregated = [];
        $temp = [];
        
        // Seskupení podle časového intervalu
        foreach ($metrics as $metric) {
            $timestamp = strtotime($metric->timestamp);
            $key = '';
            
            switch ($interval) {
                case 'hour':
                    $key = date('Y-m-d H:00', $timestamp);
                    break;
                case 'day':
                    $key = date('Y-m-d', $timestamp);
                    break;
                case 'week':
                    $key = date('Y-W', $timestamp);
                    break;
                case 'month':
                    $key = date('Y-m', $timestamp);
                    break;
            }
            
            if (!isset($temp[$key])) {
                $temp[$key] = [
                    'count' => 0,
                    'cpu_sum' => 0,
                    'mem_sum' => 0,
                    'io_read_sum' => 0,
                    'io_write_sum' => 0
                ];
            }
            
            $temp[$key]['count']++;
            $temp[$key]['cpu_sum'] += $metric->cpu_usage;
            $temp[$key]['mem_sum'] += $metric->mem_usage;
            $temp[$key]['io_read_sum'] += $metric->io_read_rate;
            $temp[$key]['io_write_sum'] += $metric->io_write_rate;
        }
        
        // Výpočet průměrů
        foreach ($temp as $key => $data) {
            $aggregated[] = (object)[
                'timestamp' => $key,
                'cpu_usage' => $data['cpu_sum'] / $data['count'],
                'mem_usage' => $data['mem_sum'] / $data['count'],
                'io_read_rate' => $data['io_read_sum'] / $data['count'],
                'io_write_rate' => $data['io_write_sum'] / $data['count']
            ];
        }
        
        // Seřazení podle časové značky
        usort($aggregated, function($a, $b) {
            return strtotime($a->timestamp) - strtotime($b->timestamp);
        });
        
        return $aggregated;
    }
}