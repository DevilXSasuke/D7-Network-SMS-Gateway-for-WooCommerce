<?php
/**
 * Plugin Name: D7 Network SMS Gateway
 * Plugin URI: https://app.d7networks.com/signin
 * Description: WooCommerce SMS plugin using D7 Networks with customizable templates, logs, status toggles, and order notes.
 * Version: 1.7
 * Author: DevilXSasuke
 * Author URI: https://github.com/DevilXSasuke
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ─── Activation: Create Logs Table ───────────────────────────────────────────
register_activation_hook( __FILE__, function(){
	global $wpdb;
	$table = $wpdb->prefix . 'd7sms_logs';
	$charset = $wpdb->get_charset_collate();
	$sql = "CREATE TABLE $table (
		id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		phone VARCHAR(30),
		message TEXT,
		status VARCHAR(20),
		response TEXT,
		user VARCHAR(100),
		sent_at DATETIME DEFAULT CURRENT_TIMESTAMP
	) $charset;";
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
});

add_action('plugins_loaded', function(){
	global $wpdb;
	$table = $wpdb->prefix . 'd7sms_logs';
	if( ! $wpdb->get_var("SHOW COLUMNS FROM `$table` LIKE 'user'") ) {
		$wpdb->query("ALTER TABLE `$table` ADD `user` VARCHAR(100)");
	}
});

// ─── Send & Log SMS ─────────────────────────────────────────────────────────
if ( ! function_exists( 'd7sms_send_sms' ) ) {
    function d7sms_send_sms($phone, $message, $user = null){
        $token  = get_option('d7sms_access_token');
        $sender = get_option('d7sms_sender_id');
        if(!$token||!$sender||!$phone||!$message) return 'Skipped';

        $args = [
            'headers'=>[
                'Authorization'=>"Bearer $token",
                'Content-Type'=>'application/json',
            ],
            'body' => json_encode([
                'messages'=>[[
                    'channel'=>'sms',
                    'msg_type'=>'text',
                    'recipients'=>[$phone],
                    'content'=>$message
                ]],
                'message_globals'=>['originator'=>$sender]
            ])
        ];

        $res = wp_remote_post('https://api.d7networks.com/messages/v1/send',$args);
        $status = 'Failed';
        $body   = '';
        if( !is_wp_error($res) && wp_remote_retrieve_response_code($res)===200 ){
            $status = 'Sent';
            $body   = wp_remote_retrieve_body($res);
        } elseif ( is_wp_error($res) ) {
            $body = $res->get_error_message();
        } else {
            $body = wp_remote_retrieve_body($res);
        }

        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix.'d7sms_logs',
            [ 
                'phone'   => $phone, 
                'message' => $message, 
                'status'  => $status, 
                'response'=> $body,
                'user'    => $user
            ]
        );

        return $status;
    }
}

// ─── Admin Menu ──────────────────────────────────────────────────────────────
add_action( 'admin_menu', function(){
	add_menu_page( 
        'D7 SMS Gateway', 
        'D7 SMS Settings', 
        'manage_options', 
        'd7sms_settings', 
        'd7sms_settings_page', 
        'dashicons-email-alt', 
        60 
    );
	add_submenu_page( 
        'd7sms_settings', 
        'SMS Logs', 
        'SMS Logs', 
        'manage_options', 
        'd7sms_logs', 
        'd7sms_logs_page' 
    );
});

// ─── Settings Page ──────────────────────────────────────────────────────────
function d7sms_settings_page(){
	// Save settings
	if( isset($_POST['save_d7sms']) ){
		check_admin_referer('d7sms_save');
		update_option('d7sms_access_token', sanitize_text_field($_POST['access_token']));
		update_option('d7sms_sender_id',    sanitize_text_field($_POST['sender_id']));
		update_option('d7sms_admin_phones', sanitize_text_field($_POST['admin_phones']));
		update_option('d7sms_enabled_statuses', $_POST['enabled_statuses'] ?: [] );
		update_option('d7sms_customer_templates', $_POST['customer_templates'] ?: [] );
		update_option('d7sms_admin_templates', $_POST['admin_templates'] ?: [] );
		echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
	}

	// Send Test SMS
	if( isset($_POST['send_test']) ){
		check_admin_referer('d7sms_test');
		$test_phone = sanitize_text_field($_POST['test_phone']);
		$test_message = sanitize_textarea_field($_POST['test_message']);
		$sent = d7sms_send_sms($test_phone, $test_message, 'Test');
		echo '<div class="notice notice-success"><p>Test SMS status: '.esc_html($sent).'</p></div>';
	}
    
    // Load existing settings
	$statuses = wc_get_order_statuses();
	$token    = get_option('d7sms_access_token','');
	$sender   = get_option('d7sms_sender_id','');
	$admins   = get_option('d7sms_admin_phones','');
	$enabled  = get_option('d7sms_enabled_statuses',[]);
	$cust_tm  = get_option('d7sms_customer_templates',[]);
	$admin_tm = get_option('d7sms_admin_templates',[]);

	?>
	<div class="wrap">
		<h1>D7 SMS Gateway Settings</h1>
		<form method="post">
			<?php wp_nonce_field('d7sms_save'); ?>
			<h2>General</h2>
			<table class="form-table">
				<tr><th>Access Token</th><td><input type="password" name="access_token" value="<?php echo esc_attr($token);?>" style="width:400px;"></td></tr>
				<tr><th>Sender ID</th><td><input type="text" name="sender_id" value="<?php echo esc_attr($sender);?>"></td></tr>
				<tr><th>Admin Phones</th>
					<td><input type="text" name="admin_phones" value="<?php echo esc_attr($admins);?>" placeholder="+9715xxxxxxx,..." style="width:400px;"><br><small>comma-separated</small></td>
				</tr>
			</table>
			<h2>Enabled Notifications</h2>
			<table class="form-table">
				<tr><th>Status</th><th>Customer</th><th>Admin</th></tr>
				<?php foreach($statuses as $key=>$label): ?>
				<tr>
					<th><?php echo esc_html($label);?></th>
					<td><input type="checkbox" name="enabled_statuses[<?php echo esc_attr($key);?>][customer]" <?php checked( !empty($enabled[$key]['customer']) );?>></td>
					<td><input type="checkbox" name="enabled_statuses[<?php echo esc_attr($key);?>][admin]"    <?php checked( !empty($enabled[$key]['admin']) );?>></td>
				</tr>
				<?php endforeach;?>
			</table>
			<h2>Customer Templates</h2>
			<table class="form-table">
				<?php foreach($statuses as $key=>$label): ?>
				<tr>
					<th><?php echo esc_html($label);?></th>
					<td><textarea name="customer_templates[<?php echo esc_attr($key);?>]" rows="2" cols="60"><?php echo esc_textarea($cust_tm[$key] ?? "Your order #{order_id} is now {order_status}.");?></textarea></td>
				</tr>
				<?php endforeach;?>
			</table>
			<h2>Admin Templates</h2>
			<table class="form-table">
				<?php foreach($statuses as $key=>$label): ?>
				<tr>
					<th><?php echo esc_html($label);?></th>
					<td><textarea name="admin_templates[<?php echo esc_attr($key);?>]" rows="2" cols="60"><?php echo esc_textarea($admin_tm[$key] ?? "Order #{order_id} is now {order_status}.");?></textarea></td>
				</tr>
				<?php endforeach;?>
			</table>
			<?php submit_button('Save Settings','primary','save_d7sms'); ?>
		</form>

		<h2>Send Test SMS</h2>
		<form method="post">
			<?php wp_nonce_field('d7sms_test');?>
			<input type="text" name="test_phone" placeholder="+9715xxxxxxx" style="width:300px;">
			<br>
			<textarea name="test_message" rows="2" cols="50" placeholder="Hello world"></textarea>
			<br>
			<?php submit_button('Send Test','secondary','send_test',false); ?>
		</form>
	</div>
	<?php
}

// ─── Logs Page ───────────────────────────────────────────────────────────────
function d7sms_logs_page(){
	global $wpdb;
	$table = $wpdb->prefix.'d7sms_logs';
	$rows  = $wpdb->get_results("SELECT * FROM $table ORDER BY sent_at DESC LIMIT 100");
	echo '<div class="wrap"><h1>SMS Logs</h1><table class="widefat"><thead><tr><th>ID</th><th>Phone</th><th>Message</th><th>Status</th><th>User</th><th>Time</th></tr></thead><tbody>';
	foreach($rows as $r){
		$color = $r->status==='Sent'?'green':'red';
		printf(
			"<tr><td>%d</td><td>%s</td><td>%s</td><td style='color:%s'>%s</td><td>%s</td><td>%s</td></tr>",
			$r->id, esc_html($r->phone), esc_html(mb_strimwidth($r->message,0,50,'…')), $color, esc_html($r->status), esc_html($r->user), esc_html($r->sent_at)
		);
	}
	echo '</tbody></table></div>';
}

// ─── WooCommerce Hook ────────────────────────────────────────────────────────
add_action('woocommerce_order_status_changed', 'd7sms_wc_notify', 10, 4);
function d7sms_wc_notify($order_id, $old, $new, $order) {
    $enabled = get_option('d7sms_enabled_statuses', []);
    if (empty($enabled["wc-$new"])) return; // nothing enabled for this status

    $data = [
        '{site_title}'   => get_bloginfo('name'),
        '{order_id}'     => $order_id,
        '{order_status}' => ucfirst($new),
        '{order_total}'  => $order->get_formatted_order_total(),
        '{billing_name}' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
        '{shipping_name}' => $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(),
        '{order_date}'   => $order->get_date_created()->date('Y-m-d'),
    ];

    // Log for order notes
    $order_note = '';

    // Admin
    if (!empty($enabled["wc-$new"]['admin'])) {
        $admin_templates = get_option('d7sms_admin_templates', []);
        $template = $admin_templates["wc-$new"] ?? "Order #{order_id} is now {order_status}.";
        $msg = strtr($template, $data);
        $admins = explode(',', get_option('d7sms_admin_phones'));

        foreach ($admins as $ph) {
            $status = d7sms_send_sms(trim($ph), $msg, 'Admin');
            // Append to order note
            $order_note .= "SMS to admin \"" . esc_html(trim($ph)) . "\": " . $status . "\n";
        }
    }

    // Customer
    if (!empty($enabled["wc-$new"]['customer'])) {
        $customer_templates = get_option('d7sms_customer_templates', []);
        $template = $customer_templates["wc-$new"] ?? "Your order #{order_id} is now {order_status}.";
        $msg = strtr($template, $data);
        $ph = $order->get_billing_phone();

        if ($ph) {
            $billing_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
            $status = d7sms_send_sms($ph, $msg, $billing_name);
            // Append to order note
            $order_note .= "SMS to customer \"" . esc_html($ph) . "\": " . $status . "\n";
        }
    }

    // Add combined order note
    if (!empty($order_note)) {
        $order->add_order_note($order_note);
    }
}
