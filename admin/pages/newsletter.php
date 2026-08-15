<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$table = $wpdb->prefix . AF_EMAIL_TABLE_EMAILS;

$newsletter_id = absint( $_GET['newsletter_id'] ?? 0 );
$newsletter    = $newsletter_id
	? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $newsletter_id ) )
	: null;

$active_count    = AF_Email_Subscribers::count( 'active' );
$all_newsletters = $wpdb->get_results( "SELECT id, subject, status, created_at FROM {$table} ORDER BY created_at DESC LIMIT 30" );
?>
<div class="wrap af-wrap">
	<h1>Newsletter</h1>
	<hr class="wp-header-end">

	<?php if ( isset( $_GET['saved'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p>Draft saved.</p></div>
	<?php endif; ?>

	<?php if ( isset( $_GET['status'] ) ) :
		$status_class = in_array( $_GET['status'], array( 'sent', 'test_sent' ), true ) ? 'success' : 'error';
		$status_msg   = match ( $_GET['status'] ) {
			'sent'       => esc_html( urldecode( $_GET['msg'] ?? 'Newsletter sent!' ) ),
			'test_sent'  => 'Test email sent successfully.',
			'test_error' => 'Test email failed. Check your SMTP settings.',
			default      => esc_html( urldecode( $_GET['msg'] ?? 'An error occurred.' ) ),
		};
	?>
		<div class="notice notice-<?php echo $status_class; ?> is-dismissible"><p><?php echo $status_msg; ?></p></div>
	<?php endif; ?>

	<div class="af-newsletter-layout">

		<!-- Sidebar: previous newsletters -->
		<div class="af-newsletter-sidebar">
			<a
				href="<?php echo esc_url( admin_url( 'admin.php?page=af-email-newsletter' ) ); ?>"
				class="button button-primary af-new-btn"
			>+ New Newsletter</a>

			<?php if ( $all_newsletters ) : ?>
				<ul class="af-newsletter-list">
					<?php foreach ( $all_newsletters as $nl ) : ?>
					<li class="<?php echo $nl->id === $newsletter_id ? 'active' : ''; ?>">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=af-email-newsletter&newsletter_id=' . $nl->id ) ); ?>">
							<strong><?php echo esc_html( $nl->subject ?: '(no subject)' ); ?></strong>
							<span class="af-nl-meta">
								<?php echo esc_html( date( 'M j', strtotime( $nl->created_at ) ) ); ?>
								&bull;
								<span class="af-status af-status-<?php echo esc_attr( $nl->status ); ?>"><?php echo esc_html( $nl->status ); ?></span>
							</span>
						</a>
					</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p style="color:#666;font-size:13px;">No newsletters yet.</p>
			<?php endif; ?>
		</div>

		<!-- Main: compose -->
		<div class="af-newsletter-compose">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action"        value="af_save_newsletter">
				<input type="hidden" name="newsletter_id" value="<?php echo absint( $newsletter_id ); ?>">
				<?php wp_nonce_field( 'af_save_newsletter' ); ?>

				<div class="af-field">
					<label for="nl-subject"><strong>Subject line</strong></label>
					<input
						type="text"
						id="nl-subject"
						name="subject"
						class="large-text"
						value="<?php echo esc_attr( $newsletter->subject ?? '' ); ?>"
						placeholder="What's this email about?"
						required
					>
				</div>

				<div class="af-field">
					<label><strong>Body</strong></label>
					<?php
					wp_editor( $newsletter->body ?? '', 'body', array(
						'textarea_name' => 'body',
						'media_buttons' => false,
						'textarea_rows' => 16,
						'quicktags'     => true,
						'tinymce'       => array(
							'toolbar1' => 'bold,italic,underline,separator,bullist,numlist,separator,link,unlink,separator,undo,redo',
						),
					) );
					?>
				</div>

				<div class="af-form-actions">
					<button type="submit" class="button button-secondary">Save Draft</button>
				</div>
			</form>

			<?php if ( $newsletter ) : ?>
			<hr style="margin:24px 0;">

				<?php if ( 'sent' === $newsletter->status ) : ?>
					<div class="notice notice-info inline">
						<p>
							Sent on <strong><?php echo esc_html( date( 'M j, Y g:i A', strtotime( $newsletter->sent_at ) ) ); ?></strong>
							to <strong><?php echo number_format( $newsletter->sent_count ); ?></strong> subscriber<?php echo $newsletter->sent_count !== 1 ? 's' : ''; ?>.
						</p>
					</div>

				<?php else : ?>
					<div class="af-send-section">
						<p class="af-send-info">
							<strong><?php echo number_format( $active_count ); ?></strong>
							active subscriber<?php echo $active_count !== 1 ? 's' : ''; ?> will receive this.
						</p>

						<!-- Send test email -->
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="af-test-form">
							<input type="hidden" name="action"        value="af_send_test_email">
							<input type="hidden" name="newsletter_id" value="<?php echo absint( $newsletter_id ); ?>">
							<input type="hidden" name="subject"       value="<?php echo esc_attr( $newsletter->subject ); ?>">
							<input type="hidden" name="body"          value="<?php echo esc_attr( $newsletter->body ); ?>">
							<?php wp_nonce_field( 'af_send_test_email' ); ?>
							<input type="email" name="test_email" value="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" class="regular-text">
							<button type="submit" class="button">Send Test</button>
						</form>

						<!-- Send to all -->
						<form
							method="post"
							action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
							onsubmit="return confirm('Send to all <?php echo absint( $active_count ); ?> subscribers? This cannot be undone.')"
						>
							<input type="hidden" name="action"        value="af_send_newsletter">
							<input type="hidden" name="newsletter_id" value="<?php echo absint( $newsletter_id ); ?>">
							<?php wp_nonce_field( 'af_send_newsletter' ); ?>
							<button type="submit" class="button button-primary af-send-all-btn">
								Send to All Subscribers
							</button>
						</form>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>
	</div>
</div>
