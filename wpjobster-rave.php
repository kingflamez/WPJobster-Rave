<?php

/**
 * Plugin Name: WPJobster Rave Gateway
 * Plugin URI: http://rave.flutterwave.com/
 * Description: This plugin extends Jobster Theme to accept payments with Rave.
 * Author: King Flamez (Oluwole Adebiyi)
 * Author URI: https://github.com/kingflamez
 * Version: 1.0
 *
 * Copyright (c) 2018 KingFlamez
 *
 */
if (!defined('ABSPATH')) {
  exit;
}
/**
 * Required minimums
 */
define('WPJOBSTER_SAMPLE_MIN_PHP_VER', '5.4.0');
class WPJobster_Rave_Loader
{
  /**
   * @var Singleton The reference the *Singleton* instance of this class
   */
  private static $instance;
  public $priority, $unique_slug;
  /**
   * Returns the *Singleton* instance of this class.
   *
   * @return Singleton The *Singleton* instance.
   */
  public static function get_instance()
  {
    if (null === self::$instance) {
      self::$instance = new self();
    }
    return self::$instance;
  }
  /**
   * Notices (array)
   * @var array
   */
  public $notices = array();
  /**
   * Protected constructor to prevent creating a new instance of the
   * *Singleton* via the `new` operator from outside of this class.
   */
  protected function __construct()
  {
    $this->requeryCount = 0;
    $this->priority = 1111;           // 100, 200, 300 [...] are reserved
    $this->unique_slug = 'rave';    // this needs to be unique
    add_action('admin_init', array($this, 'check_environment'));
    add_action('admin_notices', array($this, 'admin_notices'), 15);
    add_action('plugins_loaded', array($this, 'init_gateways'), 0);
    add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'plugin_action_links'));
    add_action('wpjobster_taketo_rave_gateway', array($this, 'taketogateway_function'), 10);
    add_action('wpjobster_processafter_rave_gateway', array($this, 'processgateway_function'), 10);
    if (isset($_POST['wpjobster_save_' . $this->unique_slug])) {
      add_action('wpjobster_payment_methods_action', array($this, 'save_gateway'), 11);
    }
  }
  /**
   * Initialize the gateway. Called very early - in the context of the plugins_loaded action
   *
   * @since 1.0.0
   */
  public function init_gateways()
  {
    load_plugin_textdomain('wpjobster-rave', false, trailingslashit(dirname(plugin_basename(__FILE__))));
    add_filter('wpjobster_payment_gateways', array($this, 'add_gateways'));
  }
  /**
   * Add the gateways to WPJobster
   *
   * @since 1.0.0
   */
  public function add_gateways($methods)
  {
    $methods[$this->priority] =
      array(
      'label' => __('Rave', 'wpjobster-rave'),
      'action' => '',
      'unique_id' => $this->unique_slug,
      'process_action' => 'wpjobster_taketo_rave_gateway',
      'response_action' => 'wpjobster_processafter_rave_gateway',
    );
    add_action('wpjobster_show_paymentgateway_forms', array($this, 'show_gateways'), $this->priority, 3);
    return $methods;
  }
  /**
   * Save the gateway settings in admin
   *
   * @since 1.0.0
   */
  public function save_gateway()
  {
    if (isset($_POST['wpjobster_save_' . $this->unique_slug])) {
			// _enable and _button_caption are mandatory
      update_option('wpjobster_' . $this->unique_slug . '_enable', trim($_POST['wpjobster_' . $this->unique_slug . '_enable']));
      update_option('wpjobster_' . $this->unique_slug . '_button_caption', trim($_POST['wpjobster_' . $this->unique_slug . '_button_caption']));
			// you can add here any other information that you need from the user
      update_option('wpjobster_rave_enablesandbox', trim($_POST['wpjobster_rave_enablesandbox']));
      update_option('wpjobster_rave_live_sk', trim($_POST['wpjobster_rave_live_sk']));
      update_option('wpjobster_rave_live_pk', trim($_POST['wpjobster_rave_live_pk']));
      update_option('wpjobster_rave_test_sk', trim($_POST['wpjobster_rave_test_sk']));
      update_option('wpjobster_rave_test_pk', trim($_POST['wpjobster_rave_test_pk']));
      update_option('wpjobster_rave_logo', trim($_POST['wpjobster_rave_logo']));
      update_option('wpjobster_rave_pym', trim($_POST['wpjobster_rave_pym']));
      update_option('wpjobster_rave_country', trim($_POST['wpjobster_rave_country']));
      
      update_option('wpjobster_rave_success_page', trim($_POST['wpjobster_rave_success_page']));
      update_option('wpjobster_rave_failure_page', trim($_POST['wpjobster_rave_failure_page']));
      echo '<div class="updated fade"><p>' . __('Settings saved!', 'wpjobster-rave') . '</p></div>';
    }
  }
  /**
   * Display the gateway settings in admin
   *
   * @since 1.0.0
   */
  public function show_gateways($wpjobster_payment_gateways, $arr, $arr_pages)
  {
    $payment_method = array(
      'both' => 'All',
      'card' => 'Card Only',
      'account' => 'Account only',
      'ussd' => 'USSD only',
    );

    $country = array(
      'NG' => 'Nigeria',
      'KE' => 'Kenya',
      'GH' => 'Ghana'
    );
    
    $tab_id = get_tab_id($wpjobster_payment_gateways);
    ?>
		<div id="tabs<?php echo $tab_id ?>">
			<form method="post" action="<?php bloginfo('siteurl'); ?>/wp-admin/admin.php?page=payment-methods&active_tab=tabs<?php echo $tab_id; ?>">
			<table width="100%" class="sitemile-table">
				<tr>
					<td valign=top width="22"><?php wpjobster_theme_bullet(); ?></td>
					<td valign="top"><?php _e('Rave Gateway Note:', 'wpjobster-rave'); ?></td>
					<td>
					<p><?php _e('Do you have any special instructions for your gateway?', 'wpjobster-rave'); ?></p>
					<p><?php _e('You can put them here.', 'wpjobster-rave'); ?></p>
					</td>
				</tr>

				<tr>
					<?php // _enable and _button_caption are mandatory ?>
					<td valign=top width="22"><?php wpjobster_theme_bullet(__('Enable/Disable Rave payment gateway', 'wpjobster-rave')); ?></td>
					<td width="200"><?php _e('Enable:', 'wpjobster-rave'); ?></td>
					<td><?php echo wpjobster_get_option_drop_down($arr, 'wpjobster_rave_enable', 'no'); ?></td>
				</tr>
				<tr>
					<td valign=top width="22"><?php wpjobster_theme_bullet(__('Enable/Disable Rave test mode.', 'wpjobster-rave')); ?></td>
					<td width="200"><?php _e('Enable Test Mode:', 'wpjobster-rave'); ?></td>
					<td><?php echo wpjobster_get_option_drop_down($arr, 'wpjobster_rave_enablesandbox', 'no'); ?></td>
				</tr>
				<tr>
					<?php // _enable and _button_caption are mandatory ?>
					<td valign=top width="22"><?php wpjobster_theme_bullet(__('Put the Rave button caption you want user to see on purchase page', 'wpjobster-rave')); ?></td>
					<td><?php _e('Rave Button Caption:', 'wpjobster-rave'); ?></td>
					<td><input type="text" size="85" name="wpjobster_<?php echo $this->unique_slug; ?>_button_caption" value="<?php echo get_option('wpjobster_' . $this->unique_slug . '_button_caption'); ?>" /></td>
				</tr>
				<tr>
					<td valign=top width="22"><?php wpjobster_theme_bullet(__('Your Rave Live Secret Key', 'wpjobster-rave')); ?></td>
					<td ><?php _e('Rave Live Secret Key:', 'wpjobster-rave'); ?></td>
					<td><input type="text" size="85" name="wpjobster_rave_live_sk" value="<?php echo get_option('wpjobster_rave_live_sk'); ?>" /></td>
				</tr>
				<tr>
					<td valign=top width="22"><?php wpjobster_theme_bullet(__('Your Rave Live Public Key', 'wpjobster-rave')); ?></td>
					<td ><?php _e('Rave Live Public Key:', 'wpjobster-rave'); ?></td>
					<td><input type="text" size="85" name="wpjobster_rave_live_pk" value="<?php echo get_option('wpjobster_rave_live_pk'); ?>" /></td>
				</tr>
				<tr>
					<td valign=top width="22"><?php wpjobster_theme_bullet(__('Your Rave Test Secret Key', 'wpjobster-rave')); ?></td>
					<td ><?php _e('Rave Test Secret Key:', 'wpjobster-rave'); ?></td>
					<td><input type="text" size="85" name="wpjobster_rave_test_sk" value="<?php echo get_option('wpjobster_rave_test_sk'); ?>" /></td>
				</tr>
				<tr>
					<td valign=top width="22"><?php wpjobster_theme_bullet(__('Your Rave Test Public Key', 'wpjobster-rave')); ?></td>
					<td ><?php _e('Rave Test Public Key:', 'wpjobster-rave'); ?></td>
					<td><input type="text" size="85" name="wpjobster_rave_test_pk" value="<?php echo get_option('wpjobster_rave_test_pk'); ?>" /></td>
				</tr>
				<tr>
					<td valign=top width="22"><?php wpjobster_theme_bullet(__('Your Rave Logo Link (Preferrably square size)', 'wpjobster-rave')); ?></td>
					<td ><?php _e('Rave Logo Link:', 'wpjobster-rave'); ?></td>
					<td><input type="text" size="85" name="wpjobster_rave_logo" value="<?php echo get_option('wpjobster_rave_logo'); ?>" /></td>
				</tr>
				<tr>
					<td valign=top width="22"><?php wpjobster_theme_bullet(__('Your Rave Payment Method', 'wpjobster-rave')); ?></td>
					<td ><?php _e('Rave Payment Method:', 'wpjobster-rave'); ?></td>
					<td><?php echo wpjobster_get_option_drop_down($payment_method, 'wpjobster_rave_pym', 'both'); ?></td>
				</tr>
				<tr>
					<td valign=top width="22"><?php wpjobster_theme_bullet(__('Your Country', 'wpjobster-rave')); ?></td>
					<td ><?php _e('Country:', 'wpjobster-rave'); ?></td>
					<td><?php echo wpjobster_get_option_drop_down($country, 'wpjobster_rave_country', 'NG'); ?></td>
        </tr>
        

				<tr>
					<td valign=top width="22"><?php wpjobster_theme_bullet(__('Please select a page to show when Rave payment successful. If empty, it redirects to the transaction page', 'wpjobster-rave')); ?></td>
					<td><?php _e('Transaction Success Redirect:', 'wpjobster-rave'); ?></td>
					<td><?php
        echo wpjobster_get_option_drop_down($arr_pages, 'wpjobster_' . $this->unique_slug . '_success_page', '', ' class="select2" '); ?>
						</td>
				</tr>
				<tr>
					<td valign=top width="22"><?php wpjobster_theme_bullet(__('Please select a page to show when Rave payment failed. If empty, it redirects to the transaction page', 'wpjobster-rave')); ?></td>
					<td><?php _e('Transaction Failure Redirect:', 'wpjobster-rave'); ?></td>
					<td><?php
        echo wpjobster_get_option_drop_down($arr_pages, 'wpjobster_' . $this->unique_slug . '_failure_page', '', ' class="select2" '); ?></td>
				</tr>
				<tr>
					<td></td>
					<td></td>
					<td><input type="submit" name="wpjobster_save_<?php echo $this->unique_slug; ?>" value="<?php _e('Save Options', 'wpjobster-rave'); ?>" /></td>
				</tr>
				</table>
			</form>
		</div>
		<?php

}
/**
 * This function is not required, but it helps making the code a bit cleaner.
 *
 * @since 1.0.0
 */
public function get_gateway_credentials()
{
  $wpjobster_rave_enablesandbox = get_option('wpjobster_rave_enablesandbox');
  if ($wpjobster_rave_enablesandbox == 'no') {
    $rave_payment_url = 'https://api.ravepay.co';
    $publickey = get_option('wpjobster_rave_live_pk');
    $secretkey = get_option('wpjobster_rave_live_sk');
  } else {
    $rave_payment_url = 'https://rave-api-v2.herokuapp.com';
    $publickey = get_option('wpjobster_rave_test_pk');
    $secretkey = get_option('wpjobster_rave_test_sk');
  }

  $payment_method = get_option('wpjobster_rave_pym');

  $credentials = array(
    'publickey' => $publickey,
    'secretkey' => $secretkey,
    'rave_payment_url' => $rave_payment_url,
    'rave_payment_method' => $payment_method,
  );
  return $credentials;
}

function rave_generate_new_code($length = 10)
{
  $characters = '0123456789abcdefgh'.time().'ijklmnopqrstuvwxyzABCDEFGHIJK'.time().'LMNOPQRSTUVWXYZ';
  $charactersLength = strlen($characters);
  $randomString = '';
  for ($i = 0; $i < $length; $i++) {
    $randomString .= $characters[rand(0, $charactersLength - 1)];
  }
  return time() . "-" . $randomString;
}

/**
 * Collect all the info that we need and forward to the gateway
 *
 * @since 1.0.0
 */
public function taketogateway_function($payment_type, $common_details)
{
  $credentials = $this->get_gateway_credentials();

  $all_data = array();
  $all_data['publickey'] = $credentials['publickey'];
  $all_data['secretkey'] = $credentials['secretkey'];
  $all_data['rave_payment_url'] = $credentials['rave_payment_url'];
  $all_data['rave_payment_method'] = $credentials['rave_payment_method']; 

  $uid = $common_details['uid'];
  $wpjobster_final_payable_amount = $common_details['wpjobster_final_payable_amount'];
  $currency = $common_details['currency'];
  $order_id = $common_details['order_id'];

		// user() is a helper function which calls the appropriate function
		// between get_userdata() and get_user_meta() depending on what info is needed

  $all_data['amount'] = $wpjobster_final_payable_amount;
  $all_data['currency'] = $currency;
  $all_data['success_url'] = get_bloginfo('url') . '/?payment_response=rave&payment_type=' . $payment_type;
  $all_data['fail_url'] = get_bloginfo('url') . '/?payment_response=rave&action=fail&payment_type=' . $payment_type;

		// any other info that the gateway needs
  $all_data['firstname'] = user($uid, 'first_name');
  $all_data['email'] = user($uid, 'user_email');
  $all_data['phone'] = user($uid, 'cell_number');
  $all_data['lastname'] = user($uid, 'last_name');
  $all_data['address'] = user($uid, 'address');
  $all_data['city'] = user($uid, 'city');
  $all_data['country'] = user($uid, 'country_name');
  $all_data['zipcode'] = user($uid, 'zip');
  $all_data['order_id'] = $order_id;

  $loading_text = __('Loading...', 'wpjobster-rave');

  $redirectURL = $all_data['success_url'];
  $publicKey = $all_data['publickey']; // Remember to change this to your live public keys when going live
  $secretKey = $all_data['secretkey']; // Remember to change this to your live secret keys when going live
  $baseUrl = $all_data['rave_payment_url'];
  $country = $all_data['country'];
  $ref = $order_id.'_'.time();
  
  $amountToBePaid = $all_data['amount'];
  $postfields = array();
  $postfields['PBFPubKey'] = $publicKey;
  $postfields['customer_email'] = $all_data['email'];
  $postfields['customer_firstname'] = $all_data['firstname'];
  $postfields['customer_lastname'] = $all_data['lastname'];
  $postfields['custom_description'] = "Payment for Order: " . $all_data['order_id'] . " on " . get_bloginfo('name');
  $postfields['custom_title'] = get_bloginfo('name');
  $postfields['country'] = $country;
  $postfields['redirect_url'] = $redirectURL;
  $postfields['txref'] = $ref;
  $postfields['payment_method'] = $all_data['rave_payment_method'];
  $postfields['amount'] = $amountToBePaid + 0;
  $postfields['currency'] = $all_data['currency'];
  $postfields['hosted_payment'] = 1;
  ksort($postfields);
  $stringToHash = "";
  foreach ($postfields as $key => $val) {
    $stringToHash .= $val;
  }
  $stringToHash .= $secretKey;
  $hashedValue = hash('sha256', $stringToHash);
  $transactionData = array_merge($postfields, array('integrity_hash' => $hashedValue));
  $json = json_encode($transactionData);
  $htmlOutput = "
		    <script type='text/javascript' src='" . $baseUrl . "/flwv3-pug/getpaidx/api/flwpbf-inline.js'></script>
		    <script>
		    document.addEventListener('DOMContentLoaded', function(event) {
			    var data = JSON.parse('" . json_encode($transactionData = array_merge($postfields, array('integrity_hash' => $hashedValue))) . "');
			    getpaidSetup(data);
			});
		    </script>
		    ";
  echo $htmlOutput;
  exit;
}

/**
 * Process the response from the gateway and mark the order as completed or failed
 *
 * @since 1.0.0
 */
function processgateway_function($payment_type, $details)
{
  if (isset($_GET['txref'])) {
    $this->requery($payment_type, $details);
  }
}

function requery($payment_type, $details)
{
  $credentials = $this->get_gateway_credentials();

  $all_data = array();
  $all_data['publickey'] = $credentials['publickey'];
  $all_data['secretkey'] = $credentials['secretkey'];

  $secretKey = $credentials['secretkey']; // Remember to change this to your live secret keys when going live
  $wpjobster_rave_enablesandbox = get_option('wpjobster_rave_enablesandbox');
  if ($wpjobster_rave_enablesandbox == 'no') {
    $apiLink = 'https://api.ravepay.co';
  } else {
    $apiLink = 'http://flw-pms-dev.eu-west-1.elasticbeanstalk.com/';
  }

  $txref = $_REQUEST['txref'];
  $this->requeryCount++;
  $data = array(
    'txref' => $txref,
    'SECKEY' => $secretKey,
    'last_attempt' => '1'
	        // 'only_successful' => '1'
  );
	    // make request to endpoint.
  $data_string = json_encode($data);
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $apiLink . 'flwv3-pug/getpaidx/api/xrequery');
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
  curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
  $response = curl_exec($ch);
  $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
  $header = substr($response, 0, $header_size);
  $body = substr($response, $header_size);
  curl_close($ch);
  $resp = json_decode($response, false);
  if ($resp && $resp->status === "success") {
    if ($resp && $resp->data && $resp->data->status === "successful") {
      $this->verifyTransaction($payment_type, $resp->data);
    } elseif ($resp && $resp->data && $resp->data->status === "failed") {
      $this->failed($payment_type, $data);
    } else {
      if ($this->requeryCount > 4) {
        $this->failed($payment_type, $data);
      } else {
        sleep(3);
        $this->requery($payment_type, $details);
      }
    }
  } else {
    if ($this->requeryCount > 4) {
      $this->failed($payment_type, $data);
    } else {
      sleep(3);
      $this->requery($payment_type, $details);
    }
  }
}
/**
 * Requeries a previous transaction from the Rave payment gateway
 * @param string $referenceNumber This should be the reference number of the transaction you want to requery
 * @return object
 * */
function verifyTransaction($payment_type, $data)
{
  $order_id = explode('_', $_GET["txref"]);
  $order_id = $invoiceId[0];
  $order_details = wpjobster_get_order_details_by_orderid($order_id);
  $amt = $order_details->final_paidamount;
  $amt_arr = explode("|", $amt);
  $currency = $amt_arr['0'];
  $amountToBePaid = $amt_arr['1'];
  $amount = $amountToBePaid + 0;
  
  if (($data->chargecode == "00" || $data->chargecode == "0") && ($data->amount == $amount) && ($data->currency == $currency)) {
    $payment_details = "success action returned"; // any info you may find useful for debug
    do_action(
      "wpjobster_" . $payment_type . "_payment_success",
      $order_id,
      $this->unique_slug,
      $payment_details,
      $data->chargemessage
    );
    die();
  } else {
    $this->failed($payment_type, $data);
  }
}

function failed($payment_type, $data)
{

  $order_id = explode('_', $_GET["txref"]);
  $order_id = $invoiceId[0];

  $payment_details = "Failed action returned"; // any info you may find useful for debug
  do_action(
    "wpjobster_" . $payment_type . "_payment_failed",
    $order_id,
    $this->unique_slug,
    $payment_details,
    $data->chargemessage
  );
  die();
}

/**
 * Allow this class and other classes to add slug keyed notices (to avoid duplication)
 */
public function add_admin_notice($slug, $class, $message)
{
  $this->notices[$slug] = array(
    'class' => $class,
    'message' => $message
  );
}
/**
 * The primary sanity check, automatically disable the plugin on activation if it doesn't
 * meet minimum requirements.
 *
 * Based on http://wptavern.com/how-to-prevent-wordpress-plugins-from-activating-on-sites-with-incompatible-hosting-environments
 */
public static function activation_check()
{
  $environment_warning = self::get_environment_warning(true);
  if ($environment_warning) {
    deactivate_plugins(plugin_basename(__FILE__));
    wp_die($environment_warning);
  }
}
/**
 * The backup sanity check, in case the plugin is activated in a weird way,
 * or the environment changes after activation.
 */
public function check_environment()
{
  $environment_warning = self::get_environment_warning();
  if ($environment_warning && is_plugin_active(plugin_basename(__FILE__))) {
    deactivate_plugins(plugin_basename(__FILE__));
    $this->add_admin_notice('bad_environment', 'error', $environment_warning);
    if (isset($_GET['activate'])) {
      unset($_GET['activate']);
    }
  }
}
/**
 * Checks the environment for compatibility problems.  Returns a string with the first incompatibility
 * found or false if the environment has no problems.
 */
static function get_environment_warning($during_activation = false)
{
  if (version_compare(phpversion(), WPJOBSTER_SAMPLE_MIN_PHP_VER, '<')) {
    if ($during_activation) {
      $message = __('The plugin could not be activated. The minimum PHP version required for this plugin is %1$s. You are running %2$s.', 'wpjobster-rave');
    } else {
      $message = __('The Rave Powered by wpjobster plugin has been deactivated. The minimum PHP version required for this plugin is %1$s. You are running %2$s.', 'wpjobster-rave');
    }
    return sprintf($message, WPJOBSTER_SAMPLE_MIN_PHP_VER, phpversion());
  }
  return false;
}
/**
 * Adds plugin action links
 *
 * @since 1.0.0
 */
public function plugin_action_links($links)
{
  $setting_link = $this->get_setting_link();
  $plugin_links = array(
    '<a href="' . $setting_link . '">' . __('Settings', 'wpjobster-rave') . '</a>',
  );
  return array_merge($plugin_links, $links);
}
/**
 * Get setting link.
 *
 * @return string Braintree checkout setting link
 */
public function get_setting_link()
{
  $section_slug = $this->unique_slug;
  return admin_url('admin.php?page=payment-methods&active_tab=tabs' . $section_slug);
}
/**
 * Display any notices we've collected thus far (e.g. for connection, disconnection)
 */
public function admin_notices()
{
  foreach ((array)$this->notices as $notice_key => $notice) {
    echo "<div class='" . esc_attr($notice['class']) . "'><p>";
    echo wp_kses($notice['message'], array('a' => array('href' => array())));
    echo "</p></div>";
  }
}
}
$GLOBALS['WPJobster_rave_Loader'] = WPJobster_rave_Loader::get_instance();
register_activation_hook(__FILE__, array('WPJobster_rave_Loader', 'activation_check'));