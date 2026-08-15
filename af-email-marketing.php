<?php
/**
 * Plugin Name: AF Email Marketing
 * Plugin URI:  https://arnelfernandez.com
 * Description: Email opt-in forms, subscriber management, and newsletter sending. Built for arnelfernandez.com.
 * Version:     1.3.0
 * Author:      Arnel Fernandez
 * Author URI:  https://arnelfernandez.com
 * License:     GPL v2 or later
 * Text Domain: af-email
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'AF_EMAIL_VERSION',      '1.3.0' );
define( 'AF_EMAIL_DIR',          plugin_dir_path( __FILE__ ) );
define( 'AF_EMAIL_URL',          plugin_dir_url( __FILE__ ) );
define( 'AF_EMAIL_TABLE_SUBS',   'af_subscribers' );
define( 'AF_EMAIL_TABLE_EMAILS', 'af_newsletters' );

require_once AF_EMAIL_DIR . 'includes/class-database.php';
require_once AF_EMAIL_DIR . 'includes/class-subscribers.php';
require_once AF_EMAIL_DIR . 'includes/class-forms.php';
require_once AF_EMAIL_DIR . 'includes/class-mailer.php';
require_once AF_EMAIL_DIR . 'admin/class-admin.php';

class AF_Email_Marketing {

	private static $instance;

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		register_activation_hook( __FILE__, array( 'AF_Email_Database', 'activate' ) );
		add_action( 'plugins_loaded', array( $this, 'boot' ) );
	}

	public function boot() {
		// Shortcode
		add_shortcode( 'subscribe_form', array( 'AF_Email_Forms', 'shortcode' ) );

		// Public assets
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public' ) );

		// AJAX subscription (logged in + logged out)
		add_action( 'wp_ajax_nopriv_af_subscribe', array( 'AF_Email_Forms', 'ajax_subscribe' ) );
		add_action( 'wp_ajax_af_subscribe',        array( 'AF_Email_Forms', 'ajax_subscribe' ) );

		// Auto-append form to post content if enabled
		$opts = get_option( 'af_email_options', array() );
		if ( ! empty( $opts['auto_append_posts'] ) ) {
			add_filter( 'the_content', array( 'AF_Email_Forms', 'auto_append' ) );
		}

		// SMTP override
		add_action( 'phpmailer_init', array( 'AF_Email_Mailer', 'configure_smtp' ) );

		// Unsubscribe handler
		add_action( 'init', array( $this, 'handle_unsubscribe' ) );

		// Admin
		if ( is_admin() ) {
			new AF_Email_Admin();
		}
	}

	public function handle_unsubscribe() {
		if ( isset( $_GET['af_unsubscribe'], $_GET['token'] ) ) {
			$token = sanitize_text_field( $_GET['token'] );
			AF_Email_Subscribers::unsubscribe_by_token( $token );
			wp_die(
				'You have been unsubscribed. <a href="' . esc_url( home_url() ) . '">Return to site</a>',
				'Unsubscribed',
				array( 'response' => 200 )
			);
		}
	}

	public function enqueue_public() {
		wp_enqueue_style(
			'af-email',
			AF_EMAIL_URL . 'public/css/form.css',
			array(),
			AF_EMAIL_VERSION
		);
		wp_enqueue_script(
			'af-email',
			AF_EMAIL_URL . 'public/js/form.js',
			array( 'jquery' ),
			AF_EMAIL_VERSION,
			true
		);
		wp_localize_script( 'af-email', 'AF_Email', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'af_subscribe' ),
		) );
	}
}

AF_Email_Marketing::instance();
