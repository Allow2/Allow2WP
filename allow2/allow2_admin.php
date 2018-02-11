<?php

    $host = 'https://app.allow2.com:8443';
    if($_POST['allow2_setup'] == 'Y') {
        //Form data sent
        $a2token = $_POST['allow2_token'];
        $a2secret = $_POST['allow2_secret'];
		$a2sandbox = $_POST['allow2_sandbox'];
		
        $params = [
            'token' => $a2token,
            'secret' => $a2secret,
            'redirect' => plugin_dir_url( __FILE__ ) . 'allow2_oauth2callback.php'
        ];
        
    	$host = 'https://app.allow2.com:8443';
        $url = $host . '/serviceapi/confirmSettings';

		$postargs = array(
			'body' => $params,
			'timeout' => 10
		);
		$response = wp_remote_post($url, $postargs );
		$response_body = wp_remote_retrieve_body( $response );
		$httpCode = wp_remote_retrieve_response_code( $response );
		
		if (is_wp_error( $response_body )) {
			?>
            <div class="error"><p><strong><?php _e( $error ); ?></strong></p></div>
            <?php
        } else if ($httpCode != 200) {
            ?>
            <div class="error"><p><strong><?php _e( 'Incorrect Token, Secret or Redirect URI' ); ?></strong></p></div>
            <?php
        } else {
            $obj = json_decode($response_body, true);
            $a2userId = $obj["accountId"];
            update_option('allow2_token', $a2token);
            update_option('allow2_secret', $a2secret);
            update_option('allow2_sandbox', $a2sandbox);
            update_option('allow2_userId', $a2userId);
            ?>
            <div class="updated"><p><strong><?php _e('Connected to Allow2.' ); ?></strong></p></div>
            <?php
        }
        ?>

        <?php
    } else if($_POST['allow2_disconnect'] == 'Y') {
        $a2token = '';
        $a2secret = '';
        $a2sandbox = true;
        $a2userId = false;
        update_option('allow2_token', $a2token);
        update_option('allow2_secret', $a2secret);
        update_option('allow2_sandbox', $a2sandbox);
        update_option('allow2_userId', $a2userId);
        
    } else {
        //Normal page display
        $a2token = get_option('allow2_token', '');
        $a2secret = get_option('allow2_secret', '');
        $a2sandbox = get_option('allow2_sandbox', true);
        $a2userId = get_option('allow2_userId');
    }

    if ($a2userId) {
?>
<div class="wrap">
    <?php echo '<h2><img width=30 height=30 src="' . $host . '/images/logo_sml.png">&nbsp;' . __( 'Allow2', 'allow2_trdom' ) . "</h2>";
    if (!class_exists('UseClientsTimezone')) {
    	?>
    	<div>
    		WARNING: The Allow2 WP plugin requires <a target="timezoneplugin" href="https://a2wp.mystagingwebsite.com/wp-admin/plugin-install.php?tab=plugin-information&plugin=use-clients-time-zone">Use Client's Time Zone</a>, but it seems to either be missing or not activated.
    		Allow2 will be disabled until it has been correctly installed.
    	</div>
    	<?php
    }
    ?>
    <div>
        <form name="allow2_form" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
            <input type="hidden" name="allow2_disconnect" value="Y">
            <table class="form-table">
				<tr>
					<th><label for="allow2_token"><?php _e("Token :"); ?></label></th>
					<td aria-live="assertive">
						<div class="allow2_token">
							<?php echo $a2token; ?>
						</div>
					</td>
				</tr>
				<tr>
					<th><label for="allow2_secret"><?php _e("Secret :"); ?></label></th>
					<td aria-live="assertive">
						<div class="allow2_secret">
							<?php echo $a2secret; ?>
						</div>
					</td>
				</tr>
				<tr>
					<th><label for="allow2_redirect_uri"><?php _e("Redirect Uri :"); ?></label></th>
					<td aria-live="assertive">
						<div class="allow2_redirect_uri">
							<?php echo plugin_dir_url( __FILE__ ) . 'allow2_oauth2callback.php'; ?>
						</div>
					</td>
				</tr>
				<tr>
					<th><label for="allow2_redirect_uri"><?php _e("Mode :"); ?></label></th>
					<td aria-live="assertive">
						<div class="allow2_sandbox">
							<?php 
							if ($a2sandbox) {
								echo 'Sandbox';
							} else {
								echo 'Production';
							}
							?>
						</div>
					</td>
				</tr>
				<tr>
					<th></th>
					<td aria-live="assertive">
						<p class="description">These all need to match the entry in your
							<?php if ($a2sandbox) { ?>
								<a target="Allow2" href="https://staging-developer.allow2.com/">Allow2 <b>Sandbox</b> service settings</a>.
							<?php } else { ?>
								<a target="Allow2" href="https://developer.allow2.com/">Allow2 <b>Production</b> service settings</a>.
							<?php } ?>
						</p>
					</td>
				</tr>
			</table>

            <p class="submit">
                <input type="submit" name="Submit" value="<?php _e('Disconnect', 'allow2_trdom' ) ?>" />
            </p>
        </form>
    </div>
</div>

<?php
} else {
?>
	<div class="wrap">
    <?php echo "<h2>" . __( 'Allow2', 'allow2_trdom' ) . "</h2>";
    if (!class_exists('UseClientsTimezone')) {
    	?>
    	<div>
    		WARNING: The Allow2 WP plugin requires <a target="timezoneplugin" href="https://a2wp.mystagingwebsite.com/wp-admin/plugin-install.php?tab=plugin-information&plugin=use-clients-time-zone">Use Client's Time Zone</a>, but it seems to either be missing or not activated.
    		Allow2 will be disabled until it has been correctly installed.
    	</div>
    	<?php
    }
    ?>
    <div>
        <form name="allow2_form" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
            <input type="hidden" name="allow2_setup" value="Y">
            <table class="form-table">
				<tr>
					<th><label for="allow2_token"><?php _e("Token :"); ?></label></th>
					<td aria-live="assertive">
						<div class="allow2_token">
							<input type="text" name="allow2_token" value="<?php echo $a2token; ?>" size="50">&nbsp; <?php _e(" eg: 4ecf0c4e-defd-4c22-8e7c-2b3620053fa9" ); ?>
						</div>
					</td>
				</tr>
				<tr>
					<th><label for="allow2_secret"><?php _e("Secret :"); ?></label></th>
					<td aria-live="assertive">
						<div class="allow2_secret">
							<input type="text" name="allow2_secret" value="<?php echo $a2secret; ?>" size="50">&nbsp; <?php _e(" eg: 06baaf32-7164-4540-857d-8b7199632662" ); ?>
						</div>
					</td>
				</tr>
				<tr>
					<th><label for="allow2_redirect_uri"><?php _e("Redirect Uri :"); ?></label></th>
					<td aria-live="assertive">
						<div class="allow2_redirect_uri">
							<?php echo plugin_dir_url( __FILE__ ) . 'allow2_oauth2callback.php'; ?>
						</div>
					</td>
				</tr>
				<tr>
					<th><label for="allow2_webhook_uri"><?php _e("Webhook Uri :"); ?></label></th>
					<td aria-live="assertive">
						<div class="allow2_webhook_uri">
							<?php echo plugin_dir_url( __FILE__ ) . 'allow2_webhook.php'; ?>
						</div>
					</td>
				</tr>
				<tr>
					<th><label for="allow2_redirect_uri"><?php _e("Mode :"); ?></label></th>
					<td aria-live="assertive">
						<div class="allow2_sandbox">
							<input type="checkbox" name="allow2_sandbox" value="1"<?php checked( $a2sandbox ); ?> />
							&nbsp;Sandbox
						</div>
					</td>
				</tr>

				<tr>
					<th></th>
					<td aria-live="assertive">
						<p class="description">These all need to match the entry in your
							<a target="Allow2" href="https://staging-developer.allow2.com/">Allow2 <b>Sandbox</b> service settings</a> or
							<a target="Allow2" href="https://developer.allow2.com/">Allow2 <b>Production</b> service settings</a>.
						</p>
					</td>
				</tr>
			</table>

            <p class="submit">
                <input type="submit" name="Submit" value="<?php _e('Connect', 'allow2_trdom' ) ?>" />
            </p>
        </form>
    </div>
</div>
<?php
    }
?>