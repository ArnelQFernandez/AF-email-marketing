<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class AF_Email_Database {

	public static function activate() {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();

		$subs_table  = $wpdb->prefix . AF_EMAIL_TABLE_SUBS;
		$email_table = $wpdb->prefix . AF_EMAIL_TABLE_EMAILS;

		$sql1 = "CREATE TABLE IF NOT EXISTS {$subs_table} (
			id            bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			email         varchar(200)        NOT NULL,
			first_name    varchar(100)        NOT NULL DEFAULT '',
			status        varchar(20)         NOT NULL DEFAULT 'active',
			token         varchar(64)                  DEFAULT NULL,
			ip_address    varchar(45)         NOT NULL DEFAULT '',
			subscribed_at datetime            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY email (email),
			KEY status (status)
		) {$charset};";

		$sql2 = "CREATE TABLE IF NOT EXISTS {$email_table} (
			id         bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			subject    varchar(255)        NOT NULL,
			body       longtext            NOT NULL,
			status     varchar(20)         NOT NULL DEFAULT 'draft',
			sent_count int                 NOT NULL DEFAULT 0,
			sent_at    datetime                     DEFAULT NULL,
			created_at datetime            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql1 );
		dbDelta( $sql2 );

		add_option( 'af_email_options', array(
			'smtp_host'          => 'send.one.com',
			'smtp_port'          => 465,
			'smtp_encryption'    => 'ssl',
			'smtp_user'          => '',
			'smtp_pass'          => '',
			'from_name'          => get_bloginfo( 'name' ),
			'from_email'         => get_option( 'admin_email' ),
			'form_title'         => 'Get updates from Arnel',
			'form_description'   => 'Subscribe for my latest posts, tools, and tips.',
			'button_text'        => 'Subscribe',
			'success_message'    => "You're in! Check your inbox for a confirmation.",
			'auto_append_posts'  => 0,
		) );

		update_option( 'af_email_db_version', AF_EMAIL_VERSION );
	}
}
