<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class AF_Email_Mailer {

	/**
	 * Hooks into phpmailer_init to override WordPress default mail
	 * with one.com SMTP settings stored in plugin options.
	 */
	public static function configure_smtp( $phpmailer ) {
		$opts = get_option( 'af_email_options', array() );

		if ( empty( $opts['smtp_host'] ) || empty( $opts['smtp_user'] ) || empty( $opts['smtp_pass'] ) ) {
			return; // No SMTP configured — fall back to wp_mail default
		}

		$phpmailer->isSMTP();
		$phpmailer->Host       = $opts['smtp_host'];
		$phpmailer->Port       = (int) ( $opts['smtp_port'] ?? 465 );
		$phpmailer->SMTPAuth   = true;
		$phpmailer->Username   = $opts['smtp_user'];
		$phpmailer->Password   = $opts['smtp_pass'];
		$phpmailer->SMTPSecure = $opts['smtp_encryption'] ?? 'ssl';

		if ( ! empty( $opts['from_email'] ) ) {
			$phpmailer->From     = $opts['from_email'];
			$phpmailer->FromName = $opts['from_name'] ?? get_bloginfo( 'name' );
		}
	}

	/**
	 * Send a newsletter to all active subscribers.
	 * Returns array with 'success', 'message', 'sent', 'errors'.
	 */
	public static function send_newsletter( $newsletter_id ) {
		global $wpdb;

		$table      = $wpdb->prefix . AF_EMAIL_TABLE_EMAILS;
		$newsletter = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE id = %d",
			absint( $newsletter_id )
		) );

		if ( ! $newsletter ) {
			return array( 'success' => false, 'message' => 'Newsletter not found.' );
		}

		$subscribers = AF_Email_Subscribers::get_active_for_send();

		if ( empty( $subscribers ) ) {
			return array( 'success' => false, 'message' => 'No active subscribers to send to.' );
		}

		$opts   = get_option( 'af_email_options', array() );
		$sent   = 0;
		$errors = 0;

		foreach ( $subscribers as $sub ) {
			$unsub_url = add_query_arg(
				array( 'af_unsubscribe' => 1, 'token' => $sub->token ),
				home_url()
			);

			$greeting = $sub->first_name ? "Hi {$sub->first_name}," : 'Hi there,';
			$footer   = "\n\n---\nTo unsubscribe: " . esc_url( $unsub_url );
			$body     = $greeting . "\n\n" . $newsletter->body . $footer;

			$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
			if ( ! empty( $opts['from_email'] ) ) {
				$from_name = ! empty( $opts['from_name'] ) ? $opts['from_name'] : get_bloginfo( 'name' );
				$headers[] = "From: {$from_name} <{$opts['from_email']}>";
			}

			$ok = wp_mail( $sub->email, $newsletter->subject, $body, $headers );
			$ok ? $sent++ : $errors++;
		}

		// Update newsletter record
		$wpdb->update( $table, array(
			'status'     => 'sent',
			'sent_count' => $sent,
			'sent_at'    => current_time( 'mysql' ),
		), array( 'id' => absint( $newsletter_id ) ) );

		$msg = "Sent to {$sent} subscriber" . ( $sent !== 1 ? 's' : '' ) . '.';
		if ( $errors ) {
			$msg .= " ({$errors} failed — check your SMTP settings.)";
		}

		return array(
			'success' => true,
			'message' => $msg,
			'sent'    => $sent,
			'errors'  => $errors,
		);
	}

	/** Send a test email to a single address. */
	public static function send_test( $to, $subject, $body ) {
		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
		return wp_mail( $to, '[TEST] ' . $subject, $body, $headers );
	}
}
