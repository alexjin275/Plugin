<?php
/**
 * Real-time Notifications and Alerts System
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crypto_Exchange_Notifications {
    
    private $wpdb;
    private $notification_config;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        $this->init_notification_config();
        add_action('crypto_exchange_send_notifications', array($this, 'send_pending_notifications'));
        add_action('wp_ajax_crypto_exchange_get_notifications', array($this, 'get_notifications'));
        add_action('wp_ajax_crypto_exchange_mark_notification_read', array($this, 'mark_notification_read'));
        add_action('wp_ajax_crypto_exchange_update_notification_settings', array($this, 'update_notification_settings'));
        
        if (!wp_next_scheduled('crypto_exchange_send_notifications')) {
            wp_schedule_event(time(), 'every_minute', 'crypto_exchange_send_notifications');
        }
    }
    
    /**
     * Initialize notification configuration
     */
    private function init_notification_config() {
        $this->notification_config = array(
            'enable_email' => get_option('crypto_exchange_email_notifications', true),
            'enable_sms' => get_option('crypto_exchange_sms_notifications', false),
            'enable_push' => get_option('crypto_exchange_push_notifications', true),
            'enable_in_app' => get_option('crypto_exchange_in_app_notifications', true),
            'email_from' => get_option('crypto_exchange_email_from', get_bloginfo('admin_email')),
            'email_from_name' => get_option('crypto_exchange_email_from_name', get_bloginfo('name')),
            'sms_provider' => get_option('crypto_exchange_sms_provider', 'twilio'),
            'push_provider' => get_option('crypto_exchange_push_provider', 'firebase'),
            'notification_types' => array(
                'trade_executed' => array(
                    'name' => 'Trade Executed',
                    'default_enabled' => true,
                    'channels' => array('email', 'push', 'in_app')
                ),
                'order_filled' => array(
                    'name' => 'Order Filled',
                    'default_enabled' => true,
                    'channels' => array('email', 'push', 'in_app')
                ),
                'order_cancelled' => array(
                    'name' => 'Order Cancelled',
                    'default_enabled' => true,
                    'channels' => array('email', 'push', 'in_app')
                ),
                'deposit_received' => array(
                    'name' => 'Deposit Received',
                    'default_enabled' => true,
                    'channels' => array('email', 'push', 'in_app')
                ),
                'withdrawal_completed' => array(
                    'name' => 'Withdrawal Completed',
                    'default_enabled' => true,
                    'channels' => array('email', 'push', 'in_app')
                ),
                'kyc_approved' => array(
                    'name' => 'KYC Approved',
                    'default_enabled' => true,
                    'channels' => array('email', 'push', 'in_app')
                ),
                'kyc_rejected' => array(
                    'name' => 'KYC Rejected',
                    'default_enabled' => true,
                    'channels' => array('email', 'push', 'in_app')
                ),
                'price_alert' => array(
                    'name' => 'Price Alert',
                    'default_enabled' => true,
                    'channels' => array('email', 'push', 'in_app')
                ),
                'security_alert' => array(
                    'name' => 'Security Alert',
                    'default_enabled' => true,
                    'channels' => array('email', 'sms', 'push', 'in_app')
                ),
                'risk_alert' => array(
                    'name' => 'Risk Alert',
                    'default_enabled' => true,
                    'channels' => array('email', 'push', 'in_app')
                )
            )
        );
    }
    
    /**
     * Send pending notifications
     */
    public function send_pending_notifications() {
        $pending_notifications = $this->wpdb->get_results(
            "SELECT * FROM {$this->wpdb->prefix}crypto_notifications 
             WHERE status = 'pending' AND scheduled_at <= NOW() 
             ORDER BY created_at ASC LIMIT 100"
        );
        
        foreach ($pending_notifications as $notification) {
            $this->send_notification($notification);
        }
    }
    
    /**
     * Send individual notification
     */
    private function send_notification($notification) {
        $user_id = $notification->user_id;
        $type = $notification->type;
        $channels = json_decode($notification->channels, true);
        
        // Get user notification preferences
        $user_preferences = $this->get_user_notification_preferences($user_id);
        
        foreach ($channels as $channel) {
            if ($this->should_send_notification($user_id, $type, $channel, $user_preferences)) {
                $this->send_notification_to_channel($notification, $channel);
            }
        }
        
        // Mark notification as sent
        $this->mark_notification_sent($notification->id);
    }
    
    /**
     * Send notification to specific channel
     */
    private function send_notification_to_channel($notification, $channel) {
        switch ($channel) {
            case 'email':
                $this->send_email_notification($notification);
                break;
            case 'sms':
                $this->send_sms_notification($notification);
                break;
            case 'push':
                $this->send_push_notification($notification);
                break;
            case 'in_app':
                $this->send_in_app_notification($notification);
                break;
        }
    }
    
    /**
     * Send email notification
     */
    private function send_email_notification($notification) {
        if (!$this->notification_config['enable_email']) {
            return;
        }
        
        $user = get_user_by('id', $notification->user_id);
        if (!$user) {
            return;
        }
        
        $subject = $notification->title;
        $message = $this->format_email_message($notification);
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->notification_config['email_from_name'] . ' <' . $this->notification_config['email_from'] . '>'
        );
        
        wp_mail($user->user_email, $subject, $message, $headers);
    }
    
    /**
     * Send SMS notification
     */
    private function send_sms_notification($notification) {
        if (!$this->notification_config['enable_sms']) {
            return;
        }
        
        $user = get_user_by('id', $notification->user_id);
        if (!$user) {
            return;
        }
        
        $phone = get_user_meta($user->ID, 'crypto_phone_number', true);
        if (!$phone) {
            return;
        }
        
        $message = $notification->title . ': ' . $notification->message;
        
        switch ($this->notification_config['sms_provider']) {
            case 'twilio':
                $this->send_sms_twilio($phone, $message);
                break;
            case 'aws_sns':
                $this->send_sms_aws_sns($phone, $message);
                break;
        }
    }
    
    /**
     * Send push notification
     */
    private function send_push_notification($notification) {
        if (!$this->notification_config['enable_push']) {
            return;
        }
        
        $user = get_user_by('id', $notification->user_id);
        if (!$user) {
            return;
        }
        
        $device_tokens = $this->get_user_device_tokens($user->ID);
        if (empty($device_tokens)) {
            return;
        }
        
        $payload = array(
            'title' => $notification->title,
            'body' => $notification->message,
            'data' => json_decode($notification->data, true),
            'icon' => get_site_icon_url(),
            'badge' => $this->get_user_unread_count($user->ID)
        );
        
        switch ($this->notification_config['push_provider']) {
            case 'firebase':
                $this->send_push_firebase($device_tokens, $payload);
                break;
            case 'onesignal':
                $this->send_push_onesignal($device_tokens, $payload);
                break;
        }
    }
    
    /**
     * Send in-app notification
     */
    private function send_in_app_notification($notification) {
        if (!$this->notification_config['enable_in_app']) {
            return;
        }
        
        // In-app notifications are stored in database and retrieved via AJAX
        // No additional action needed here
    }
    
    /**
     * Create notification
     */
    public function create_notification($user_id, $type, $title, $message, $data = array(), $channels = null, $priority = 'normal') {
        if (!$channels) {
            $channels = $this->get_default_channels_for_type($type);
        }
        
        $this->wpdb->insert(
            $this->wpdb->prefix . 'crypto_notifications',
            array(
                'user_id' => $user_id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => json_encode($data),
                'channels' => json_encode($channels),
                'priority' => $priority,
                'status' => 'pending',
                'scheduled_at' => current_time('mysql'),
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        return $this->wpdb->insert_id;
    }
    
    /**
     * Get notifications for user
     */
    public function get_notifications() {
        if (!is_user_logged_in()) {
            wp_send_json_error('Not logged in');
        }
        
        $user_id = get_current_user_id();
        $page = intval($_POST['page'] ?? 1);
        $limit = intval($_POST['limit'] ?? 20);
        $offset = ($page - 1) * $limit;
        
        $notifications = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}crypto_notifications 
                 WHERE user_id = %d 
                 ORDER BY created_at DESC 
                 LIMIT %d OFFSET %d",
                $user_id,
                $limit,
                $offset
            )
        );
        
        $total = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->wpdb->prefix}crypto_notifications WHERE user_id = %d",
                $user_id
            )
        );
        
        wp_send_json_success(array(
            'notifications' => $notifications,
            'total' => $total,
            'page' => $page,
            'pages' => ceil($total / $limit)
        ));
    }
    
    /**
     * Mark notification as read
     */
    public function mark_notification_read() {
        if (!is_user_logged_in()) {
            wp_send_json_error('Not logged in');
        }
        
        $user_id = get_current_user_id();
        $notification_id = intval($_POST['notification_id']);
        
        $this->wpdb->update(
            $this->wpdb->prefix . 'crypto_notifications',
            array('read_at' => current_time('mysql')),
            array('id' => $notification_id, 'user_id' => $user_id),
            array('%s'),
            array('%d', '%d')
        );
        
        wp_send_json_success('Notification marked as read');
    }
    
    /**
     * Update notification settings
     */
    public function update_notification_settings() {
        if (!is_user_logged_in()) {
            wp_send_json_error('Not logged in');
        }
        
        $user_id = get_current_user_id();
        $settings = $_POST['settings'];
        
        update_user_meta($user_id, 'crypto_notification_settings', $settings);
        
        wp_send_json_success('Notification settings updated');
    }
    
    /**
     * Get user notification preferences
     */
    private function get_user_notification_preferences($user_id) {
        $preferences = get_user_meta($user_id, 'crypto_notification_settings', true);
        
        if (!$preferences) {
            $preferences = $this->get_default_notification_preferences();
        }
        
        return $preferences;
    }
    
    /**
     * Get default notification preferences
     */
    private function get_default_notification_preferences() {
        $preferences = array();
        
        foreach ($this->notification_config['notification_types'] as $type => $config) {
            $preferences[$type] = array(
                'enabled' => $config['default_enabled'],
                'channels' => $config['channels']
            );
        }
        
        return $preferences;
    }
    
    /**
     * Check if notification should be sent
     */
    private function should_send_notification($user_id, $type, $channel, $preferences) {
        if (!isset($preferences[$type])) {
            return false;
        }
        
        if (!$preferences[$type]['enabled']) {
            return false;
        }
        
        if (!in_array($channel, $preferences[$type]['channels'])) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get default channels for notification type
     */
    private function get_default_channels_for_type($type) {
        if (isset($this->notification_config['notification_types'][$type])) {
            return $this->notification_config['notification_types'][$type]['channels'];
        }
        
        return array('email', 'in_app');
    }
    
    /**
     * Format email message
     */
    private function format_email_message($notification) {
        $template = $this->get_email_template($notification->type);
        
        $placeholders = array(
            '{title}' => $notification->title,
            '{message}' => $notification->message,
            '{data}' => $notification->data,
            '{site_name}' => get_bloginfo('name'),
            '{site_url}' => home_url(),
            '{user_name}' => get_user_by('id', $notification->user_id)->display_name
        );
        
        return str_replace(array_keys($placeholders), array_values($placeholders), $template);
    }
    
    /**
     * Get email template
     */
    private function get_email_template($type) {
        $templates = array(
            'trade_executed' => '
                <h2>Trade Executed</h2>
                <p>Hello {user_name},</p>
                <p>Your trade has been executed successfully.</p>
                <p><strong>Details:</strong></p>
                <p>{message}</p>
                <p>Thank you for trading with {site_name}.</p>
            ',
            'deposit_received' => '
                <h2>Deposit Received</h2>
                <p>Hello {user_name},</p>
                <p>We have received your deposit.</p>
                <p><strong>Details:</strong></p>
                <p>{message}</p>
                <p>Thank you for using {site_name}.</p>
            ',
            'kyc_approved' => '
                <h2>KYC Verification Approved</h2>
                <p>Hello {user_name},</p>
                <p>Congratulations! Your KYC verification has been approved.</p>
                <p>You now have access to higher trading limits and additional features.</p>
                <p>Thank you for using {site_name}.</p>
            ',
            'security_alert' => '
                <h2>Security Alert</h2>
                <p>Hello {user_name},</p>
                <p>We have detected unusual activity on your account.</p>
                <p><strong>Details:</strong></p>
                <p>{message}</p>
                <p>If this was not you, please contact support immediately.</p>
            '
        );
        
        return $templates[$type] ?? '<h2>{title}</h2><p>{message}</p>';
    }
    
    /**
     * Send SMS via Twilio
     */
    private function send_sms_twilio($phone, $message) {
        $account_sid = get_option('crypto_exchange_twilio_account_sid', '');
        $auth_token = get_option('crypto_exchange_twilio_auth_token', '');
        $from_number = get_option('crypto_exchange_twilio_from_number', '');
        
        if (!$account_sid || !$auth_token || !$from_number) {
            return;
        }
        
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$account_sid}/Messages.json";
        
        $data = array(
            'From' => $from_number,
            'To' => $phone,
            'Body' => $message
        );
        
        wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($account_sid . ':' . $auth_token)
            ),
            'body' => $data,
            'timeout' => 30
        ));
    }
    
    /**
     * Send SMS via AWS SNS
     */
    private function send_sms_aws_sns($phone, $message) {
        $access_key = get_option('crypto_exchange_aws_access_key', '');
        $secret_key = get_option('crypto_exchange_aws_secret_key', '');
        $region = get_option('crypto_exchange_aws_region', 'us-east-1');
        
        if (!$access_key || !$secret_key) {
            return;
        }
        
        // AWS SNS implementation would go here
        // This is a simplified version
    }
    
    /**
     * Send push notification via Firebase
     */
    private function send_push_firebase($device_tokens, $payload) {
        $server_key = get_option('crypto_exchange_firebase_server_key', '');
        
        if (!$server_key) {
            return;
        }
        
        $data = array(
            'registration_ids' => $device_tokens,
            'notification' => array(
                'title' => $payload['title'],
                'body' => $payload['body'],
                'icon' => $payload['icon'],
                'badge' => $payload['badge']
            ),
            'data' => $payload['data']
        );
        
        wp_remote_post('https://fcm.googleapis.com/fcm/send', array(
            'headers' => array(
                'Authorization' => 'key=' . $server_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($data),
            'timeout' => 30
        ));
    }
    
    /**
     * Send push notification via OneSignal
     */
    private function send_push_onesignal($device_tokens, $payload) {
        $app_id = get_option('crypto_exchange_onesignal_app_id', '');
        $rest_api_key = get_option('crypto_exchange_onesignal_rest_api_key', '');
        
        if (!$app_id || !$rest_api_key) {
            return;
        }
        
        $data = array(
            'app_id' => $app_id,
            'include_player_ids' => $device_tokens,
            'headings' => array('en' => $payload['title']),
            'contents' => array('en' => $payload['body']),
            'data' => $payload['data']
        );
        
        wp_remote_post('https://onesignal.com/api/v1/notifications', array(
            'headers' => array(
                'Authorization' => 'Basic ' . $rest_api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($data),
            'timeout' => 30
        ));
    }
    
    /**
     * Get user device tokens
     */
    private function get_user_device_tokens($user_id) {
        return get_user_meta($user_id, 'crypto_device_tokens', true) ?: array();
    }
    
    /**
     * Get user unread notification count
     */
    private function get_user_unread_count($user_id) {
        return $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->wpdb->prefix}crypto_notifications 
                 WHERE user_id = %d AND read_at IS NULL",
                $user_id
            )
        );
    }
    
    /**
     * Mark notification as sent
     */
    private function mark_notification_sent($notification_id) {
        $this->wpdb->update(
            $this->wpdb->prefix . 'crypto_notifications',
            array('status' => 'sent', 'sent_at' => current_time('mysql')),
            array('id' => $notification_id),
            array('%s', '%s'),
            array('%d')
        );
    }
    
    /**
     * Create notification tables
     */
    public function create_notification_tables() {
        // Notifications table
        $sql = "CREATE TABLE IF NOT EXISTS {$this->wpdb->prefix}crypto_notifications (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            type varchar(50) NOT NULL,
            title varchar(255) NOT NULL,
            message text NOT NULL,
            data text,
            channels text,
            priority varchar(20) DEFAULT 'normal',
            status varchar(20) DEFAULT 'pending',
            scheduled_at datetime DEFAULT CURRENT_TIMESTAMP,
            sent_at datetime DEFAULT NULL,
            read_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY type (type),
            KEY status (status),
            KEY scheduled_at (scheduled_at)
        ) " . $this->wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
    /**
     * Get user device tokens
     */
    private function get_user_device_tokens($user_id) {
        return get_user_meta($user_id, 'crypto_device_tokens', true) ?: array();
    }
    
    /**
     * Get user unread notification count
     */
    private function get_user_unread_count($user_id) {
        return $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->wpdb->prefix}crypto_notifications 
                 WHERE user_id = %d AND read_at IS NULL",
                $user_id
            )
        );
    }
    
    /**
     * Mark notification as sent
     */
    private function mark_notification_sent($notification_id) {
        $this->wpdb->update(
            $this->wpdb->prefix . 'crypto_notifications',
            array('status' => 'sent', 'sent_at' => current_time('mysql')),
            array('id' => $notification_id),
            array('%s', '%s'),
            array('%d')
        );
    }
    
    /**
     * Create notification tables
     */
    public function create_notification_tables() {
        // Notifications table
        $sql = "CREATE TABLE IF NOT EXISTS {$this->wpdb->prefix}crypto_notifications (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            type varchar(50) NOT NULL,
            title varchar(255) NOT NULL,
            message text NOT NULL,
            data text,
            channels text,
            priority varchar(20) DEFAULT 'normal',
            status varchar(20) DEFAULT 'pending',
            scheduled_at datetime DEFAULT CURRENT_TIMESTAMP,
            sent_at datetime DEFAULT NULL,
            read_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY type (type),
            KEY status (status),
            KEY scheduled_at (scheduled_at)
        ) " . $this->wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
