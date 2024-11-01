<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Woo_Bulk_Email
 * @subpackage Woo_Bulk_Email/admin
 * @author     Jaydeep Rami <jaydeep.ramii@gmail.com>
 */
class Woo_Bulk_Email_Admin {
	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		// Add new Bulk actions.
		add_filter( 'bulk_actions-edit-shop_order', array( $this, 'wbe_bulk_email' ), 10, 1 );

		// Handle Bulk action.
		add_filter( 'handle_bulk_actions-edit-shop_order', array( $this, 'wbe_handle_bulk_action' ), 10, 3 );

		// Show Confirmation message.
		add_action( 'admin_notices', array( $this, 'wbe_bulk_admin_notices' ) );

	}

	/**
	 * Send Bulk Email.
	 *
	 * @since 1.0.0
	 *
	 * @param array $actions Bulk actions.
	 *
	 * @return array $actions Bulk actions.
	 */
	public function wbe_bulk_email( $actions ) {
		$actions['send_order_details'] = __( 'Email invoice / order details to customer', 'woo-bulk-email' );

		return $actions;
	}

	/**
	 * Handle shop order bulk actions.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $redirect_to URL to redirect to.
	 * @param  string $action      Action name.
	 * @param  array  $ids         List of ids.
	 *
	 * @return string
	 */
	public function wbe_handle_bulk_action( $redirect_to, $action, $ids ) {

		// Bail out if this is not a status-changing action.
		if ( false === strpos( $action, 'send_order_details' ) ) {
			return $redirect_to;
		}

		$changed = 0;
		$ids     = array_map( 'absint', $ids );

		foreach ( $ids as $id ) {
			$order = wc_get_order( $id );

			do_action( 'woocommerce_before_resend_order_emails', $order, 'customer_invoice' );

			// Send the customer invoice email.
			WC()->payment_gateways();
			WC()->shipping();
			WC()->mailer()->customer_invoice( $order );

			// Note the event.
			$order->add_order_note( __( 'Order details manually sent to customer.', 'woo-bulk-email' ), false, true );

			do_action( 'woocommerce_after_resend_order_email', $order, 'customer_invoice' );
			$changed ++;
		}
		$redirect_to = add_query_arg( array(
			'post_type'  => 'shop_order',
			'send'       => $changed,
			'msg_status' => 1,
			'ids'        => join( ',', $ids ),
		), $redirect_to );

		return esc_url_raw( $redirect_to );

	}


	/**
	 * Show Confirmation message.
	 *
	 * @since 1.0.0
	 */
	public function wbe_bulk_admin_notices() {
		global $post_type, $pagenow;

		// Bail out if not on shop order list page.
		if ( 'edit.php' !== $pagenow || 'shop_order' !== $post_type ) {
			return;
		}

		// Message status.
		$message_status = isset( $_GET['msg_status'] ) ? absint( $_GET['msg_status'] ) : '';

		// Bail out, if Message status not set.
		if ( empty( $message_status ) ) {
			return;
		}

		// Number of order send.
		$number = isset( $_GET['send'] ) ? absint( $_GET['send'] ) : 0;

		// Bulk email notification sent to the Customers.
		$message = '';
		if ( 1 === $message_status ) {
			$message = sprintf( _n( 'Confirmation email sent to the customer.', 'Confirmation email sent to the %s customers.', $number, 'woo-bulk-email' ), number_format_i18n( $number ) );
		}

		echo '<div class="updated"><p>' . apply_filters( 'wbe_confirmation_message', $message ) . '</p></div>';
	}

}
