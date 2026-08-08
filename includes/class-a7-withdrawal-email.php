<?php
/**
 * Rejestracja klas email WooCommerce.
 *
 * @package StudioA7_Withdrawal
 */

defined( 'ABSPATH' ) || exit;

/**
 * A7_Withdrawal_Email
 *
 * Rejestruje własne klasy emaili w systemie WooCommerce
 * i podpina trigger `a7w_withdrawal_confirmed`.
 */
class A7_Withdrawal_Email {

	public function __construct() {
		add_filter( 'woocommerce_email_classes', array( $this, 'register_email_classes' ) );
		add_action( 'a7w_withdrawal_confirmed', array( $this, 'trigger_emails' ), 10, 2 );
	}

	/**
	 * Rejestruje klasy emaili w WooCommerce.
	 *
	 * @param array $email_classes Istniejące klasy emaili WC.
	 * @return array
	 */
	public function register_email_classes( array $email_classes ): array {
		require_once A7W_PLUGIN_DIR . 'emails/class-a7-email-customer-withdrawal.php';
		require_once A7W_PLUGIN_DIR . 'emails/class-a7-email-admin-notification.php';

		$email_classes['A7_Email_Customer_Withdrawal'] = new A7_Email_Customer_Withdrawal();
		$email_classes['A7_Email_Admin_Notification']  = new A7_Email_Admin_Notification();

		return $email_classes;
	}

	/**
	 * Wywołuje wysyłkę emaili po potwierdzeniu odstąpienia.
	 *
	 * @param object         $withdrawal Rekord wniosku z bazy.
	 * @param \WC_Order|null $order      Obiekt zamówienia.
	 */
	public function trigger_emails( object $withdrawal, ?\WC_Order $order ): void {
		$mailer = WC()->mailer();
		$emails = $mailer->get_emails();

		// Email do klienta
		if ( ! empty( $emails['A7_Email_Customer_Withdrawal'] ) ) {
			$emails['A7_Email_Customer_Withdrawal']->trigger( $withdrawal, $order );
		}

		// Email do administratora
		if ( 'yes' === get_option( 'a7w_notify_admin', 'yes' )
			&& ! empty( $emails['A7_Email_Admin_Notification'] ) ) {
			$emails['A7_Email_Admin_Notification']->trigger( $withdrawal, $order );
		}
	}
}

new A7_Withdrawal_Email();
