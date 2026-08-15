<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$opts = get_option( 'af_email_options', array() );
?>
<div class="wrap af-wrap">
	<h1>Email Marketing Settings</h1>
	<hr class="wp-header-end">

	<?php if ( isset( $_GET['saved'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="af_save_settings">
		<?php wp_nonce_field( 'af_save_settings' ); ?>

		<h2>SMTP (one.com)</h2>
		<p>Configure your one.com outgoing mail server. These settings override WordPress default mail for all emails sent by this plugin.</p>
		<table class="form-table">
			<tr>
				<th><label for="smtp_host">SMTP Host</label></th>
				<td>
					<input type="text" id="smtp_host" name="smtp_host"
						value="<?php echo esc_attr( $opts['smtp_host'] ?? 'send.one.com' ); ?>"
						class="regular-text">
					<p class="description">Default for one.com: <code>send.one.com</code></p>
				</td>
			</tr>
			<tr>
				<th><label for="smtp_port">Port</label></th>
				<td>
					<input type="number" id="smtp_port" name="smtp_port"
						value="<?php echo esc_attr( $opts['smtp_port'] ?? 465 ); ?>"
						class="small-text">
					<p class="description">Default for one.com SSL: <code>465</code></p>
				</td>
			</tr>
			<tr>
				<th><label for="smtp_encryption">Encryption</label></th>
				<td>
					<select id="smtp_encryption" name="smtp_encryption">
						<option value="ssl" <?php selected( $opts['smtp_encryption'] ?? 'ssl', 'ssl' ); ?>>SSL</option>
						<option value="tls" <?php selected( $opts['smtp_encryption'] ?? '', 'tls' ); ?>>TLS (STARTTLS)</option>
						<option value=""    <?php selected( $opts['smtp_encryption'] ?? '', '' ); ?>>None</option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="smtp_user">Username</label></th>
				<td>
					<input type="text" id="smtp_user" name="smtp_user"
						value="<?php echo esc_attr( $opts['smtp_user'] ?? '' ); ?>"
						class="regular-text"
						placeholder="arnel@arnelfernandez.com"
						autocomplete="off">
				</td>
			</tr>
			<tr>
				<th><label for="smtp_pass">Password</label></th>
				<td>
					<input type="password" id="smtp_pass" name="smtp_pass"
						value="<?php echo esc_attr( $opts['smtp_pass'] ?? '' ); ?>"
						class="regular-text"
						autocomplete="new-password">
				</td>
			</tr>
			<tr>
				<th><label for="from_name">From Name</label></th>
				<td>
					<input type="text" id="from_name" name="from_name"
						value="<?php echo esc_attr( $opts['from_name'] ?? get_bloginfo( 'name' ) ); ?>"
						class="regular-text"
						placeholder="Arnel Fernandez">
				</td>
			</tr>
			<tr>
				<th><label for="from_email">From Email</label></th>
				<td>
					<input type="email" id="from_email" name="from_email"
						value="<?php echo esc_attr( $opts['from_email'] ?? get_option( 'admin_email' ) ); ?>"
						class="regular-text">
				</td>
			</tr>
		</table>

		<h2>Subscribe Form</h2>
		<p>
			Use the shortcode <code>[subscribe_form]</code> to embed the form anywhere, or enable auto-append below.
			Optional attributes: <code>title</code>, <code>description</code>, <code>button</code>, <code>style</code> (default | minimal | boxed).
		</p>
		<table class="form-table">
			<tr>
				<th><label for="form_title">Form Title</label></th>
				<td>
					<input type="text" id="form_title" name="form_title"
						value="<?php echo esc_attr( $opts['form_title'] ?? '' ); ?>"
						class="regular-text">
				</td>
			</tr>
			<tr>
				<th><label for="form_description">Description</label></th>
				<td>
					<textarea id="form_description" name="form_description" rows="2" class="large-text"><?php echo esc_textarea( $opts['form_description'] ?? '' ); ?></textarea>
				</td>
			</tr>
			<tr>
				<th><label for="button_text">Button Text</label></th>
				<td>
					<input type="text" id="button_text" name="button_text"
						value="<?php echo esc_attr( $opts['button_text'] ?? 'Subscribe' ); ?>"
						class="regular-text">
				</td>
			</tr>
			<tr>
				<th><label for="success_message">Success Message</label></th>
				<td>
					<input type="text" id="success_message" name="success_message"
						value="<?php echo esc_attr( $opts['success_message'] ?? "You're subscribed!" ); ?>"
						class="regular-text">
				</td>
			</tr>
			<tr>
				<th>Auto-Append to Posts</th>
				<td>
					<label>
						<input type="checkbox" name="auto_append_posts" value="1"
							<?php checked( ! empty( $opts['auto_append_posts'] ) ); ?>>
						Automatically show subscribe form at the bottom of every blog post
					</label>
				</td>
			</tr>
		</table>

		<?php submit_button( 'Save Settings' ); ?>
	</form>
</div>
