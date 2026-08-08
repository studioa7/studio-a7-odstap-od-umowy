<?php
/**
 * Klasa email do klienta – potwierdzenie odstąpienia od umowy.
 *
 * @package StudioA7_Withdrawal
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Email' ) ) {
	return;
}

/**
 * A7_Email_Customer_Withdrawal
 *
 * Wysyła klientowi potwierdzenie złożonego oświadczenia o odstąpieniu od umowy
 * na trwałym nośniku (email), zgodnie z wymogami Dyrektywy UE 2023/2673.
 *
 * Wymagane elementy (art. dyrektywy):
 *  - treść oświadczenia (zamówienie, data, godzina),
 *  - data i godzina złożenia oświadczenia,
 *  - informacja o dalszym trybie postępowania.
 */
class A7_Email_Customer_Withdrawal extends WC_Email {

	/** @var object|null Rekord wniosku o odstąpienie */
	public ?object $withdrawal = null;

	public function __construct() {
		$this->id             = 'a7w_customer_withdrawal';
		$this->customer_email = true;
		$this->title          = __( 'Studio A7 – Potwierdzenie odstąpienia od umowy (klient)', 'studio-a7-odstap' );
		$this->description    = __( 'Email wysyłany do klienta natychmiast po potwierdzeniu oświadczenia o odstąpieniu od umowy.', 'studio-a7-odstap' );

		$this->template_html  = 'emails/views/email-customer-withdrawal.php';
		$this->template_plain = '';
		$this->template_base  = A7W_PLUGIN_DIR;

		$this->subject = $this->get_option(
			'subject',
			__( 'Potwierdzenie odstąpienia od umowy – zamówienie nr {order_number}', 'studio-a7-odstap' )
		);
		$this->heading = $this->get_option(
			'heading',
			__( 'Odstąpienie od umowy zostało przyjęte', 'studio-a7-odstap' )
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
		$this->recipient  = $withdrawal->customer_email;

		if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
			$this->restore_locale();
			return;
		}

		// Podmiana placeholderów w temacie
		if ( $order ) {
			$this->placeholders['{order_number}'] = $order->get_order_number();
			$this->placeholders['{order_date}']   = wc_format_datetime( $order->get_date_created() );
		}

		$this->send(
			$this->get_recipient(),
			$this->get_subject(),
			$this->get_content(),
			$this->get_headers(),
			$this->get_attachments()
		);

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
	 * Zwraca treść plain-text emaila (fallback).
	 *
	 * @return string
	 */
	public function get_content_plain(): string {
		if ( ! $this->withdrawal ) {
			return '';
		}

		$confirmed_at = $this->withdrawal->confirmed_at;
		$order_id     = $this->withdrawal->order_id;

		return sprintf(
			/* translators: 1: imię i nazwisko, 2: numer zamówienia, 3: data potwierdzenia, 4: godzina */
			__(
				"Szanowna/y %1\$s,\n\nNiniejszym potwierdzamy przyjęcie Pani/Pana oświadczenia o odstąpieniu od umowy sprzedaży dotyczącej zamówienia nr %2\$s.\n\nData i godzina złożenia oświadczenia: %3\$s godz. %4\$s\n\nZwrot środków nastąpi w ciągu 14 dni od daty otrzymania zwróconego towaru. Prosimy o odesłanie towaru na adres sklepu.\n\nZ poważaniem,\n%5\$s",
				'studio-a7-odstap'
			),
			esc_html( $this->withdrawal->customer_name ),
			esc_html( $order_id ),
			wp_date( get_option( 'date_format' ), strtotime( $confirmed_at ) ),
			wp_date( get_option( 'time_format' ), strtotime( $confirmed_at ) ),
			esc_html( get_bloginfo( 'name' ) )
		);
	}

	/**
	 * Pola formularza ustawień emaila w panelu WC.
	 *
	 * @return array
	 */
	public function init_form_fields(): void {
		$this->form_fields = array(
			'enabled'            => array(
				'title'   => __( 'Włącz/wyłącz', 'studio-a7-odstap' ),
				'type'    => 'checkbox',
				'label'   => __( 'Włącz ten email', 'studio-a7-odstap' ),
				'default' => 'yes',
			),
			'subject'            => array(
				'title'       => __( 'Temat', 'studio-a7-odstap' ),
				'type'        => 'text',
				'default'     => $this->get_default_subject(),
				'description' => __( 'Dostępne tagi: {order_number}, {order_date}', 'studio-a7-odstap' ),
				'desc_tip'    => true,
			),
			'heading'            => array(
				'title'   => __( 'Nagłówek emaila', 'studio-a7-odstap' ),
				'type'    => 'text',
				'default' => $this->get_default_heading(),
			),
			'additional_content' => array(
				'title'       => __( 'Dodatkowa treść', 'studio-a7-odstap' ),
				'type'        => 'textarea',
				'default'     => '',
				'description' => __( 'Opcjonalna treść wyświetlana na końcu emaila (np. dane do zwrotu towaru, adres sklepu).', 'studio-a7-odstap' ),
				'desc_tip'    => true,
			),
		);
	}
}
