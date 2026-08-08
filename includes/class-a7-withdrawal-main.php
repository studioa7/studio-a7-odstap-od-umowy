<?php
/**
 * Klasa główna wtyczki – hooki, AJAX, rejestracja assetów.
 *
 * @package StudioA7_Withdrawal
 */

defined( 'ABSPATH' ) || exit;

/**
 * A7_Withdrawal_Main
 *
 * Odpowiada za:
 *  - wyświetlanie przycisku w panelu klienta (lista zamówień + strona szczegółów),
 *  - obsługę żądań AJAX (krok 1 i krok 2),
 *  - rejestrację stylów i skryptów frontendu.
 */
class A7_Withdrawal_Main {


	/** @var A7_Withdrawal_Main|null */
	private static ?A7_Withdrawal_Main $instance = null;

	/** @var A7_Withdrawal_Handler */
	private A7_Withdrawal_Handler $handler;

	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->handler = new A7_Withdrawal_Handler();
		$this->init_hooks();
	}

	/**
	 * Rejestracja wszystkich hooków.
	 */
	private function init_hooks(): void {
		// Style i skrypty
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// Przycisk w tabeli listy zamówień (Moje konto → Zamówienia)
		add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'add_withdrawal_action_to_list' ), 10, 2 );

		// Przycisk na stronie szczegółów zamówienia
		add_action( 'woocommerce_view_order', array( $this, 'render_withdrawal_section' ), 20 );

		// AJAX – krok 1 (formularz)
		add_action( 'wp_ajax_a7w_step1', array( $this, 'ajax_step1' ) );

		// AJAX – krok 2 (potwierdzenie)
		add_action( 'wp_ajax_a7w_step2', array( $this, 'ajax_step2' ) );

		// Cleanup starych wniosków (cron)
		add_action( 'a7w_cleanup_pending', array( $this, 'cleanup_pending_withdrawals' ) );
		if ( ! wp_next_scheduled( 'a7w_cleanup_pending' ) ) {
			wp_schedule_event( time(), 'daily', 'a7w_cleanup_pending' );
		}
	}

	// =========================================================================
	// Rejestracja assetów
	// =========================================================================

	public function enqueue_assets(): void {
		if ( ! is_account_page() && ! is_wc_endpoint_url( 'view-order' ) ) {
			return;
		}

		wp_enqueue_style(
			'a7w-public',
			A7W_PLUGIN_URL . 'public/css/public.css',
			array(),
			A7W_VERSION
		);

		wp_enqueue_script(
			'a7w-public',
			A7W_PLUGIN_URL . 'public/js/public.js',
			array( 'jquery' ),
			A7W_VERSION,
			true
		);

		wp_localize_script(
			'a7w-public',
			'a7wData',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'i18n'    => array(
					'loading'       => __( 'Proszę czekać…', 'studio-a7-odstap' ),
					'error_generic' => __( 'Wystąpił błąd. Spróbuj ponownie.', 'studio-a7-odstap' ),
					'confirm_step2' => __( 'Czy na pewno chcesz odstąpić od umowy? Tej operacji nie można cofnąć.', 'studio-a7-odstap' ),
				),
			)
		);
	}

	// =========================================================================
	// Lista zamówień – dodanie akcji
	// =========================================================================

	/**
	 * Dodaje przycisk „Odstąp od umowy" do tabeli z listą zamówień.
	 *
	 * @param array     $actions Istniejące akcje.
	 * @param \WC_Order $order   Obiekt zamówienia.
	 * @return array
	 */
	public function add_withdrawal_action_to_list( array $actions, \WC_Order $order ): array {
		$can = $this->handler->can_withdraw( $order );

		if ( true !== $can ) {
			return $actions;
		}

		$actions['a7w_withdraw'] = array(
			'url'   => '#a7w-modal-' . $order->get_id(),
			'name'  => esc_html( get_option( 'a7w_button_label', __( 'Odstąp od umowy', 'studio-a7-odstap' ) ) ),
			'class' => 'a7w-open-modal button',
			'attrs' => array(
				'data-order-id' => $order->get_id(),
			),
		);

		// Dołącz modal HTML do strony (jeden raz na zamówienie)
		add_action(
			'wp_footer',
			function () use ( $order ) {
				$this->render_modal( $order );
			}
		);

		return $actions;
	}

	// =========================================================================
	// Strona szczegółów zamówienia
	// =========================================================================

	/**
	 * Renderuje sekcję odstąpienia na stronie szczegółów zamówienia.
	 *
	 * @param int $order_id ID zamówienia.
	 */
	public function render_withdrawal_section( int $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		// Sprawdź czy można odstąpić
		$can = $this->handler->can_withdraw( $order );

		// Załaduj widok
		$this->load_view(
			'button.php',
			array(
				'order'          => $order,
				'can_withdraw'   => $can,
				'days_remaining' => true === $can ? $this->handler->get_days_remaining( $order ) : 0,
				'handler'        => $this->handler,
			)
		);

		if ( true === $can ) {
			$this->render_modal( $order );
		}
	}

	/**
	 * Renderuje modal z formularzem odstąpienia (krok 1 i krok 2).
	 *
	 * @param \WC_Order $order Zamówienie.
	 */
	private function render_modal( \WC_Order $order ): void {
		$this->load_view(
			'modal.php',
			array(
				'order'   => $order,
				'handler' => $this->handler,
				'nonce1'  => wp_create_nonce( 'a7w_step1_' . $order->get_id() ),
			)
		);
	}

	// =========================================================================
	// AJAX handlers
	// =========================================================================

	/**
	 * AJAX: Przetworzenie kroku 1 (formularz).
	 */
	public function ajax_step1(): void {
		$result = $this->handler->process_step1($_POST); // phpcs:ignore
		wp_send_json( $result );
	}

	/**
	 * AJAX: Przetworzenie kroku 2 (potwierdzenie).
	 */
	public function ajax_step2(): void {
		$result = $this->handler->process_step2($_POST); // phpcs:ignore
		wp_send_json( $result );
	}

	// =========================================================================
	// Cron – sprzątanie wygasłych wniosków
	// =========================================================================

	public function cleanup_pending_withdrawals(): void {
		A7_Withdrawal_DB::get_instance()->cleanup_expired_pending( 24 );
		A7_Withdrawal_DB::get_instance()->cleanup_expired_confirmed(
			max( 1, absint( get_option( 'a7w_retention_months', 24 ) ) )
		);
	}

	// =========================================================================
	// Pomocnicze
	// =========================================================================

	/**
	 * Ładuje plik widoku z przekazanymi zmiennymi.
	 *
	 * @param string $view_file Nazwa pliku widoku (relative do public/views/).
	 * @param array  $vars      Zmienne dostępne w widoku.
	 */
	private function load_view( string $view_file, array $vars = array() ): void {
		extract($vars, EXTR_SKIP); // phpcs:ignore
		$path = A7W_PLUGIN_DIR . 'public/views/' . $view_file;
		if ( file_exists( $path ) ) {
			include $path;
		}
	}
}
