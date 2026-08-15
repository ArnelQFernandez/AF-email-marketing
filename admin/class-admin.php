<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class AF_Email_Admin {

	public function __construct() {
		add_action( 'admin_menu',            array( $this, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// Form handlers
		add_action( 'admin_post_af_save_settings',      array( $this, 'save_settings' ) );
		add_action( 'admin_post_af_save_newsletter',    array( $this, 'save_newsletter' ) );
		add_action( 'admin_post_af_send_newsletter',    array( $this, 'send_newsletter' ) );
		add_action( 'admin_post_af_send_test_email',    array( $this, 'send_test_email' ) );
		add_action( 'admin_post_af_delete_subscriber',  array( $this, 'delete_subscriber' ) );
		add_action( 'admin_post_af_export_subscribers', array( $this, 'export_subscribers' ) );
	}

	public function add_menu() {
		add_menu_page(
			'Email Marketing',
			'Email Marketing',
			'manage_options',
			'af-email',
			array( $this, 'page_subscribers' ),
			'dashicons-email-alt2',
			26
		);
		add_submenu_page(
			'af-email', 'Subscribers', 'Subscribers',
			'manage_options', 'af-email',
			array( $this, 'page_subscribers' )
		);
		add_submenu_page(
			'af-email', 'Send Newsletter', 'Send Newsletter',
			'manage_options', 'af-email-newsletter',
			array( $this, 'page_newsletter' )
		);
		add_submenu_page(
			'af-email', 'Settings', 'Settings',
			'manage_options', 'af-email-settings',
			array( $this, 'page_settings' )
		);
	}

	public function enqueue_assets( $hook ) {
		if ( false === strpos( $hook, 'af-email' ) ) return;

		wp_enqueue_style(
			'af-email-admin',
			AF_EMAIL_URL . 'admin/css/admin.css',
			array(),
			AF_EMAIL_VERSION
		);

		// Load WP editor only on newsletter page
		if ( false !== strpos( $hook, 'newsletter' ) ) {
			wp_enqueue_editor();
		}
	}

	// ── Page loaders ──────────────────────────────────────────────────────

	public function page_subscribers() {
		include AF_EMAIL_DIR . 'admin/pages/subscribers.php';
	}

	public function page_newsletter() {
		include AF_EMAIL_DIR . 'admin/pages/newsletter.php';
	}

	public function page_settings() {
		include AF_EMAIL_DIR . 'admin/pages/settings.php';
	}

	// ── Form handlers ─────────────────────────────────────────────────────

	public function save_settings() {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
		check_admin_referer( 'af_save_settings' );

		$opts = array(
			'smtp_host'         => sanitize_text_field( $_POST['smtp_host']         ?? '' ),
			'smtp_port'         => absint( $_POST['smtp_port']                       ?? 465 ),
			'smtp_encryption'   => sanitize_text_field( $_POST['smtp_encryption']   ?? 'ssl' ),
			'smtp_user'         => sanitize_text_field( $_POST['smtp_user']         ?? '' ),
			'smtp_pass'         => $_POST['smtp_pass']                               ?? '',
			'from_name'         => sanitize_text_field( $_POST['from_name']         ?? '' ),
			'from_email'        => sanitize_email( $_POST['from_email']             ?? '' ),
			'form_title'        => sanitize_text_field( $_POST['form_title']        ?? '' ),
			'form_description'  => sanitize_textarea_field( $_POST['form_description'] ?? '' ),
			'button_text'       => sanitize_text_field( $_POST['button_text']       ?? '' ),
			'success_message'   => sanitize_text_field( $_POST['success_message']   ?? '' ),
			'auto_append_posts' => ! empty( $_POST['auto_append_posts'] ) ? 1 : 0,
		);

		update_option( 'af_email_options', $opts );
		wp_redirect( admin_url( 'admin.php?page=af-email-settings&saved=1' ) );
		exit;
	}

	public function save_newsletter() {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
		check_admin_referer( 'af_save_newsletter' );

		global $wpdb;
		$table   = $wpdb->prefix . AF_EMAIL_TABLE_EMAILS;
		$subject = sanitize_text_field( $_POST['subject'] ?? '' );
		$body    = wp_kses_post( $_POST['body'] ?? '' );
		$id      = absint( $_POST['newsletter_id'] ?? 0 );

		if ( $id ) {
			$wpdb->update( $table, array( 'subject' => $subject, 'body' => $body ), array( 'id' => $id ) );
		} else {
			$wpdb->insert( $table, array(
				'subject'    => $subject,
				'body'       => $body,
				'status'     => 'draft',
				'created_at' => current_time( 'mysql' ),
			) );
			$id = $wpdb->insert_id;
		}

		wp_redirect( admin_url( 'admin.php?page=af-email-newsletter&newsletter_id=' . $id . '&saved=1' ) );
		exit;
	}

	public function send_newsletter() {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
		check_admin_referer( 'af_send_newsletter' );

		$id     = absint( $_POST['newsletter_id'] ?? 0 );
		$result = AF_Email_Mailer::send_newsletter( $id );

		$status = $result['success'] ? 'sent' : 'error';
		$msg    = urlencode( $result['message'] );
		wp_redirect( admin_url( "admin.php?page=af-email-newsletter&newsletter_id={$id}&status={$status}&msg={$msg}" ) );
		exit;
	}

	public function send_test_email() {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
		check_admin_referer( 'af_send_test_email' );

		$to      = sanitize_email( $_POST['test_email']  ?? get_option( 'admin_email' ) );
		$subject = sanitize_text_field( $_POST['subject'] ?? 'Test Email' );
		$body    = wp_kses_post( $_POST['body']           ?? '' );
		$id      = absint( $_POST['newsletter_id']        ?? 0 );

		$ok     = AF_Email_Mailer::send_test( $to, $subject, $body );
		$status = $ok ? 'test_sent' : 'test_error';
		wp_redirect( admin_url( "admin.php?page=af-email-newsletter&newsletter_id={$id}&status={$status}" ) );
		exit;
	}

	public function delete_subscriber() {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
		check_admin_referer( 'af_delete_subscriber' );

		AF_Email_Subscribers::delete( absint( $_POST['subscriber_id'] ?? 0 ) );
		wp_redirect( admin_url( 'admin.php?page=af-email&deleted=1' ) );
		exit;
	}

	public function export_subscribers() {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
		check_admin_referer( 'af_export_subscribers' );
		AF_Email_Subscribers::export_csv();
	}
}
