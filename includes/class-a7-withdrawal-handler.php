<?php
/**
 * Klasa obsługi logiki biznesowej wniosków o odstąpienie od umowy.
 *
 * @package StudioA7_Withdrawal
 */

defined( 'ABSPATH' ) || exit;

/**
 * A7_Withdrawal_Handler
 *
 * Odpowiada za:
 *  - sprawdzenie kwalifikowalności zamówienia,
 *  - przetworzenie kroku 1 (formularz),
 *  - przetworzenie kroku 2 (potwierdzenie),
 *  - generowanie tokenów,
 *  - triggering emaili.
 */
class A7_Withdrawal_Handler {

	/** @var A7_Withdrawal_DB */
	private A7_Withdrawal_DB $db;

	public function __construct() {
		$this->db = A7_Withdrawal_DB::get_instance();
	}

	// =========================================================================
	// Kwalifikowalność zamówienia
	// =========================================================================

	/**
	 * Sprawdza czy zamówienie kwalifikuje się do odstąpienia od umowy.
	 *
	 * @param int|\WC_Order $order ID zamówienia lub obiekt WC_Order.
	 * @return true|\WP_Error True jeśli kwalifikuje, WP_Error z przyczyną w przeciwnym razie.
	 */
	public function can_withdraw( int|\WC_Order $order ): true|\WP_Error {
		if ( is_int( $order ) ) {
			$order = wc_get_order( $order );
		}

		if ( ! $order instanceof \WC_Order ) {
			return new \WP_Error( 'invalid_order', __( 'Zamówienie nie istnieje.', 'studio-a7-odstap' ) );
		}

		// 1. Status zamówienia
		$allowed_statuses = (array) get_option( 'a7w_allowed_statuses', array( 'wc-completed', 'wc-processing' ) );
		$order_status     = 'wc-' . $order->get_status();

		if ( ! in_array( $order_status, $allowed_statuses, true ) ) {
			return new \WP_Error(
				'invalid_status',
				__( 'Status zamówienia nie pozwala na złożenie oświadczenia o odstąpieniu od umowy.', 'studio-a7-odstap' )
			);
		}

		// 2. Termin 14 dni
		$withdrawal_days = (int) get_option( 'a7w_withdrawal_days', 14 );
		$order_date      = $order->get_date_created();

		if ( ! $order_date ) {
			return new \WP_Error( 'invalid_date', __( 'Nie można odczytać daty zamówienia.', 'studio-a7-odstap' ) );
		}

		$days_since_order = (int) floor( ( time() - $order_date->getTimestamp() ) / DAY_IN_SECONDS );

		if ( $days_since_order >= $withdrawal_days ) {
			return new \WP_Error(
				'expired',
				sprintf(
					/* translators: %d: liczba dni */
					__( 'Termin na odstąpienie od umowy (%d dni) minął.', 'studio-a7-odstap' ),
					$withdrawal_days
				)
			);
		}

		// 3. Brak istniejącego wniosku
		if ( $this->db->order_has_withdrawal( $order->get_id() ) ) {
			return new \WP_Error(
				'already_withdrawn',
				__( 'Dla tego zamówienia zostało już złożone oświadczenie o odstąpieniu od umowy.', 'studio-a7-odstap' )
			);
		}

		// 4. Sprawdzenie wyjątków produktów
		$exclusion_check = $this->check_product_exclusions( $order );
		if ( is_wp_error( $exclusion_check ) ) {
			return $exclusion_check;
		}

		// 5. Zamówienie należy do zalogowanego klienta (lub gościa przez token w emailu)
		$current_user_id = get_current_user_id();
		$order_user_id   = (int) $order->get_customer_id();

		if ( $current_user_id > 0 && $order_user_id > 0 && $current_user_id !== $order_user_id ) {
			return new \WP_Error( 'not_owner', __( 'Brak dostępu do tego zamówienia.', 'studio-a7-odstap' ) );
		}

		return true;
	}

	/**
	 * Sprawdza wyjątki produktów w zamówieniu.
	 *
	 * @param \WC_Order $order Zamówienie.
	 * @return true|\WP_Error
	 */
	private function check_product_exclusions( \WC_Order $order ): true|\WP_Error {
		$exclude_virtual      = 'yes' === get_option( 'a7w_exclude_virtual', 'yes' );
		$exclude_downloadable = 'yes' === get_option( 'a7w_exclude_downloadable', 'yes' );
		$excluded_categories  = (array) get_option( 'a7w_excluded_categories', array() );

		foreach ( $order->get_items() as $item ) {
			/** @var \WC_Order_Item_Product $item */
			$product = $item->get_product();
			if ( ! $product instanceof \WC_Product ) {
				continue;
			}

			// Wirtualne
			if ( $exclude_virtual && $product->is_virtual() ) {
				return new \WP_Error(
					'virtual_product',
					__( 'Zamówienie zawiera produkty wirtualne/usługi, które są wyłączone z prawa odstąpienia od umowy.', 'studio-a7-odstap' )
				);
			}

			// Do pobrania (cyfrowe)
			if ( $exclude_downloadable && $product->is_downloadable() ) {
				// Sprawdź czy klient już pobrał plik
				if ( $this->customer_downloaded_product( $order, $product ) ) {
					return new \WP_Error(
						'downloaded_product',
						__( 'Zamówienie zawiera treści cyfrowe, które zostały już pobrane. Prawo odstąpienia od umowy nie przysługuje.', 'studio-a7-odstap' )
					);
				}
			}

			// Wykluczone kategorie
			if ( ! empty( $excluded_categories ) ) {
				$product_id         = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
				$product_categories = wc_get_product_term_ids( $product_id, 'product_cat' );
				$intersection       = array_intersect( $product_categories, array_map( 'intval', $excluded_categories ) );

				if ( ! empty( $intersection ) ) {
					return new \WP_Error(
						'excluded_category',
						__( 'Zamówienie zawiera produkty z kategorii wyłączonych z prawa odstąpienia od umowy (np. towary na zamówienie lub szyte na miarę).', 'studio-a7-odstap' )
					);
				}
			}
		}

		return true;
	}

	/**
	 * Sprawdza czy klient pobrał produkt cyfrowy z danego zamówienia.
	 *
	 * @param \WC_Order   $order   Zamówienie.
	 * @param \WC_Product $product Produkt.
	 * @return bool
	 */
	private function customer_downloaded_product( \WC_Order $order, \WC_Product $product ): bool {
		global $wpdb;

		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions
			 WHERE order_id = %d AND product_id = %d AND download_count > 0",
				$order->get_id(),
				$product->get_id()
			)
		);

		return (int) $count > 0;
	}

	// =========================================================================
	// Krok 1 – Złożenie oświadczenia (formularz)
	// =========================================================================

	/**
	 * Przetwarza formularz z kroku 1.
	 *
	 * @param array $post_data Dane z $_POST.
	 * @return array{success: bool, message: string, token?: string, withdrawal_id?: int}
	 */
	public function process_step1( array $post_data ): array {
		// Sanityzacja
		$order_id = absint( $post_data['order_id'] ?? 0 );
		$reason   = sanitize_textarea_field( $post_data['reason'] ?? '' );
		$nonce    = $post_data['_wpnonce'] ?? '';

		// Weryfikacja nonce
		if ( ! wp_verify_nonce( $nonce, 'a7w_step1_' . $order_id ) ) {
			return array(
				'success' => false,
				'message' => __( 'Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'studio-a7-odstap' ),
			);
		}

		// Pobranie zamówienia
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return array(
				'success' => false,
				'message' => __( 'Nie znaleziono zamówienia.', 'studio-a7-odstap' ),
			);
		}

		// Sprawdzenie kwalifikowalności
		$can = $this->can_withdraw( $order );
		if ( is_wp_error( $can ) ) {
			return array(
				'success' => false,
				'message' => $can->get_error_message(),
			);
		}

		// Wygeneruj unikalny token
		$token = $this->generate_token( $order_id );

		// Zapis do bazy (status: pending – czeka na potwierdzenie w kroku 2)
		$withdrawal_id = $this->db->insert(
			array(
				'order_id'       => $order_id,
				'customer_id'    => (int) $order->get_customer_id(),
				'customer_email' => $order->get_billing_email(),
				'customer_name'  => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
				'reason'         => $reason,
				'status'         => 'pending',
				'token'          => $token,
				'ip_address'     => $this->get_client_ip(),
			'user_agent'     => substr( $_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500 ), // phpcs:ignore
			'created_at'         => current_time( 'mysql' ),
			)
		);

		if ( ! $withdrawal_id ) {
			return array(
				'success' => false,
				'message' => __( 'Wystąpił błąd podczas zapisywania wniosku. Spróbuj ponownie.', 'studio-a7-odstap' ),
			);
		}

		return array(
			'success'       => true,
			'message'       => __( 'Wniosek przygotowany. Potwierdź odstąpienie w następnym kroku.', 'studio-a7-odstap' ),
			'token'         => $token,
			'withdrawal_id' => $withdrawal_id,
		);
	}

	// =========================================================================
	// Krok 2 – Potwierdzenie oświadczenia
	// =========================================================================

	/**
	 * Przetwarza potwierdzenie z kroku 2.
	 *
	 * @param array $post_data Dane z $_POST.
	 * @return array{success: bool, message: string}
	 */
	public function process_step2( array $post_data ): array {
		$token = sanitize_text_field( $post_data['token'] ?? '' );
		$nonce = $post_data['_wpnonce'] ?? '';

		// Weryfikacja nonce
		if ( ! wp_verify_nonce( $nonce, 'a7w_step2_' . $token ) ) {
			return array(
				'success' => false,
				'message' => __( 'Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'studio-a7-odstap' ),
			);
		}

		if ( empty( $token ) ) {
			return array(
				'success' => false,
				'message' => __( 'Nieprawidłowy token potwierdzający.', 'studio-a7-odstap' ),
			);
		}

		// Pobierz wniosek po tokenie
		$withdrawal = $this->db->get_by_token( $token );

		if ( ! $withdrawal ) {
			return array(
				'success' => false,
				'message' => __( 'Nie znaleziono wniosku. Możliwe że sesja wygasła.', 'studio-a7-odstap' ),
			);
		}

		if ( 'pending' !== $withdrawal->status ) {
			return array(
				'success' => false,
				'message' => __( 'Ten wniosek został już przetworzony.', 'studio-a7-odstap' ),
			);
		}

		// Sprawdź ponownie kwalifikowalność (zabezpieczenie)
		$can = $this->can_withdraw( (int) $withdrawal->order_id );
		if ( is_wp_error( $can ) ) {
			return array(
				'success' => false,
				'message' => $can->get_error_message(),
			);
		}

		// Finalizuj wniosek
		$confirmed = $this->db->confirm( (int) $withdrawal->id, 'confirmed' );

		if ( ! $confirmed ) {
			return array(
				'success' => false,
				'message' => __( 'Wystąpił błąd podczas potwierdzania wniosku. Spróbuj ponownie.', 'studio-a7-odstap' ),
			);
		}

		// Pobierz zaktualizowany rekord
		$withdrawal = $this->db->get( (int) $withdrawal->id );
		$order      = wc_get_order( (int) $withdrawal->order_id );

		// Dodaj notatkę do zamówienia
		if ( $order ) {
			$order->add_order_note(
				sprintf(
					/* translators: 1: data, 2: czas, 3: IP */
					__( 'Klient złożył oświadczenie o odstąpieniu od umowy w dniu %1$s o godz. %2$s (IP: %3$s).', 'studio-a7-odstap' ),
					wp_date( get_option( 'date_format' ), strtotime( $withdrawal->confirmed_at ) ),
					wp_date( get_option( 'time_format' ), strtotime( $withdrawal->confirmed_at ) ),
					esc_html( $withdrawal->ip_address )
				),
				false,
				true
			);

			// Zmień status zamówienia jeśli ustawione
			$new_status = get_option( 'a7w_order_status_after_withdrawal', '' );
			if ( ! empty( $new_status ) ) {
				$order->update_status(
					$new_status,
					__( 'Automatyczna zmiana po odstąpieniu od umowy.', 'studio-a7-odstap' )
				);
			}
		}

		// Wyślij emaile
		do_action( 'a7w_withdrawal_confirmed', $withdrawal, $order );

		return array(
			'success' => true,
			'message' => __( 'Oświadczenie o odstąpieniu od umowy zostało złożone. Potwierdzenie zostało wysłane na Twój adres e-mail.', 'studio-a7-odstap' ),
		);
	}

	// =========================================================================
	// Pomocnicze
	// =========================================================================

	/**
	 * Oblicza liczbę dni pozostałych na odstąpienie.
	 *
	 * @param \WC_Order $order Zamówienie.
	 * @return int Liczba pozostałych dni (ujemna jeśli minął termin).
	 */
	public function get_days_remaining( \WC_Order $order ): int {
		$withdrawal_days = (int) get_option( 'a7w_withdrawal_days', 14 );
		$order_date      = $order->get_date_created();

		if ( ! $order_date ) {
			return 0;
		}

		$days_since = (int) floor( ( time() - $order_date->getTimestamp() ) / DAY_IN_SECONDS );
		return $withdrawal_days - $days_since;
	}

	/**
	 * Generuje unikalny token kryptograficzny.
	 *
	 * @param int $order_id ID zamówienia (używany jako sól).
	 * @return string Token 64-znakowy.
	 */
	private function generate_token( int $order_id ): string {
		return hash( 'sha256', wp_generate_password( 32, true, true ) . $order_id . microtime() );
	}

	/**
	 * Pobiera IP klienta (z obsługą proxy).
	 *
	 * @return string Adres IP.
	 */
	private function get_client_ip(): string {
		$ip_keys = array(
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'REMOTE_ADDR',
		);

		foreach ( $ip_keys as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) { // phpcs:ignore
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) ); // phpcs:ignore
				// Weź pierwszy IP z listy (w przypadku X-Forwarded-For)
				$ip = explode( ',', $ip )[0];
				$ip = trim( $ip );
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return 'unknown';
	}
}
