<?php
/**
 *
 * @package     Allow2
 * @author      Allow2 Pty Ltd
 * @copyright   2016 Allow2 Pty Ltd
 * @license     https://www.allow2.com/developer-license/
 *
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

$homeUrl = get_home_url();
$host = 'https://api.allow2.com';
if ($_POST['allow2_setup'] == 'Y') {

  /* check nonce nonce from form or die() */
  if (!isset($_POST['allow2_form_nonce']) || !wp_verify_nonce($_POST['allow2_form_nonce'], 'allow2_form_submit')) {
    die("Please refresh page and try again.");
  }

  //Form data sent
  $a2token = $_POST['allow2_token'];
  $a2secret = $_POST['allow2_secret'];

  $params = [
    'token' => $a2token,
    'secret' => $a2secret,
    'redirect' => site_url() . '?allow2_callback=oauth',
    'webHook' => plugin_dir_url(__FILE__) . 'allow2_webhook.php'
  ];

  $url = $host . '/serviceapi/confirmSettings';

  $postargs = array(
    'body' => $params,
    'timeout' => 10
  );
  $response = wp_remote_post($url, $postargs);
  $response_body = wp_remote_retrieve_body($response);
  $httpCode = wp_remote_retrieve_response_code($response);

  if (is_wp_error($response_body)) {
    ?>
      <div class="error"><p><strong><?php _e($error); ?></strong></p></div>
    <?php
  } else if ($httpCode != 200) {
    ?>
      <div class="error"><p><strong><?php _e('Incorrect Token, Secret or Redirect URI'); ?></strong></p></div>
    <?php
  } else {
    $obj = json_decode($response_body, true);
    $a2userId = $obj["accountId"];
    update_option('allow2_token', $a2token);
    update_option('allow2_secret', $a2secret);
    update_option('allow2_userId', $a2userId);
    ?>
      <div class="updated"><p><strong><?php _e('Connected to Allow2.'); ?></strong></p></div>
    <?php
  }
  ?>

  <?php
} else if ($_POST['allow2_disconnect'] == 'Y') {
  $a2token = '';
  $a2secret = '';
  $a2userId = false;
  update_option('allow2_token', $a2token);
  update_option('allow2_secret', $a2secret);
  update_option('allow2_userId', $a2userId);

} else {
  //Normal page display
  $a2token = get_option('allow2_token', '');
  $a2secret = get_option('allow2_secret', '');
  $a2userId = get_option('allow2_userId');
}

if ($a2userId) {
  ?>
    <div class="wrap">
      <?php echo '<h2><img width=30 height=30 src="' . plugin_dir_url(__FILE__) . 'assets/icon-30x30.png">&nbsp;' . __('Allow2', 'allow2_trdom') . "</h2>";
      if (!class_exists('UseClientsTimezone')) {
        ?>
          <div>
              WARNING: The Allow2 WP plugin requires <a target="timezoneplugin" href="<?php echo esc_attr($homeUrl); ?>/wp-admin/plugin-install.php?tab=plugin-information&plugin=use-clients-time-zone">Use Client's Time Zone</a>, but it seems to either be missing or not activated.
              Allow2 will be disabled until it has been correctly installed.
          </div>
        <?php
      }
      ?>
        <div>
            <form name="allow2_form" method="post" action="<?php echo str_replace('%7E', '~', $_SERVER['REQUEST_URI']); ?>">
                <input type="hidden" name="allow2_disconnect" value="Y">
                <table class="form-table">
                    <tr>
                        <th><label for="allow2_token"><?php _e("Token :"); ?></label></th>
                        <td aria-live="assertive">
                            <div class="allow2_token">
                              <?php echo esc_attr($a2token); ?>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="allow2_secret"><?php _e("Secret :"); ?></label></th>
                        <td aria-live="assertive">
                            <div class="allow2_secret">
                              <?php echo esc_attr($a2secret); ?>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="allow2_redirect_uri"><?php _e("Redirect Uri :"); ?></label></th>
                        <td aria-live="assertive">
                            <div class="allow2_redirect_uri">
                              <?php echo site_url() . '?allow2_callback=oauth'; ?>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="allow2_webhook_uri"><?php _e("Webhook Uri :"); ?></label></th>
                        <td aria-live="assertive">
                            <div class="allow2_webhook_uri">
                              <?php echo plugin_dir_url(__FILE__) . 'allow2_webhook.php'; ?>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th></th>
                        <td aria-live="assertive">
                            <p class="description">These all need to match the entry in your
                                <a target="Allow2" href="https://developer.allow2.com/">Allow2 service settings</a>.
                            </p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <input type="submit" name="Submit" value="<?php _e('Disconnect', 'allow2_trdom') ?>"/>
                </p>
            </form>
        </div>
    </div>

  <?php
} else {
  ?>
    <div class="wrap">
      <?php echo '<h2><img width=30 height=30 src="' . plugin_dir_url(__FILE__) . 'assets/icon-30x30.png">&nbsp;' . __('Allow2', 'allow2_trdom') . '</h2>';
      if (!class_exists('UseClientsTimezone')) {
        ?>
          <div>
              WARNING: The Allow2 WP plugin requires <a target="timezoneplugin" href="<?php echo esc_attr($homeUrl); ?>/wp-admin/plugin-install.php?tab=plugin-information&plugin=use-clients-time-zone">Use Client's Time Zone</a>, but it seems to either be missing or not activated.
              Allow2 will be disabled until it has been correctly installed.
          </div>
        <?php
      }
      ?>
        <div>
            <form id="allow2_connect_form" name="allow2_form" method="post" action="<?php echo str_replace('%7E', '~', $_SERVER['REQUEST_URI']); ?>">
              <?php wp_nonce_field('allow2_form_submit', 'allow2_form_nonce'); ?>
                <input type="hidden" name="allow2_setup" value="Y">
                <table class="form-table">
                    <tr>
                        <th><label for="allow2_token"><?php _e("Token :"); ?></label></th>
                        <td aria-live="assertive">
                            <div class="allow2_token">
                                <input type="text" name="allow2_token" id="allow2_token" value="<?php echo esc_attr($a2token); ?>" size="50">&nbsp; <?php _e(" eg: 4ecf0c4e-defd-4c22-8e7c-2b3620053fa9"); ?>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="allow2_secret"><?php _e("Secret :"); ?></label></th>
                        <td aria-live="assertive">
                            <div class="allow2_secret">
                                <input type="text" name="allow2_secret" id="allow2_secret" value="<?php echo esc_attr($a2secret); ?>" size="50">&nbsp; <?php _e(" eg: 06baaf32-7164-4540-857d-8b7199632662"); ?>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="allow2_redirect_uri"><?php _e("Redirect Uri :"); ?></label></th>
                        <td aria-live="assertive">
                            <div class="allow2_redirect_uri">
                              <?php echo site_url() . '?allow2_callback=oauth'; ?>
                            </div>
                            <!-- <button class="btn" data-clipboard-target=".allow2_redirect_uri">Copy to Clipboard</button> -->
                        </td>
                    </tr>
                    <tr>
                        <th><label for="allow2_webhook_uri"><?php _e("Webhook Uri :"); ?></label></th>
                        <td aria-live="assertive">
                            <div class="allow2_webhook_uri">
                              <?php
                              /* todo: update webhook */
                              echo plugin_dir_url(__FILE__) . 'allow2_webhook.php'; ?>
                            </div>
                            <!-- <button class="btn" data-clipboard-target=".allow2_webhook_uri">Copy to Clipboard</button>-->
                        </td>
                    </tr>

                    <tr>
                        <th></th>
                        <td aria-live="assertive">
                            <p class="description">These all need to match the entry in your
                                <a target="Allow2" href="https://developer.allow2.com/">Allow2 service settings</a>.
                            </p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <input id="allow2_connect_button" type="submit" name="Submit" value="<?php _e('Connect', 'allow2_trdom'); ?>" onClick="allow2_submitConnect();"/>
                </p>
                <i id="allow2_connect_spinner" class="fa fa-spinner fa-pulse fa-2x" style="display: none;" aria-hidden="true"></i>

                <script type="text/javascript">
                  function allow2_submitConnect() {
                    console.log('connecting...');
                    var spinner = jQuery('#allow2_connect_spinner');
                    var button = jQuery('#allow2_connect_button');
                    spinner.css("display", "inline-block");
                    button.prop('disabled', true);
                    console.log('connecting...');
                    jQuery('#allow2_connect_form').submit();
                  }
                </script>
            </form>
        </div>
    </div>
  <?php
  wp_enqueue_script('clipboard', plugin_dir_url(__FILE__) . 'lib/clipboard.min.js', array('jquery'), '0.1');
}
?>