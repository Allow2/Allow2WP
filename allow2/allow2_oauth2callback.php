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
?>
<div class="container" style="text-align: center;">
    <p>&nbsp;</p>
    <h1><img width=30 height=30 src="<?= plugin_dir_url(__FILE__) ?>assets/icon-30x30.png"> Allow2</h1>
    <h2 class="display-3">Pairing... &nbsp;<img style="max-width: 100%;, height: auto;" src="/wp-admin/images/wpspin_light.gif" alt=""></h2>
</div>
<script type="text/javascript">
  var loc = window.location;
  var baseUrl = loc.protocol + "//" + loc.hostname + (loc.port ? ":" + loc.port : "");
  window.onload = function () {
    var result = new RegExp("code=([^&]*)", "i").exec(window.location.search);
    var code = result && unescape(result[1]) || "";
    window.opener.postMessage(code, baseUrl);
  };
</script>