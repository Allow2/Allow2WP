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
    <h2>Error</h2>
    <p>There was an error, please close the window and try again.</p>
    <p>
        <button class="btn btn-danger" role="button" onclick="self.close(); false;">Close</button>
    </p>
</div>