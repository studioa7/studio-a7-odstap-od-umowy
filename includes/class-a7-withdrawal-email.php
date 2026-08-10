<?php
/**
 * Rejestracja klas email WooCommerce.
 *
 * @package StudioA7_Withdrawal
 */

defined('ABSPATH') || exit;

/**
 * A7_Withdrawal_Email
 *
 * Rejestruje własne klasy emaili w systemie WooCommerce
 * i podpina trigger `a7w_withdrawal_confirmed`.
 */
class A7_Withdrawal_Email
{

	public function __construct()
	{
		add_filter('woocommerce_email_classes', array($this, 'register_email_classes'));
		add_action('a7w_withdrawal_confirmed', array($this, 'trigger_emails'), 10, 2);
		add_action('a7w_withdrawal_decided', array($this, 'trigger_lifecycle_email'), 10, 3);
		add_action('a7w_withdrawal_cancelled', array($this, 'trigger_cancelled_email'), 10, 2);
		add_action('a7w_withdrawal_shipping_updated', array($this, 'trigger_shipping_email'), 10, 2);
	}

	/**
	 * Rejestruje klasy emaili w WooCommerce.
	 *
	 * @param array $email_classes Istniejące klasy emaili WC.
	 * @return array
	 */
	public function register_email_classes(array $email_classes): array
	{
		require_once A7W_PLUGIN_DIR . 'emails/class-a7-email-customer-withdrawal.php';
		require_once A7W_PLUGIN_DIR . 'emails/class-a7-email-admin-notification.php';

		$email_classes['A7_Email_Customer_Withdrawal'] = new A7_Email_Customer_Withdrawal();
		$email_classes['A7_Email_Admin_Notification'] = new A7_Email_Admin_Notification();

		return $email_classes;
	}

	/**
	 * Wywołuje wysyłkę emaili po potwierdzeniu odstąpienia.
	 *
	 * @param object         $withdrawal Rekord wniosku z bazy.
	 * @param \WC_Order|null $order      Obiekt zamówienia.
	 */
	public function trigger_emails(object $withdrawal, ?\WC_Order $order): void
	{
		$mailer = WC()->mailer();
		$emails = $mailer->get_emails();

		// Email do klienta
		if (!empty($emails['A7_Email_Customer_Withdrawal'])) {
			$emails['A7_Email_Customer_Withdrawal']->trigger($withdrawal, $order);
		}

		// Email do administratora
		if (
			'yes' === get_option('a7w_notify_admin', 'yes')
			&& !empty($emails['A7_Email_Admin_Notification'])
		) {
			$emails['A7_Email_Admin_Notification']->trigger($withdrawal, $order);
		}
	}

	/** Sends a minimal transactional notice without exposing audit or guest secrets. */
	public function trigger_lifecycle_email(?object $withdrawal, ?\WC_Order $order, string $status): void
	{
		if (!$withdrawal || !is_email($withdrawal->customer_email)) {
			return;
		}
		$subject = 'approved' === $status
			? __('Decyzja w sprawie odstąpienia od umowy', 'studio-a7-odstap')
			: __('Informacja o wniosku o odstąpienie', 'studio-a7-odstap');
		$message = sprintf(
			/* translators: 1: order number, 2: decision, 3: optional note. */
			__('Wniosek dotyczący zamówienia nr %1$s został %2$s.%3$s', 'studio-a7-odstap'),
			$order ? $order->get_order_number() : $withdrawal->order_id,
			'approved' === $status ? __('zaakceptowany', 'studio-a7-odstap') : __('odrzucony', 'studio-a7-odstap'),
			empty($withdrawal->admin_note) ? '' : ' ' . sanitize_textarea_field($withdrawal->admin_note)
		);
		$this->send_lifecycle_message($withdrawal->customer_email, $subject, $message);
	}

	public function trigger_cancelled_email(object $withdrawal, ?\WC_Order $order): void
	{
		$this->send_lifecycle_message(
			$withdrawal->customer_email,
			__('Wniosek o odstąpienie został anulowany', 'studio-a7-odstap'),
			sprintf(__('Wniosek dotyczący zamówienia nr %s został anulowany.', 'studio-a7-odstap'), $order ? $order->get_order_number() : $withdrawal->order_id)
		);
	}

	public function trigger_shipping_email(object $withdrawal, ?\WC_Order $order): void
	{
		$this->send_lifecycle_message(
			$withdrawal->customer_email,
			__('Dane przesyłki zwrotnej zostały zapisane', 'studio-a7-odstap'),
			sprintf(__('Dane przesyłki zwrotnej dla zamówienia nr %s zostały zapisane.', 'studio-a7-odstap'), $order ? $order->get_order_number() : $withdrawal->order_id)
		);
	}

	private function send_lifecycle_message(string $recipient, string $subject, string $message): void
	{
		if (!is_email($recipient) || !function_exists('wc_mail')) {
			return;
		}
		wc_mail($recipient, $subject, WC()->mailer()->wrap_message($subject, wpautop(esc_html($message))));
	}
}

new A7_Withdrawal_Email();
