<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class AF_Email_Subscribers {

	private static function table() {
		global $wpdb;
		return $wpdb->prefix . AF_EMAIL_TABLE_SUBS;
	}

	/**
	 * Subscribe an email address.
	 * Returns array with 'success' (bool) and 'message' (string).
	 */
	public static function subscribe( $email, $first_name = '', $ip = '' ) {
		global $wpdb;
		$table = self::table();

		$existing = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE email = %s",
			$email
		) );

		if ( $existing ) {
			if ( 'active' === $existing->status ) {
				return array( 'success' => false, 'message' => 'You are already subscribed!' );
			}
			// Reactivate unsubscribed user
			$wpdb->update( $table, array( 'status' => 'active', 'first_name' => $first_name ), array( 'email' => $email ) );
			return array( 'success' => true, 'message' => "Welcome back! You're subscribed again." );
		}

		$token  = wp_generate_password( 32, false );
		$result = $wpdb->insert( $table, array(
			'email'        => $email,
			'first_name'   => $first_name,
			'status'       => 'active',
			'token'        => $token,
			'ip_address'   => $ip,
			'subscribed_at' => current_time( 'mysql' ),
		) );

		if ( $result ) {
			self::send_welcome_email( $email, $first_name );
			return array( 'success' => true, 'message' => '' ); // message comes from options
		}

		return array( 'success' => false, 'message' => 'Something went wrong. Please try again.' );
	}

	/** Mark subscriber as unsubscribed via token link. */
	public static function unsubscribe_by_token( $token ) {
		global $wpdb;
		$wpdb->update(
			self::table(),
			array( 'status' => 'unsubscribed' ),
			array( 'token'  => sanitize_text_field( $token ) )
		);
	}

	/** Get subscribers list with optional search/pagination. */
	public static function get_all( $args = array() ) {
		global $wpdb;
		$table = self::table();

		$defaults = array(
			'status'  => 'active',
			'search'  => '',
			'limit'   => 50,
			'offset'  => 0,
			'orderby' => 'subscribed_at',
			'order'   => 'DESC',
		);
		$args = wp_parse_args( $args, $defaults );

		$where = '1=1';
		$values = array();

		if ( $args['status'] ) {
			$where   .= ' AND status = %s';
			$values[] = $args['status'];
		}

		if ( $args['search'] ) {
			$like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where   .= ' AND (email LIKE %s OR first_name LIKE %s)';
			$values[] = $like;
			$values[] = $like;
		}

		$allowed_orderby = array( 'subscribed_at', 'email', 'first_name', 'status' );
		$orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'subscribed_at';
		$order   = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

		$limit  = absint( $args['limit'] );
		$offset = absint( $args['offset'] );

		$sql = "SELECT * FROM {$table} WHERE {$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
		array_push( $values, $limit, $offset );

		return $wpdb->get_results( $wpdb->prepare( $sql, $values ) );
	}

	/** Count subscribers by status. Empty string = all. */
	public static function count( $status = 'active' ) {
		global $wpdb;
		$table = self::table();
		if ( $status ) {
			return (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE status = %s",
				$status
			) );
		}
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
	}

	/** Hard-delete a subscriber by ID. */
	public static function delete( $id ) {
		global $wpdb;
		$wpdb->delete( self::table(), array( 'id' => absint( $id ) ) );
	}

	/** Get all active subscribers for sending newsletters. */
	public static function get_active_for_send() {
		global $wpdb;
		$table = self::table();
		return $wpdb->get_results(
			"SELECT email, first_name, token FROM {$table} WHERE status = 'active'"
		);
	}

	/** Export all subscribers as a CSV download. */
	public static function export_csv() {
		$subscribers = self::get_all( array( 'limit' => 999999, 'status' => '' ) );

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="subscribers-' . date( 'Y-m-d' ) . '.csv"' );

		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, array( 'ID', 'Email', 'First Name', 'Status', 'Subscribed At', 'IP' ) );
		foreach ( $subscribers as $sub ) {
			fputcsv( $out, array(
				$sub->id,
				$sub->email,
				$sub->first_name,
				$sub->status,
				$sub->subscribed_at,
				$sub->ip_address,
			) );
		}
		fclose( $out );
		exit;
	}

	private static function send_welcome_email( $email, $first_name ) {
		$opts    = get_option( 'af_email_options', array() );
		$name    = $first_name ? $first_name : 'there';
		$site    = get_bloginfo( 'name' );
		$from    = ! empty( $opts['from_name'] ) ? $opts['from_name'] : $site;
		$subject = 'Welcome to ' . $site . '!';
		$body    = "Hi {$name},\n\nThanks for subscribing! I'll let you know when I publish something new.\n\n{$from}\n" . home_url();
		wp_mail( $email, $subject, $body );
	}
}
