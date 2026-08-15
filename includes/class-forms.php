<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class AF_Email_Forms {

	/**
	 * Shortcode: [subscribe_form title="..." description="..." button="..." style="default|minimal|boxed"]
	 */
	public static function shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'title'       => '',
			'description' => '',
			'button'      => '',
			'style'       => 'default',
		), $atts, 'subscribe_form' );

		$opts = get_option( 'af_email_options', array() );

		$title  = $atts['title']       ?: ( $opts['form_title']       ?? 'Stay in the loop' );
		$desc   = $atts['description'] ?: ( $opts['form_description'] ?? '' );
		$button = $atts['button']      ?: ( $opts['button_text']      ?? 'Subscribe' );
		$style  = sanitize_html_class( $atts['style'] );

		ob_start();
		?>
		<div class="af-subscribe-form af-style-<?php echo esc_attr( $style ); ?>">
			<?php if ( $title ) : ?>
				<h3 class="af-form-title"><?php echo esc_html( $title ); ?></h3>
			<?php endif; ?>
			<?php if ( $desc ) : ?>
				<p class="af-form-desc"><?php echo esc_html( $desc ); ?></p>
			<?php endif; ?>
			<form class="af-form">
				<div class="af-fields">
					<input
						type="text"
						name="first_name"
						class="af-input af-input-name"
						placeholder="First name"
						autocomplete="given-name"
					>
					<input
						type="email"
						name="email"
						class="af-input af-input-email"
						placeholder="Your email address"
						required
						autocomplete="email"
					>
					<button type="submit" class="af-btn"><?php echo esc_html( $button ); ?></button>
				</div>
				<div class="af-message" aria-live="polite"></div>
				<p class="af-privacy">No spam. Unsubscribe anytime.</p>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/** Automatically appends the subscribe form after single post content. */
	public static function auto_append( $content ) {
		if ( is_single() && in_the_loop() && is_main_query() ) {
			$content .= self::shortcode( array() );
		}
		return $content;
	}

	/** Handles AJAX form submission. */
	public static function ajax_subscribe() {
		check_ajax_referer( 'af_subscribe', 'nonce' );

		$email      = isset( $_POST['email'] )      ? sanitize_email( $_POST['email'] )           : '';
		$first_name = isset( $_POST['first_name'] ) ? sanitize_text_field( $_POST['first_name'] ) : '';
		$ip         = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( $_SERVER['REMOTE_ADDR'] ) : '';

		if ( ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => 'Please enter a valid email address.' ) );
		}

		$result = AF_Email_Subscribers::subscribe( $email, $first_name, $ip );

		if ( $result['success'] ) {
			$opts    = get_option( 'af_email_options', array() );
			$message = $result['message'] ?: ( $opts['success_message'] ?? "You're subscribed!" );
			wp_send_json_success( array( 'message' => $message ) );
		} else {
			wp_send_json_error( array( 'message' => $result['message'] ) );
		}
	}
}
