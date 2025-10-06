<?php
/**
 * WebSocket class for real-time trading
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crypto_Exchange_WebSocket {
    
    private $wpdb;
    private $clients = array();
    private $rooms = array();
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        add_action('wp_ajax_crypto_exchange_websocket', array($this, 'handle_websocket'));
        add_action('wp_ajax_nopriv_crypto_exchange_websocket', array($this, 'handle_websocket'));
        add_action('init', array($this, 'init_websocket'));
    }
    
    /**
     * Initialize WebSocket
     */
    public function init_websocket() {
        if (isset($_GET['crypto_ws']) && $_GET['crypto_ws'] === '1') {
            $this->start_websocket_server();
        }
    }
    
    /**
     * Start WebSocket server
     */
    private function start_websocket_server() {
        // Set headers for WebSocket
        header('Upgrade: websocket');
        header('Connection: Upgrade');
        header('Sec-WebSocket-Accept: ' . $this->generate_accept_key($_SERVER['HTTP_SEC_WEBSOCKET_KEY']));
        header('Sec-WebSocket-Protocol: chat');
        
        // Start WebSocket server
        $this->websocket_server();
    }
    
    /**
     * Generate WebSocket accept key
     */
    private function generate_accept_key($key) {
        return base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
    }
    
    /**
     * WebSocket server loop
     */
    private function websocket_server() {
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($socket, '0.0.0.0', 8080);
        socket_listen($socket);
        
        while (true) {
            $client = socket_accept($socket);
            $this->handle_client($client);
        }
    }
    
    /**
     * Handle WebSocket client
     */
    private function handle_client($client) {
        $client_id = uniqid();
        $this->clients[$client_id] = $client;
        
        while (true) {
            $data = socket_read($client, 1024);
            if ($data === false) break;
            
            $message = $this->decode_message($data);
            if ($message) {
                $this->process_message($client_id, $message);
            }
        }
        
        unset($this->clients[$client_id]);
        socket_close($client);
    }
    
    /**
     * Decode WebSocket message
     */
    private function decode_message($data) {
        $length = ord($data[1]) & 127;
        if ($length == 126) {
            $masks = substr($data, 4, 4);
            $data = substr($data, 8);
        } elseif ($length == 127) {
            $masks = substr($data, 10, 4);
            $data = substr($data, 14);
        } else {
            $masks = substr($data, 2, 4);
            $data = substr($data, 6);
        }
        
        $decoded = '';
        for ($i = 0; $i < strlen($data); ++$i) {
            $decoded .= $data[$i] ^ $masks[$i % 4];
        }
        
        return json_decode($decoded, true);
    }
    
    /**
     * Encode WebSocket message
     */
    private function encode_message($message) {
        $message = json_encode($message);
        $length = strlen($message);
        
        if ($length < 126) {
            $header = chr(129) . chr($length);
        } elseif ($length < 65536) {
            $header = chr(129) . chr(126) . pack('n', $length);
        } else {
            $header = chr(129) . chr(127) . pack('J', $length);
        }
        
        return $header . $message;
    }
    
    /**
     * Process WebSocket message
     */
    private function process_message($client_id, $message) {
        $action = $message['action'] ?? '';
        
        switch ($action) {
            case 'subscribe':
                $this->subscribe_to_room($client_id, $message['room']);
                break;
            case 'unsubscribe':
                $this->unsubscribe_from_room($client_id, $message['room']);
                break;
            case 'ping':
                $this->send_to_client($client_id, array('action' => 'pong'));
                break;
            case 'order':
                $this->handle_order_message($client_id, $message);
                break;
        }
    }
    
    /**
     * Subscribe to room
     */
    private function subscribe_to_room($client_id, $room) {
        if (!isset($this->rooms[$room])) {
            $this->rooms[$room] = array();
        }
        $this->rooms[$room][] = $client_id;
    }
    
    /**
     * Unsubscribe from room
     */
    private function unsubscribe_from_room($client_id, $room) {
        if (isset($this->rooms[$room])) {
            $this->rooms[$room] = array_diff($this->rooms[$room], array($client_id));
        }
    }
    
    /**
     * Send message to client
     */
    private function send_to_client($client_id, $message) {
        if (isset($this->clients[$client_id])) {
            $encoded = $this->encode_message($message);
            socket_write($this->clients[$client_id], $encoded);
        }
    }
    
    /**
     * Broadcast message to room
     */
    public function broadcast_to_room($room, $message) {
        if (isset($this->rooms[$room])) {
            foreach ($this->rooms[$room] as $client_id) {
                $this->send_to_client($client_id, $message);
            }
        }
    }
    
    /**
     * Handle order message
     */
    private function handle_order_message($client_id, $message) {
        // Process order and broadcast updates
        $trading = new Crypto_Exchange_Trading();
        $result = $trading->place_order($message['data']);
        
        // Broadcast order update
        $this->broadcast_to_room('orders', array(
            'action' => 'order_update',
            'data' => $result
        ));
        
        // Broadcast market data update
        $this->broadcast_to_room('market_data', array(
            'action' => 'price_update',
            'data' => $this->get_latest_prices()
        ));
    }
    
    /**
     * Get latest prices
     */
    private function get_latest_prices() {
        $market_data = new Crypto_Exchange_Market_Data();
        return $market_data->get_all_market_data();
    }
    
    /**
     * Start price updates
     */
    public function start_price_updates() {
        add_action('crypto_exchange_update_prices', array($this, 'update_prices'));
        
        if (!wp_next_scheduled('crypto_exchange_update_prices')) {
            wp_schedule_event(time(), 'every_second', 'crypto_exchange_update_prices');
        }
    }
    
    /**
     * Update prices and broadcast
     */
    public function update_prices() {
        $market_data = new Crypto_Exchange_Market_Data();
        $market_data->update_all_prices();
        
        $prices = $market_data->get_all_market_data();
        
        $this->broadcast_to_room('market_data', array(
            'action' => 'price_update',
            'data' => $prices,
            'timestamp' => time()
        ));
    }
    
    /**
     * Add custom cron intervals
     */
    public function add_cron_intervals($schedules) {
        $schedules['every_second'] = array(
            'interval' => 1,
            'display' => __('Every Second')
        );
        return $schedules;
    }
    
    /**
     * Handle WebSocket AJAX
     */
    public function handle_websocket() {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'get_connection_info':
                wp_send_json_success(array(
                    'ws_url' => home_url('/?crypto_ws=1'),
                    'rooms' => array('market_data', 'orders', 'trades')
                ));
                break;
        }
    }
}
