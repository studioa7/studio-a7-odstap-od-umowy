<?php
/**
 * Klasa email do administratora – powiadomienie o nowym odstąpieniu.
 *
 * @package StudioA7_Withdrawal
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Email' ) ) {
	return;
}

/**
 * A7_Email_Admin_Notification
 *
 * Wysyła właścicielowi sklepu powiadomienie o nowym wniosku odstąpienia od umowy.
 */
class A7_Email_Admin_Notification extends WC_Email {


	/** @var object|null Rekord wniosku */
	public ?object $withdrawal = null;

	public function __construct() {
		$this->id          = 'a7w_admin_notification';
		$this->title       = __( 'Studio A7 – Powiadomienie o odstąpieniu (admin)', 'studio-a7-odstap' );
		$this->description = __( 'Email wysyłany do administratora sklepu po złożeniu oświadczenia o odstąpieniu od umowy.', 'studio-a7-odstap' );

		$this->template_html  = 'emails/views/email-admin-notification.php';
		$this->template_plain = '';
		$this->template_base  = A7W_PLUGIN_DIR;

		$this->subject = $this->get_option(
			'subject',
			__( '[{site_title}] Nowe odstąpienie od umowy – zamówienie #{order_number}', 'studio-a7-odstap' )
		);
		$this->heading = $this->get_option(
			'heading',
			__( 'Klient złożył oświadczenie o odstąpieniu od umowy', 'studio-a7-odstap' )
		);

		parent::__construct();
	}

	/**
	 * Wywołuje wysyłkę emaila.
	 *
	 * @param object         $withdrawal Rekord wniosku.
	 * @param \WC_Order|null $order      Obiekt zamówienia.
	 */
	public function trigger( object $withdrawal, ?\WC_Order $order ): void {
		$this->setup_locale();

		$this->withdrawal = $withdrawal;
		$this->object     = $order;

		// Odbiorca – email z ustawień lub domyślny admin
		$admin_email     = get_option( 'a7w_admin_email', get_option( 'admin_email' ) );
		$recipients      = array_filter(
			array_map( 'sanitize_email', array_map( 'trim', explode( ',', (string) $admin_email ) ) )
		);
		$this->recipient = implode( ',', array_unique( $recipients ) );

		if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
			$this->restore_locale();
			return;
		}

		if ( $order ) {
			$this->placeholders['{order_number}'] = $order->get_order_number();
		}

		$sent = $this->send(
			$this->get_recipient(),
			$this->get_subject(),
			$this->get_content(),
			$this->get_headers(),
			$this->get_attachments()
		);

		if ( ! $sent && $order ) {
			$order->add_order_note(
				__( 'Nie udało się wysłać powiadomienia e-mail o odstąpieniu do obsługi sklepu.', 'studio-a7-odstap' )
			);
		}

		$this->restore_locale();
	}

	/**
	 * Zwraca treść HTML emaila.
	 *
	 * @return string
	 */
	public function get_content_html(): string {
		return wc_get_template_html(
			$this->template_html,
			array(
				'withdrawal'    => $this->withdrawal,
				'order'         => $this->object,
				'email_heading' => $this->get_heading(),
				'email'         => $this,
			),
			'',
			$this->template_base
		);
	}

	/**
	 * Zwraca treść plain-text emaila.
	 *
	 * @return string
	 */
	public function get_content_plain(): string {
		if ( ! $this->withdrawal ) {
			return '';
		}

		return sprintf(
			__(
				"Klient złożył oświadczenie o odstąpieniu od umowy.\n\nZamówienie: #%1\$s\nKlient: %2\$s <%3\$s>\nData i godzina: %4\$s %5\$s\nPowód: %6\$s\n\nPrzejdź do panelu: %7\$s",
				'studio-a7-odstap'
			),
			$this->withdrawal->order_id,
			$this->withdrawal->customer_name,
			$this->withdrawal->customer_email,
			wp_date( get_option( 'date_format' ), strtotime( $this->withdrawal->confirmed_at ) ),
			wp_date( get_option( 'time_format' ), strtotime( $this->withdrawal->confirmed_at ) ),
			! empty( $this->withdrawal->reason ) ? $this->withdrawal->reason : __( 'Brak podanego powodu', 'studio-a7-odstap' ),
			admin_url( 'admin.php?page=a7w-requests' )
		);
	}

	/**
	 * Pola formularza ustawień emaila.
	 */
	public function init_form_fields(): void {
		$this->form_fields = array(
			'enabled'   => array(
				'title'   => __( 'Włącz/wyłącz', 'studio-a7-odstap' ),
				'type'    => 'checkbox',
				'label'   => __( 'Włącz powiadomienia dla administratora', 'studio-a7-odstap' ),
				'default' => 'yes',
			),
			'recipient' => array(
				'title'       => __( 'Adres email odbiorcy', 'studio-a7-odstap' ),
				'type'        => 'text',
				'default'     => get_option( 'admin_email' ),
				'description' => __( 'Adres email administratora/obsługi klienta. Można wpisać wiele adresów rozdzielonych przecinkiem.', 'studio-a7-odstap' ),
				'desc_tip'    => true,
			),
			'subject'   => array(
				'title'   => __( 'Temat', 'studio-a7-odstap' ),
				'type'    => 'text',
				'default' => $this->get_default_subject(),
			),
			'heading'   => array(
				'title'   => __( 'Nagłówek emaila', 'studio-a7-odstap' ),
				'type'    => 'text',
				'default' => $this->get_default_heading(),
			),
		);
	}
}
