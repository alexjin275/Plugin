<?php
/**
 * Advanced Charting System with TradingView Integration
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crypto_Exchange_Charting {
    
    private $wpdb;
    private $tradingview_config;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        $this->init_tradingview();
        add_action('wp_ajax_crypto_exchange_get_chart_data', array($this, 'get_chart_data'));
        add_action('wp_ajax_nopriv_crypto_exchange_get_chart_data', array($this, 'get_chart_data'));
        add_action('crypto_exchange_update_chart_data', array($this, 'update_chart_data'));
        
        if (!wp_next_scheduled('crypto_exchange_update_chart_data')) {
            wp_schedule_event(time(), 'every_minute', 'crypto_exchange_update_chart_data');
        }
    }
    
    /**
     * Initialize TradingView configuration
     */
    private function init_tradingview() {
        $this->tradingview_config = array(
            'container_id' => 'tradingview_chart',
            'symbol' => 'BTCUSD',
            'interval' => '1',
            'timezone' => 'UTC',
            'theme' => 'dark',
            'style' => '1',
            'locale' => 'en',
            'toolbar_bg' => '#f1f3f6',
            'enable_publishing' => false,
            'hide_side_toolbar' => false,
            'allow_symbol_change' => true,
            'details' => true,
            'hotlist' => true,
            'calendar' => true,
            'studies' => array(
                'MACD',
                'RSI',
                'Bollinger Bands',
                'Volume',
                'Moving Average'
            ),
            'drawings_access' => array(
                'type' => 'black',
                'tools' => array(
                    array('name' => 'text', 'grayed' => false),
                    array('name' => 'anchored_text', 'grayed' => false),
                    array('name' => 'note', 'grayed' => false),
                    array('name' => 'arrow_up', 'grayed' => false),
                    array('name' => 'arrow_down', 'grayed' => false),
                    array('name' => 'flag', 'grayed' => false),
                    array('name' => 'vertical_line', 'grayed' => false),
                    array('name' => 'horizontal_line', 'grayed' => false),
                    array('name' => 'trend_line', 'grayed' => false),
                    array('name' => 'fibonacci_retracement', 'grayed' => false),
                    array('name' => 'fibonacci_extension', 'grayed' => false),
                    array('name' => 'fibonacci_arc', 'grayed' => false),
                    array('name' => 'fibonacci_fan', 'grayed' => false),
                    array('name' => 'fibonacci_timezones', 'grayed' => false),
                    array('name' => 'pitchfork', 'grayed' => false),
                    array('name' => 'gann_fan', 'grayed' => false),
                    array('name' => 'gann_square', 'grayed' => false),
                    array('name' => 'gann_hexagon', 'grayed' => false),
                    array('name' => 'gann_circle', 'grayed' => false),
                    array('name' => 'gann_line', 'grayed' => false),
                    array('name' => 'gann_arc', 'grayed' => false),
                    array('name' => 'gann_grid', 'grayed' => false),
                    array('name' => 'gann_swing', 'grayed' => false),
                    array('name' => 'gann_retracement', 'grayed' => false),
                    array('name' => 'gann_extension', 'grayed' => false),
                    array('name' => 'gann_fan', 'grayed' => false),
                    array('name' => 'gann_square', 'grayed' => false),
                    array('name' => 'gann_hexagon', 'grayed' => false),
                    array('name' => 'gann_circle', 'grayed' => false),
                    array('name' => 'gann_line', 'grayed' => false),
                    array('name' => 'gann_arc', 'grayed' => false),
                    array('name' => 'gann_grid', 'grayed' => false),
                    array('name' => 'gann_swing', 'grayed' => false),
                    array('name' => 'gann_retracement', 'grayed' => false),
                    array('name' => 'gann_extension', 'grayed' => false)
                )
            ),
            'overrides' => array(
                'mainSeriesProperties.candleStyle.upColor' => '#26a69a',
                'mainSeriesProperties.candleStyle.downColor' => '#ef5350',
                'mainSeriesProperties.candleStyle.borderUpColor' => '#26a69a',
                'mainSeriesProperties.candleStyle.borderDownColor' => '#ef5350',
                'mainSeriesProperties.candleStyle.wickUpColor' => '#26a69a',
                'mainSeriesProperties.candleStyle.wickDownColor' => '#ef5350'
            )
        );
    }
    
    /**
     * Render TradingView chart
     */
    public function render_chart($pair = 'BTC/USD', $interval = '1', $theme = 'dark') {
        $config = $this->tradingview_config;
        $config['symbol'] = str_replace('/', '', $pair);
        $config['interval'] = $interval;
        $config['theme'] = $theme;
        
        ob_start();
        ?>
        <div id="tradingview_chart" style="height: 500px; width: 100%;"></div>
        <script type="text/javascript" src="https://s3.tradingview.com/tv.js"></script>
        <script type="text/javascript">
        new TradingView.widget(<?php echo json_encode($config); ?>);
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get chart data for specific pair and interval
     */
    public function get_chart_data() {
        $pair = sanitize_text_field($_POST['pair'] ?? 'BTC/USD');
        $interval = sanitize_text_field($_POST['interval'] ?? '1');
        $from = intval($_POST['from'] ?? time() - 86400);
        $to = intval($_POST['to'] ?? time());
        
        $data = $this->get_ohlc_data($pair, $interval, $from, $to);
        
        wp_send_json_success($data);
    }
    
    /**
     * Get OHLC data for chart
     */
    private function get_ohlc_data($pair, $interval, $from, $to) {
        $table_name = $this->wpdb->prefix . 'crypto_price_history';
        
        // Create table if not exists
        $this->create_price_history_table();
        
        $interval_seconds = $this->get_interval_seconds($interval);
        
        $results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT 
                    timestamp,
                    open,
                    high,
                    low,
                    close,
                    volume
                FROM $table_name 
                WHERE pair = %s 
                AND interval_type = %s 
                AND timestamp >= %d 
                AND timestamp <= %d 
                ORDER BY timestamp ASC",
                $pair,
                $interval,
                $from,
                $to
            )
        );
        
        $data = array();
        foreach ($results as $row) {
            $data[] = array(
                'time' => $row->timestamp * 1000, // Convert to milliseconds
                'open' => floatval($row->open),
                'high' => floatval($row->high),
                'low' => floatval($row->low),
                'close' => floatval($row->close),
                'volume' => floatval($row->volume)
            );
        }
        
        return $data;
    }
    
    /**
     * Get interval in seconds
     */
    private function get_interval_seconds($interval) {
        $intervals = array(
            '1' => 60,
            '5' => 300,
            '15' => 900,
            '30' => 1800,
            '60' => 3600,
            '240' => 14400,
            '1D' => 86400,
            '1W' => 604800,
            '1M' => 2592000
        );
        
        return $intervals[$interval] ?? 60;
    }
    
    /**
     * Update chart data
     */
    public function update_chart_data() {
        $pairs = array('BTC/USD', 'ETH/USD', 'BNB/USD', 'ADA/USD', 'SOL/USD', 'DOT/USD', 'MATIC/USD', 'AVAX/USD');
        $intervals = array('1', '5', '15', '30', '60', '240', '1D');
        
        foreach ($pairs as $pair) {
            foreach ($intervals as $interval) {
                $this->update_pair_interval_data($pair, $interval);
            }
        }
    }
    
    /**
     * Update data for specific pair and interval
     */
    private function update_pair_interval_data($pair, $interval) {
        $table_name = $this->wpdb->prefix . 'crypto_price_history';
        
        // Get latest data point
        $latest = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM $table_name 
                 WHERE pair = %s AND interval_type = %s 
                 ORDER BY timestamp DESC LIMIT 1",
                $pair,
                $interval
            )
        );
        
        $start_time = $latest ? $latest->timestamp : time() - 86400;
        $end_time = time();
        
        $interval_seconds = $this->get_interval_seconds($interval);
        
        // Get raw price data
        $raw_data = $this->get_raw_price_data($pair, $start_time, $end_time);
        
        if (empty($raw_data)) {
            return;
        }
        
        // Aggregate data into intervals
        $aggregated = $this->aggregate_ohlc_data($raw_data, $interval_seconds);
        
        // Save to database
        foreach ($aggregated as $data) {
            $this->save_ohlc_data($pair, $interval, $data);
        }
    }
    
    /**
     * Get raw price data
     */
    private function get_raw_price_data($pair, $start_time, $end_time) {
        $market_data = new Crypto_Exchange_Market_Data();
        $price_data = $market_data->get_pair_data($pair);
        
        if (!$price_data) {
            return array();
        }
        
        // Generate mock historical data for demonstration
        $data = array();
        $current_time = $start_time;
        $price = floatval($price_data->price);
        
        while ($current_time < $end_time) {
            $data[] = array(
                'timestamp' => $current_time,
                'price' => $price + (rand(-100, 100) / 1000), // Add some volatility
                'volume' => rand(100, 1000)
            );
            
            $current_time += 60; // 1 minute intervals
        }
        
        return $data;
    }
    
    /**
     * Aggregate OHLC data
     */
    private function aggregate_ohlc_data($raw_data, $interval_seconds) {
        $aggregated = array();
        $current_interval = 0;
        $ohlc = null;
        
        foreach ($raw_data as $point) {
            $interval_start = floor($point['timestamp'] / $interval_seconds) * $interval_seconds;
            
            if ($interval_start !== $current_interval) {
                if ($ohlc) {
                    $aggregated[] = $ohlc;
                }
                
                $ohlc = array(
                    'timestamp' => $interval_start,
                    'open' => $point['price'],
                    'high' => $point['price'],
                    'low' => $point['price'],
                    'close' => $point['price'],
                    'volume' => $point['volume']
                );
                
                $current_interval = $interval_start;
            } else {
                $ohlc['high'] = max($ohlc['high'], $point['price']);
                $ohlc['low'] = min($ohlc['low'], $point['price']);
                $ohlc['close'] = $point['price'];
                $ohlc['volume'] += $point['volume'];
            }
        }
        
        if ($ohlc) {
            $aggregated[] = $ohlc;
        }
        
        return $aggregated;
    }
    
    /**
     * Save OHLC data
     */
    private function save_ohlc_data($pair, $interval, $data) {
        $table_name = $this->wpdb->prefix . 'crypto_price_history';
        
        // Check if data already exists
        $existing = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT id FROM $table_name 
                 WHERE pair = %s AND interval_type = %s AND timestamp = %d",
                $pair,
                $interval,
                $data['timestamp']
            )
        );
        
        if ($existing) {
            // Update existing record
            $this->wpdb->update(
                $table_name,
                array(
                    'open' => $data['open'],
                    'high' => $data['high'],
                    'low' => $data['low'],
                    'close' => $data['close'],
                    'volume' => $data['volume']
                ),
                array('id' => $existing->id),
                array('%f', '%f', '%f', '%f', '%f'),
                array('%d')
            );
        } else {
            // Insert new record
            $this->wpdb->insert(
                $table_name,
                array(
                    'pair' => $pair,
                    'price' => $data['close'],
                    'volume' => $data['volume'],
                    'high' => $data['high'],
                    'low' => $data['low'],
                    'open' => $data['open'],
                    'close' => $data['close'],
                    'interval_type' => $interval,
                    'timestamp' => $data['timestamp']
                ),
                array('%s', '%f', '%f', '%f', '%f', '%f', '%f', '%s', '%d')
            );
        }
    }
    
    /**
     * Create price history table
     */
    private function create_price_history_table() {
        $table_name = $this->wpdb->prefix . 'crypto_price_history';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            pair varchar(20) NOT NULL,
            price decimal(20,8) NOT NULL,
            volume decimal(20,8) DEFAULT 0,
            high decimal(20,8) DEFAULT 0,
            low decimal(20,8) DEFAULT 0,
            open decimal(20,8) DEFAULT 0,
            close decimal(20,8) DEFAULT 0,
            interval_type varchar(10) DEFAULT '1',
            timestamp bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY pair_interval (pair, interval_type),
            KEY timestamp (timestamp)
        ) " . $this->wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Get technical indicators
     */
    public function get_technical_indicators($pair, $interval = '1', $limit = 100) {
        $data = $this->get_ohlc_data($pair, $interval, time() - 86400, time());
        
        if (count($data) < $limit) {
            $limit = count($data);
        }
        
        $recent_data = array_slice($data, -$limit);
        
        return array(
            'sma_20' => $this->calculate_sma($recent_data, 20),
            'sma_50' => $this->calculate_sma($recent_data, 50),
            'ema_12' => $this->calculate_ema($recent_data, 12),
            'ema_26' => $this->calculate_ema($recent_data, 26),
            'rsi' => $this->calculate_rsi($recent_data, 14),
            'macd' => $this->calculate_macd($recent_data),
            'bollinger_bands' => $this->calculate_bollinger_bands($recent_data, 20, 2),
            'stochastic' => $this->calculate_stochastic($recent_data, 14, 3),
            'williams_r' => $this->calculate_williams_r($recent_data, 14),
            'atr' => $this->calculate_atr($recent_data, 14)
        );
    }
    
    /**
     * Calculate Simple Moving Average
     */
    private function calculate_sma($data, $period) {
        if (count($data) < $period) {
            return array();
        }
        
        $sma = array();
        for ($i = $period - 1; $i < count($data); $i++) {
            $sum = 0;
            for ($j = 0; $j < $period; $j++) {
                $sum += $data[$i - $j]['close'];
            }
            $sma[] = $sum / $period;
        }
        
        return $sma;
    }
    
    /**
     * Calculate Exponential Moving Average
     */
    private function calculate_ema($data, $period) {
        if (count($data) < $period) {
            return array();
        }
        
        $ema = array();
        $multiplier = 2 / ($period + 1);
        
        // First EMA is SMA
        $sum = 0;
        for ($i = 0; $i < $period; $i++) {
            $sum += $data[$i]['close'];
        }
        $ema[] = $sum / $period;
        
        // Calculate subsequent EMAs
        for ($i = $period; $i < count($data); $i++) {
            $ema[] = ($data[$i]['close'] * $multiplier) + ($ema[count($ema) - 1] * (1 - $multiplier));
        }
        
        return $ema;
    }
    
    /**
     * Calculate RSI
     */
    private function calculate_rsi($data, $period) {
        if (count($data) < $period + 1) {
            return array();
        }
        
        $gains = array();
        $losses = array();
        
        for ($i = 1; $i < count($data); $i++) {
            $change = $data[$i]['close'] - $data[$i - 1]['close'];
            $gains[] = $change > 0 ? $change : 0;
            $losses[] = $change < 0 ? abs($change) : 0;
        }
        
        $rsi = array();
        for ($i = $period; $i < count($gains); $i++) {
            $avg_gain = array_sum(array_slice($gains, $i - $period, $period)) / $period;
            $avg_loss = array_sum(array_slice($losses, $i - $period, $period)) / $period;
            
            if ($avg_loss == 0) {
                $rsi[] = 100;
            } else {
                $rs = $avg_gain / $avg_loss;
                $rsi[] = 100 - (100 / (1 + $rs));
            }
        }
        
        return $rsi;
    }
    
    /**
     * Calculate MACD
     */
    private function calculate_macd($data) {
        $ema_12 = $this->calculate_ema($data, 12);
        $ema_26 = $this->calculate_ema($data, 26);
        
        $macd = array();
        $signal = array();
        $histogram = array();
        
        for ($i = 0; $i < min(count($ema_12), count($ema_26)); $i++) {
            $macd[] = $ema_12[$i] - $ema_26[$i];
        }
        
        // Calculate signal line (9-period EMA of MACD)
        $signal = $this->calculate_ema(array_map(function($val) {
            return array('close' => $val);
        }, $macd), 9);
        
        // Calculate histogram
        for ($i = 0; $i < min(count($macd), count($signal)); $i++) {
            $histogram[] = $macd[$i] - $signal[$i];
        }
        
        return array(
            'macd' => $macd,
            'signal' => $signal,
            'histogram' => $histogram
        );
    }
    
    /**
     * Calculate Bollinger Bands
     */
    private function calculate_bollinger_bands($data, $period, $std_dev) {
        $sma = $this->calculate_sma($data, $period);
        $bands = array();
        
        for ($i = $period - 1; $i < count($data); $i++) {
            $slice = array_slice($data, $i - $period + 1, $period);
            $mean = $sma[$i - $period + 1];
            
            $variance = 0;
            foreach ($slice as $point) {
                $variance += pow($point['close'] - $mean, 2);
            }
            $std = sqrt($variance / $period);
            
            $bands[] = array(
                'upper' => $mean + ($std * $std_dev),
                'middle' => $mean,
                'lower' => $mean - ($std * $std_dev)
            );
        }
        
        return $bands;
    }
    
    /**
     * Calculate Stochastic Oscillator
     */
    private function calculate_stochastic($data, $k_period, $d_period) {
        $k_values = array();
        
        for ($i = $k_period - 1; $i < count($data); $i++) {
            $slice = array_slice($data, $i - $k_period + 1, $k_period);
            $high = max(array_column($slice, 'high'));
            $low = min(array_column($slice, 'low'));
            $close = $data[$i]['close'];
            
            $k_values[] = (($close - $low) / ($high - $low)) * 100;
        }
        
        $d_values = $this->calculate_sma(array_map(function($val) {
            return array('close' => $val);
        }, $k_values), $d_period);
        
        return array(
            'k' => $k_values,
            'd' => $d_values
        );
    }
    
    /**
     * Calculate Williams %R
     */
    private function calculate_williams_r($data, $period) {
        $williams_r = array();
        
        for ($i = $period - 1; $i < count($data); $i++) {
            $slice = array_slice($data, $i - $period + 1, $period);
            $high = max(array_column($slice, 'high'));
            $low = min(array_column($slice, 'low'));
            $close = $data[$i]['close'];
            
            $williams_r[] = (($high - $close) / ($high - $low)) * -100;
        }
        
        return $williams_r;
    }
    
    /**
     * Calculate Average True Range
     */
    private function calculate_atr($data, $period) {
        $true_ranges = array();
        
        for ($i = 1; $i < count($data); $i++) {
            $high = $data[$i]['high'];
            $low = $data[$i]['low'];
            $prev_close = $data[$i - 1]['close'];
            
            $tr1 = $high - $low;
            $tr2 = abs($high - $prev_close);
            $tr3 = abs($low - $prev_close);
            
            $true_ranges[] = max($tr1, $tr2, $tr3);
        }
        
        return $this->calculate_sma(array_map(function($val) {
            return array('close' => $val);
        }, $true_ranges), $period);
    }
    
    /**
     * Get market sentiment
     */
    public function get_market_sentiment($pair) {
        $indicators = $this->get_technical_indicators($pair, '1', 50);
        
        $sentiment_score = 0;
        $factors = 0;
        
        // RSI analysis
        if (!empty($indicators['rsi'])) {
            $rsi = end($indicators['rsi']);
            if ($rsi > 70) {
                $sentiment_score -= 1;
            } elseif ($rsi < 30) {
                $sentiment_score += 1;
            }
            $factors++;
        }
        
        // MACD analysis
        if (!empty($indicators['macd']['macd']) && !empty($indicators['macd']['signal'])) {
            $macd = end($indicators['macd']['macd']);
            $signal = end($indicators['macd']['signal']);
            if ($macd > $signal) {
                $sentiment_score += 0.5;
            } else {
                $sentiment_score -= 0.5;
            }
            $factors++;
        }
        
        // Bollinger Bands analysis
        if (!empty($indicators['bollinger_bands'])) {
            $bands = end($indicators['bollinger_bands']);
            $data = $this->get_ohlc_data($pair, '1', time() - 3600, time());
            if (!empty($data)) {
                $current_price = end($data)['close'];
                if ($current_price > $bands['upper']) {
                    $sentiment_score -= 0.5;
                } elseif ($current_price < $bands['lower']) {
                    $sentiment_score += 0.5;
                }
                $factors++;
            }
        }
        
        $sentiment = $factors > 0 ? $sentiment_score / $factors : 0;
        
        if ($sentiment > 0.3) {
            return 'Bullish';
        } elseif ($sentiment < -0.3) {
            return 'Bearish';
        } else {
            return 'Neutral';
        }
    }
}
    /**
     * Calculate Stochastic Oscillator
     */
    private function calculate_stochastic($data, $k_period, $d_period) {
        $k_values = array();
        
        for ($i = $k_period - 1; $i < count($data); $i++) {
            $slice = array_slice($data, $i - $k_period + 1, $k_period);
            $high = max(array_column($slice, 'high'));
            $low = min(array_column($slice, 'low'));
            $close = $data[$i]['close'];
            
            $k_values[] = (($close - $low) / ($high - $low)) * 100;
        }
        
        $d_values = $this->calculate_sma(array_map(function($val) {
            return array('close' => $val);
        }, $k_values), $d_period);
        
        return array(
            'k' => $k_values,
            'd' => $d_values
        );
    }
    
    /**
     * Calculate Williams %R
     */
    private function calculate_williams_r($data, $period) {
        $williams_r = array();
        
        for ($i = $period - 1; $i < count($data); $i++) {
            $slice = array_slice($data, $i - $period + 1, $period);
            $high = max(array_column($slice, 'high'));
            $low = min(array_column($slice, 'low'));
            $close = $data[$i]['close'];
            
            $williams_r[] = (($high - $close) / ($high - $low)) * -100;
        }
        
        return $williams_r;
    }
    
    /**
     * Calculate Average True Range
     */
    private function calculate_atr($data, $period) {
        $true_ranges = array();
        
        for ($i = 1; $i < count($data); $i++) {
            $high = $data[$i]['high'];
            $low = $data[$i]['low'];
            $prev_close = $data[$i - 1]['close'];
            
            $tr1 = $high - $low;
            $tr2 = abs($high - $prev_close);
            $tr3 = abs($low - $prev_close);
            
            $true_ranges[] = max($tr1, $tr2, $tr3);
        }
        
        return $this->calculate_sma(array_map(function($val) {
            return array('close' => $val);
        }, $true_ranges), $period);
    }
    
    /**
     * Get market sentiment
     */
    public function get_market_sentiment($pair) {
        $indicators = $this->get_technical_indicators($pair, '1', 50);
        
        $sentiment_score = 0;
        $factors = 0;
        
        // RSI analysis
        if (!empty($indicators['rsi'])) {
            $rsi = end($indicators['rsi']);
            if ($rsi > 70) {
                $sentiment_score -= 1;
            } elseif ($rsi < 30) {
                $sentiment_score += 1;
            }
            $factors++;
        }
        
        // MACD analysis
        if (!empty($indicators['macd']['macd']) && !empty($indicators['macd']['signal'])) {
            $macd = end($indicators['macd']['macd']);
            $signal = end($indicators['macd']['signal']);
            if ($macd > $signal) {
                $sentiment_score += 0.5;
            } else {
                $sentiment_score -= 0.5;
            }
            $factors++;
        }
        
        // Bollinger Bands analysis
        if (!empty($indicators['bollinger_bands'])) {
            $bands = end($indicators['bollinger_bands']);
            $data = $this->get_ohlc_data($pair, '1', time() - 3600, time());
            if (!empty($data)) {
                $current_price = end($data)['close'];
                if ($current_price > $bands['upper']) {
                    $sentiment_score -= 0.5;
                } elseif ($current_price < $bands['lower']) {
                    $sentiment_score += 0.5;
                }
                $factors++;
            }
        }
        
        $sentiment = $factors > 0 ? $sentiment_score / $factors : 0;
        
        if ($sentiment > 0.3) {
            return 'Bullish';
        } elseif ($sentiment < -0.3) {
            return 'Bearish';
        } else {
            return 'Neutral';
        }
    }
}
