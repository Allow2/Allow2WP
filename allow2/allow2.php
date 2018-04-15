<?php
/**
 *
 * @package     Allow2
 * @author      Allow2 Pty Ltd
 * @copyright   2016 Allow2 Pty Ltd
 * @license     https://www.allow2.com/developer-license/
 *
 * @wordpress-plugin
 * Plugin Name: Allow2
 * Plugin URI:  https://www.github.com/Allow2/Allow2WP
 * Description: The Allow2 WordPress plugin is a simple plug and play drop in component that empowers your site to offer full parental freedom.
 * Version:     1.0.1
 * Author:      Andrew Longhorn
 * Author URI:  https://www.allow2.com
 * Text Domain: allow2
 * License:     Similar to Apache 2.0
 * License URI: https://www.allow2.com/developer-license/
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly
//defined( 'ABSPATH' ) or die( 'No script kiddies please!' ); ??


/**
 * Admin Menu Components
 */
function allow2_admin() {
  include('allow2_admin.php');
}

function allow2_admin_actions() {
  add_options_page("Allow2", "Allow2", 'manage_options', "Allow2", "allow2_admin");
}

add_action('admin_menu', 'allow2_admin_actions');

/**
 * Catch callback get var display
 */
add_action('wp_loaded', 'a2check_callback');
function a2check_callback() {

  /* no need to sanitise this one */
  if (isset($_GET['allow2_callback']) && current_user_can('read')) {

    wp_head();
    if ($_GET['allow2_callback'] === 'oauth') {
      include('allow2_oauth2callback.php');
    } elseif ($_GET['allow2_callback'] === 'error') {
      /* todo: match this with correct error handling - extra get vars */
      include('allow2_error.php');
    }

    wp_footer();
    die();
  };
}


/**
 * Showing extra profile fields required to pair user accounts with the allow2 service
 */
add_action('show_user_profile', 'allow2_user_profile_fields');
add_action('edit_user_profile', 'allow2_user_profile_fields');

function allow2_user_profile_fields($user) {
  $a2token = get_option('allow2_token', '');
  $a2userId = get_option('allow2_userId');
  $host = 'https://api.allow2.com';
  if (($a2userId) && class_exists('UseClientsTimezone')) {
    ?>
      <h3><?php echo '<img width=30 height=30 src="' . plugin_dir_url(__FILE__) . 'assets/icon-30x30.png">&nbsp;' . _e("Allow2", "blank"); ?></h3>
    <?php
    if (current_user_can('administrator')) {
      ?>
        <table class="form-table">
            <tr>
                <th>Admin accounts cannot be paired with Allow2.</th>
            </tr>
        </table>
      <?php
    } else {
      $settings = get_user_meta($user->ID, 'allow2_settings', true);

      if (isset($settings["allow2_refresh_token"])) {
        $connectedClasses = "form-table";
        $connectClasses = "form-table hidden";
      } else {
        $connectedClasses = "form-table hidden";
        $connectClasses = "form-table";
      }
      ?>
        <table id="allow2Connected" class="<?php echo esc_attr($connectedClasses) ?>">
            <tr>
                <th><label for="allow2status">Connected with Allow2</label></th>
                <td aria-live="assertive">
                    <div class="allow2status">
                        <button type="button" class="button button-secondary" name="allow2status" id="allow2_status_button" onclick="checkAllow2Status(); false;">Check Status</button>
                        &nbsp;
                        <i id="allow2_status_check_spinner" class="fa fa-spinner fa-pulse fa-2x" style="display: none;" aria-hidden="true"></i>
                    </div>
                    <p class="description">Note: Only the account you are controlled by can disconnect you from within the Allow2 system.</p>
                    <p class="description">You appear to be in the <?php echo date_default_timezone_get(); ?> timezone, if this is not correct Allow2 may not operate correctly, please inform the site administrator.</p>
                </td>
            </tr>
            <tr>
                <th><label for="allow2request">Send a Request</label></th>
                <td aria-live="assertive">
                    <div class="allow2request">
                        <button type="button" class="button button-secondary" name="allow2request" id="allow2_request_button" onclick="startAllow2Request(); false;">New Request</button>
                        &nbsp;
                        <i id="allow2_make_request_spinner" class="fa fa-spinner fa-pulse fa-2x" style="display: none;" aria-hidden="true"></i>
                    </div>
                    <p class="description">Ask for more time, change the day type or request other changes.</p>
                    <p class="description">NOTE: If this button appears to do nothing, check the popup window is not being blocked by your browser.</p>
                </td>
            </tr>
        </table>

        <table id="allow2Connect" class="<?php echo esc_attr($connectClasses) ?>">
            <tr>
                <th><label for="allow2connect">Connect with Allow2</label></th>
                <td aria-live="assertive">
                    <div class="allow2connect">
                        <button type="button" class="button button-secondary" name="allow2connect" id="allow2_pair_button" onclick="requestAllow2OauthCode(); false;">Connect</button>
                    </div>
                    <p class="description">Connect this account with Allow2 to control access and usage.</p>
                </td>
            </tr>
        </table>

      <?php
      wp_enqueue_script('allow2oauth2', plugin_dir_url(__FILE__) . 'lib/allow2oauth2.js', array('jquery'), '0.1');
      wp_enqueue_script('allow2request', plugin_dir_url(__FILE__) . 'lib/allow2request.js', array('jquery'), '0.1');
      $php_data = array(
        'user_id' => $user->ID,
        'nonce' => wp_create_nonce('allow2_nonce_' . $user->ID),
        'a2_sr_n' => wp_create_nonce('allow2_start_request'),
        'a2_cs_n' => wp_create_nonce('allow2_check_status'),
        'a2_fce_n' => wp_create_nonce('allow2_finish_code_exchange'),
        'token' => get_option('allow2_token', true),
        'redirect_uri' => site_url() . '?allow2_callback=oauth'
      );
      wp_localize_script('allow2oauth2', 'php_data', $php_data);
      wp_localize_script('allow2request', 'php_data', $php_data);
    }
  }
}


/**
 * handle receiving the auth_code, store it and grab the first token
 */
add_action('wp_ajax_allow2_finish_code_exchange', 'allow2_finish_code_exchange');

function allow2_finish_code_exchange() {
  // todo: verify the nonce
  $user_id = get_current_user_id();
  check_ajax_referer('allow2_nonce_' . $user_id, 'nonce');


  /* check nonce nonce from form or die() */
  if (!isset($_POST['a2_fce_n']) || !wp_verify_nonce(esc_attr($_POST['a2_fce_n']), 'allow2_finish_code_exchange')) {
    echo '{ "status": "nonce error" }';
    exit;
  }

  // ok, all good, extract the auth_code and send to our reusable function
  $auth_code = esc_attr($_POST['auth_code']);
  $token = allow2_set_oauth2_token($auth_code, 'auth_code');
  if ($token != false) {
    echo '{ "status": "success" }';
  } else {
    echo '{ "status": "error" }';
  }
  wp_die();
}

/**
 * Main helper for managing tokens
 * this gets the initial token given an auth code, or
 * uses the refresh token to get a new token
 */
function allow2_set_oauth2_token($grantCode, $grantType) {
  // based on http://ieg.wnet.org/2015/09/using-oauth-in-wordpress-plugins-part-2-persistence/
  $user_id = get_current_user_id();
  if ($user_id < 1) {
    return false;
  }

  $settings = get_user_meta($user_id, 'allow2_settings', true);
  if ($settings == '') {
    $settings = [];
  }

  $oauth2token_url = 'https://api.allow2.com/oauth2/token';

  $clienttoken_post = array(
    "client_id" => get_option('allow2_token', false),
    "client_secret" => get_option('allow2_secret', false)
  );

  if (!$clienttoken_post['client_id'] || !$clienttoken_post['client_secret']) {
    return false;
  }

  if ($grantType === "auth_code") {
    $clienttoken_post["code"] = $grantCode;
    $clienttoken_post["redirect_uri"] = site_url() . '?allow2_callback=oauth';
    $clienttoken_post["grant_type"] = "authorization_code";
    $clienttoken_post["scope"] = "offline_access";
  }
  if ($grantType === "refresh_token") {
    $clienttoken_post["refresh_token"] = $settings['allow2_refresh_token'];
    $clienttoken_post["grant_type"] = "refresh_token";
  }

  $postargs = array(
    'body' => $clienttoken_post,
    'timeout' => 20,
    'sslverify' => false
  );
  $response = wp_remote_post($oauth2token_url, $postargs);
  $response_body = wp_remote_retrieve_body($response);
  $httpCode = wp_remote_retrieve_response_code($response);

  if (is_wp_error($response_body)) {
    return false;
  } else if ($httpCode != 200) {
    if ($httpCode != 403) {
      // 403 - very special case, this is explicitly telling us we no longer have this account attached via this token,
      // so we need to erase that connection and free up this account
      delete_user_meta($user_id, 'allow2_settings');
    }
    return false; // $httpCode  false
  }

  $authObj = json_decode($response_body, true);

  // todo: handle a 403 and erase the pairing

  $success = isset($authObj['refresh_token']);
  if ($success) {
    $refreshToken = $authObj['refresh_token'];
    $changed = !isset($settings['allow2_refresh_token']) || ($settings['allow2_refresh_token'] != $refreshToken);
    if ($changed) {
      $settings['allow2_refresh_token'] = $refreshToken;
      $success = update_user_meta($user_id, 'allow2_settings', $settings);
    }
  }
  if ($success) {
    $settings['allow2_access_token_expires'] = strtotime("+" . $authObj['expires_in'] . " seconds");
    $success = update_user_meta($user_id, 'allow2_settings', $settings);
  }
  if ($success) {
    $settings['allow2_access_token'] = $authObj['access_token'];
    $success = update_user_meta($user_id, 'allow2_settings', $settings);
    if ($success) {
      $success = $authObj['access_token'];
    }
  }
  // if there were any errors $success will be false, otherwise it'll be the access token
  if (!$success) {
    return 'no success';
  } // false
  return $success;
}

/**
 * allow2_get_access_token
 *
 * use this any time we need to use the api to make sure we have a current non-expired token
 */
function allow2_get_access_token() {
  $user_id = get_current_user_id();
  if ($user_id < 1) {
    return false;
  }

  $settings = get_user_meta($user_id, 'allow2_settings', true);
  $expiration_time = $settings['allow2_access_token_expires'];
  if (!$expiration_time) {
    return false;
  }

  // Give the access token a 5 minute buffer (300 seconds)
  $expiration_time = $expiration_time - 300;

  if (time() < $expiration_time) {
    return $settings['allow2_access_token'];
  }

  // at this point we have an expiration time but it is in the past or will be very soon
  return allow2_set_oauth2_token(null, 'refresh_token');
}

/**
 * allow2_check_status
 *
 * send the refresh token to the server and ask if it is still valid
 * if forbidden (403), erase the user pairing and return false
 * if still paired (ANY OTHER CODE), return true
 */
add_action('wp_ajax_allow2_check_status', 'allow2_check_status');

function allow2_check_status() {
  // todo: verify the nonce
  $user_id = get_current_user_id();
  check_ajax_referer('allow2_nonce_' . $user_id, 'nonce');

  /* check nonce nonce from form or die() */
  if (!isset($_POST['a2_cs_n']) || !wp_verify_nonce(esc_attr($_POST['a2_cs_n']), 'allow2_check_status')) {
    echo '{ "status": "nonce error" }';
    exit;
  }

  $postData = array(
    "client_id" => get_option('allow2_token', false),
    "client_secret" => get_option('allow2_secret', false)
  );
  /*var_dump($postData);
  die();*/
  // not using allow2, clear the user settings
  if (!$postData['client_id'] || !$postData['client_secret']) {
    delete_user_meta($user_id, 'allow2_settings');
    status_header(403);
    echo '{ "status" : "allow2 not in use" }';
    wp_die();
    return false;
  }

  $settings = get_user_meta($user_id, 'allow2_settings', true);
  // the user doesn't have a pairing, clear it to ensure valid state
  if (!isset($settings) || !isset($settings['allow2_refresh_token'])) {
    delete_user_meta($user_id, 'allow2_settings');
    status_header(403);
    echo '{ "status" : "not connected" }';
    wp_die();
    return false;
  }

  $host = 'https://api.allow2.com';
  $oauth2check_url = 'https://api.allow2.com/oauth2/checkStatus';

  // ok, all good, hit the Allow2 server to verify current status for this user
  $postData['refreshToken'] = $settings['allow2_refresh_token'];
  $postargs = array(
    'body' => $postData,
    'timeout' => 20
  );

  $response = wp_remote_post($oauth2check_url, $postargs);
  $httpCode = wp_remote_retrieve_response_code($response);

  // specifically told to clear the pairing by Allow2
  if ($httpCode == 403) {
    // this is the ONLY response code that will clear a users pairing
    delete_user_meta($user_id, 'allow2_settings');
    status_header(403);
    echo '{ "status" : "not connected" }';
    wp_die();
    return false;
  }

  // pairing is still valid
  echo '{"status":"connected"}';
  wp_die();
}


/**
 * allow2_start_request
 *
 * ask the allow2 server for a temporary access token for this user
 */
add_action('wp_ajax_allow2_start_request', 'allow2_start_request');

function allow2_start_request() {
  // verify the nonce
  $user_id = get_current_user_id();
  check_ajax_referer('allow2_nonce_' . $user_id, 'nonce');


  /* check nonce nonce from form or die() */
  if (!isset($_POST['a2_sr_n']) || !wp_verify_nonce(esc_attr($_POST['a2_sr_n']), 'allow2_start_request')) {
    echo '{ "status": "nonce error" }';
    exit;
  }

  //
  // grab the valid nonce
  //
  $nonce = '';
  $query_arg = 'nonce';
  if ($query_arg && isset($_POST[$query_arg]))
    $nonce = esc_attr($_POST[$query_arg]);
  elseif (isset($_POST['_ajax_nonce']))
    $nonce = esc_attr($_POST['_ajax_nonce']);
  elseif (isset($_POST['_wpnonce']))
    $nonce = esc_attr($_POST['_wpnonce']);

  $postData = array(
    "client_id" => get_option('allow2_token', false),
    "client_secret" => get_option('allow2_secret', false),
    "serviceToken" => $nonce
  );
  // not using allow2, reject the request and clear settings for the user
  if (!$postData['client_id'] || !$postData['client_secret']) {
    delete_user_meta($user_id, 'allow2_settings');
    http_response_code(403);
    echo '{"status":"Allow2 not in use"}';
    wp_die();
    return false;
  }

  $settings = get_user_meta($user_id, 'allow2_settings', true);
  // the user doesn't have a pairing, clear it to ensure valid state
  if (!isset($settings) || !isset($settings['allow2_refresh_token'])) {
    delete_user_meta($user_id, 'allow2_settings');
    http_response_code(403);
    echo '{"status":"not connected"}';
    wp_die();
    return;
  }


  $host = 'https://api.allow2.com';

  $tempToken_url = $host . '/request/tempToken';

  // ok, all good, hit the Allow2 server to verify current status for this user
  $postData['refreshToken'] = $settings['allow2_refresh_token'];
  $postargs = array(
    'body' => $postData,
    'timeout' => 20
  );

  $response = wp_remote_post($tempToken_url, $postargs);
  $response_body = wp_remote_retrieve_body($response);
  $httpCode = wp_remote_retrieve_response_code($response);

  if (is_wp_error($response_body)) {
    http_response_code(500);
    echo '{"status":"Allow2 error", "error": "' . $response_body . '"}';
    wp_die();
    return false;
  }

  // specifically told to clear the pairing by Allow2
  if ($httpCode == 403) {
    // this is the ONLY response code that will clear a users pairing
    delete_user_meta($user_id, 'allow2_settings');
    http_response_code(403);
    echo '{"status":"not connected"}';
    wp_die();
    return false;
  }
  if ($httpCode != 200) {
    // unexpected error
    http_response_code(500);
    echo '{"status":"unexpected error", "error": "' . wp_remote_retrieve_response_message($response) . '"}';
    wp_die();
    return false;
  }

  // all is good, the allow2 server has passed back a short-use token for us, pass it straight back to the client
  // so they can use it to create a request
  echo $response_body;
  wp_die();
}


function allow2_log_me($message) {
  if (WP_DEBUG === true) {
    if (is_array($message) || is_object($message)) {
      error_log(print_r($message, true));
    } else {
      error_log($message);
    }
  }
}

/**
 * Actual call to use the service
 *
 * activities:
 *    1 : General Internet/Web Site/Browsing Access
 */
function allow2_checkAndLog() {
  //
  // If admin account or not logged in (no identity = anonymous public access) then it's allowed
  // Allow2 only manages user accounts, not anonymous activity
  //
  if (is_admin() || !is_user_logged_in() || !class_exists('UseClientsTimezone')) {
    return;
  }

  //
  // always allow access to helper scripts
  //
  //https://a2wp.mystagingwebsite.com/wp-content/plugins/allow2/lib/allow2request.js
  global $wp;
  $current_url = add_query_arg($wp->query_string, '', home_url($wp->request));
  $plugin_dir = plugin_dir_url(__FILE__);
  if (!strncmp($current_url, $plugin_dir, strlen($plugin_dir))) {
    return;
  }

  //
  // only bother checking if the user is controlled by allow2
  //
  $user_id = get_current_user_id();
  if ($user_id < 1) {
    return;
  }
  $allow2_settings = get_user_meta($user_id, 'allow2_settings', true);
  if (!$allow2_settings || !isset($allow2_settings["allow2_refresh_token"])) {
    return;
  }

  //
  // use a cached value first, granularity is 1 minute
  //

  $result = get_user_meta($user_id, 'allow2_cache', true);
  if ($result) {
    // a cached value for now has ALL activities expiring at the same time,
    // so we only need to check the first activity expiry
    if (!$result["activities"] || !$result["activities"][0] || !$result["activities"][0]["expires"]) {
      // cache is invalid
      delete_user_meta($user_id, 'allow2_cache');
      $result = false;
    } else {
      $expires = $result["activities"][0]["expires"];
      if ($expires < time()) {
        // cache has expired
        delete_user_meta($user_id, 'allow2_cache');
        $result = false;
      }
    }
  }

  if (!$result) {
    //
    // no cached value, or it expired, so we need to hit the service again and cache the new response
    // note, we use the alternate syntax with the id of each activity in the object, as php is stupid
    // and cannot understand the string '1' is a string!
    //
    $params = array(
      'access_token' => allow2_get_access_token(),
      'tz' => 'Australia/Brisbane',        // todo: send the users timezone
      'activities' => array(
        array(
          'id' => 1,
          'log' => true
        )
      )
    );
    $serviceHost = 'https://service.allow2.com';

    $url = $serviceHost . '/serviceapi/check';

    // no access token, account is no longer controlled
    if (!$params['access_token']) {
      return;
    }

    $postargs = array(
      'body' => $params
    );

    $response = wp_remote_post($url, $postargs);
    $response_body = wp_remote_retrieve_body($response);
    $httpCode = wp_remote_retrieve_response_code($response);

    // if errors, handle them.
    // for now, connection errors and service crashes allow access.
    // need to look at the right policies based on error types here in the long term.
    if (is_wp_error($response_body)) {
      return;
    } else if ($httpCode != 200) {
      if ($httpCode == 403) {
        // 403 - very special case, this is explicitly telling us we no longer have this account attached via this token,
        // so we need to erase that connection and free up this account
        delete_user_meta($user_id, 'allow2_settings');
      }
      return;
    }

    // success, this is a controlled account with real settings and we have a valid response to work with

    $result = json_decode($response_body, true);
  }

  $allowed = $result["allowed"];

  // simplistic cache for now, cache the entire result
  // todo: cache each service separately possibly? or is this good enough for now?
  update_user_meta($user_id, 'allow2_cache', $result);

  if ($allowed) {
    return;
  }

  //
  // if we get here, there is SOMETHING wrong, let's collate the reasons they cannot access the site.
  //
  $dayType = $result["dayTypes"]["today"]["name"];
  $reasons = '';
  foreach ($result["activities"] as $activity) {
    if ($activity["banned"]) {
      $reasons .= '<p>You are currently banned from ' . $activity["name"] . '</p>';
    } else if (!$activity["timeBlock"]["allowed"]) {
      $reasons .= '<p>Outside allowed times for ' . $activity["name"] . '</p>';
    }
  }
  nocache_headers();

  $pageData = '<link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN" crossorigin="anonymous">'
    . '<h1>Allow2 Limited - ' . $dayType . '</h1><p>Access currently not Allowed.</p>' . $reasons
    . '<button class="btn btn-primary" onclick="startAllow2Request(); false;">New Request</button>'
    . '&nbsp;<i id="allow2_make_request_spinner" class="fa fa-spinner fa-pulse" style="display: none;" aria-hidden="true" ></i>'
    . '<script src="//code.jquery.com/jquery-3.1.1.min.js" integrity="sha256-hVVnYaiADRTO2PzUGmuLJr8BLUSjGIZsDYGmIJLv2b8=" crossorigin="anonymous"></script>'
    . '<script> php_data = { user_id:' . $user_id . ', nonce:"' . wp_create_nonce('allow2_nonce_' . $user_id) . '"}; ajaxurl="' . admin_url('admin-ajax.php') . '";</script>'
    . '<script src="' . plugin_dir_url(__FILE__) . 'lib/allow2request.js"></script>';

  wp_die($pageData, 'Allow2 Limited', array('response' => '503'));
}

add_action('get_header', 'allow2_checkAndLog');

?>