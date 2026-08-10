<?php
/**
 * Klasa główna wtyczki – hooki, AJAX, rejestracja assetów.
 *
 * @package StudioA7_Withdrawal
 */

defined('ABSPATH') || exit;

/**
 * A7_Withdrawal_Main
 *
 * Odpowiada za:
 *  - wyświetlanie przycisku w panelu klienta (lista zamówień + strona szczegółów),
 *  - obsługę żądań AJAX (krok 1 i krok 2),
 *  - rejestrację stylów i skryptów frontendu.
 */
class A7_Withdrawal_Main
{


	/** @var A7_Withdrawal_Main|null */
	private static ?A7_Withdrawal_Main $instance = null;

	/** @var A7_Withdrawal_Handler */
	private A7_Withdrawal_Handler $handler;

	public static function get_instance(): self
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct()
	{
		$this->handler = new A7_Withdrawal_Handler();
		$this->init_hooks();
	}

	/**
	 * Rejestracja wszystkich hooków.
	 */
	private function init_hooks(): void
	{
		// Style i skrypty
		add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));

		// Przycisk w tabeli listy zamówień (Moje konto → Zamówienia)
		add_filter('woocommerce_my_account_my_orders_actions', array($this, 'add_withdrawal_action_to_list'), 10, 2);

		// Przycisk na stronie szczegółów zamówienia
		add_action('woocommerce_view_order', array($this, 'render_withdrawal_section'), 20);

		// AJAX – krok 1 (formularz)
		add_action('wp_ajax_a7w_step1', array($this, 'ajax_step1'));
		add_action('wp_ajax_nopriv_a7w_step1', array($this, 'ajax_step1'));

		// AJAX – krok 2 (potwierdzenie)
		add_action('wp_ajax_a7w_step2', array($this, 'ajax_step2'));
		add_action('wp_ajax_nopriv_a7w_step2', array($this, 'ajax_step2'));
		add_action('wp_ajax_a7w_cancel_withdrawal', array($this, 'ajax_cancel_withdrawal'));
		add_action('wp_ajax_nopriv_a7w_cancel_withdrawal', array($this, 'ajax_cancel_withdrawal'));
		add_action('wp_ajax_a7w_update_shipping', array($this, 'ajax_update_shipping'));
		add_action('wp_ajax_nopriv_a7w_update_shipping', array($this, 'ajax_update_shipping'));
		add_action('woocommerce_account_a7w-returns_endpoint', array($this, 'render_return_history'));
		add_filter('woocommerce_account_menu_items', array($this, 'add_return_history_endpoint'));
		add_action('init', array($this, 'register_return_history_endpoint'));

		add_shortcode('a7w_guest_withdrawal', array($this, 'render_guest_withdrawal_shortcode'));
		add_action('template_redirect', array($this, 'process_guest_lookup'), 1);

		// Cleanup starych wniosków (cron)
		add_action('a7w_cleanup_pending', array($this, 'cleanup_pending_withdrawals'));
		if (!wp_next_scheduled('a7w_cleanup_pending')) {
			wp_schedule_event(time(), 'daily', 'a7w_cleanup_pending');
		}
	}

	// =========================================================================
	// Rejestracja assetów
	// =========================================================================

	public function enqueue_assets(): void
	{
		$post_id = get_queried_object_id();
		$has_guest_shortcode = $post_id > 0 && has_shortcode((string) get_post_field('post_content', $post_id), 'a7w_guest_withdrawal');
		if (!is_account_page() && !is_wc_endpoint_url('view-order') && !$has_guest_shortcode) {
			return;
		}

		$this->enqueue_public_assets();
	}

	/**
	 * Enqueues frontend assets used by both account pages and the guest shortcode.
	 */
	private function enqueue_public_assets(): void
	{
		wp_enqueue_style(
			'a7w-public',
			A7W_PLUGIN_URL . 'public/css/public.css',
			array(),
			A7W_VERSION
		);

		wp_enqueue_script(
			'a7w-public',
			A7W_PLUGIN_URL . 'public/js/public.js',
			array('jquery'),
			A7W_VERSION,
			true
		);

		wp_localize_script(
			'a7w-public',
			'a7wData',
			array(
				'ajaxUrl' => admin_url('admin-ajax.php'),
				'i18n' => array(
					'loading' => __('Proszę czekać…', 'studio-a7-odstap'),
					'error_generic' => __('Wystąpił błąd. Spróbuj ponownie.', 'studio-a7-odstap'),
					'confirm_step2' => __('Czy na pewno chcesz odstąpić od umowy? Tej operacji nie można cofnąć.', 'studio-a7-odstap'),
				),
			)
		);
	}

	// =========================================================================
	// Publiczny shortcode dla zamówień gości
	// =========================================================================

	/**
	 * Verifies a guest lookup before page output, then sets the HttpOnly cookie.
	 *
	 * Running on template_redirect makes the Set-Cookie header reliable and the
	 * redirect removes the submitted order key from the request lifecycle.
	 */
	public function process_guest_lookup(): void
	{
		if (is_admin() || 'POST' !== ($_SERVER['REQUEST_METHOD'] ?? '') || !isset($_POST['a7w_guest_lookup'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return;
		}

		$post_id = get_queried_object_id();
		if ($post_id <= 0 || !has_shortcode((string) get_post_field('post_content', $post_id), 'a7w_guest_withdrawal')) {
			return;
		}

		$lookup = wp_unslash($_POST); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$nonce = isset($lookup['_wpnonce']) ? sanitize_text_field($lookup['_wpnonce']) : '';
		$order_number = isset($lookup['order_number']) ? sanitize_text_field($lookup['order_number']) : '';
		$email = isset($lookup['billing_email']) ? sanitize_email($lookup['billing_email']) : '';
		$order_key = isset($lookup['order_key']) ? sanitize_text_field($lookup['order_key']) : '';
		$error = 'invalid';

		if (wp_verify_nonce($nonce, 'a7w_guest_lookup') && '' !== $order_number && is_email($email) && '' !== $order_key && function_exists('wc_get_order_id_by_order_key')) {
			$order_id = absint(wc_get_order_id_by_order_key($order_key));
			$order = $order_id ? wc_get_order($order_id) : false;
			$verified = $order instanceof \WC_Order
				&& 0 === (int) $order->get_customer_id()
				&& hash_equals((string) $order->get_order_key(), $order_key)
				&& hash_equals((string) $order->get_order_number(), trim($order_number))
				&& hash_equals(strtolower((string) $order->get_billing_email()), strtolower($email));

			if ($verified && $this->handler->mint_guest_session($order->get_id())) {
				wp_safe_redirect(add_query_arg('a7w_guest', '1', remove_query_arg(array('a7w_guest', 'a7w_guest_error'))));
				exit;
			}
		}

		wp_safe_redirect(add_query_arg('a7w_guest_error', $error, remove_query_arg(array('a7w_guest', 'a7w_guest_error'))));
		exit;
	}

	/**
	 * Renders the guest withdrawal lookup form and the authorized modal.
	 *
	 * The order key is accepted only by the initial nonced form post. On a
	 * successful lookup it is never rendered or sent by either AJAX step.
	 *
	 * @return string
	 */
	public function render_guest_withdrawal_shortcode(): string
	{
		$this->enqueue_public_assets();
		$error = isset($_GET['a7w_guest_error']) ? __('Nie udało się zweryfikować danych zamówienia.', 'studio-a7-odstap') : '';
		$order_id = $this->handler->get_guest_session_order_id();
		$order = $order_id ? wc_get_order($order_id) : false;

		ob_start();
		?>
		<div class="a7w-guest-withdrawal">
			<?php if (!$order): ?>
				<form class="a7w-form" method="post" novalidate>
					<?php wp_nonce_field('a7w_guest_lookup'); ?>
					<input type="hidden" name="a7w_guest_lookup" value="1">
					<h2><?php esc_html_e('Odstąpienie od umowy', 'studio-a7-odstap'); ?></h2>
					<p><?php esc_html_e('Podaj dane z potwierdzenia zamówienia, aby przejść do formularza.', 'studio-a7-odstap'); ?>
					</p>
					<div class="a7w-form__group">
						<label class="a7w-form__label"
							for="a7w-guest-order-number"><?php esc_html_e('Numer zamówienia', 'studio-a7-odstap'); ?></label>
						<input class="a7w-form__input" id="a7w-guest-order-number" name="order_number" type="text" required
							autocomplete="off">
					</div>
					<div class="a7w-form__group">
						<label class="a7w-form__label"
							for="a7w-guest-email"><?php esc_html_e('Adres e-mail rozliczeniowy', 'studio-a7-odstap'); ?></label>
						<input class="a7w-form__input" id="a7w-guest-email" name="billing_email" type="email" required
							autocomplete="email">
					</div>
					<div class="a7w-form__group">
						<label class="a7w-form__label"
							for="a7w-guest-order-key"><?php esc_html_e('Klucz zamówienia', 'studio-a7-odstap'); ?></label>
						<input class="a7w-form__input" id="a7w-guest-order-key" name="order_key" type="password" required
							autocomplete="off">
					</div>
					<?php if ('' !== $error): ?>
						<div class="a7w-form__error" role="alert"><?php echo esc_html($error); ?></div>
					<?php endif; ?>
					<button class="a7w-btn a7w-btn--primary"
						type="submit"><?php esc_html_e('Zweryfikuj i przejdź dalej', 'studio-a7-odstap'); ?></button>
				</form>
			<?php else: ?>
				<?php
				$can = $this->handler->can_withdraw($order);
				if (true === $can) {
					?>
					<button type="button" class="a7w-btn a7w-btn--primary a7w-open-modal"
						data-order-id="<?php echo esc_attr($order->get_id()); ?>">
						<?php esc_html_e('Odstąp od umowy tutaj', 'studio-a7-odstap'); ?>
					</button>
					<?php $this->render_modal($order); ?>
					<?php
				} else {
					echo '<div class="a7w-form__error" role="alert">' . esc_html($can->get_error_message()) . '</div>';
				}
				?>
			<?php endif; ?>
		</div>
		<?php

		return (string) ob_get_clean();
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
	public function add_withdrawal_action_to_list(array $actions, \WC_Order $order): array
	{
		$can = $this->handler->can_withdraw($order);

		if (true !== $can) {
			return $actions;
		}

		$actions['a7w_withdraw'] = array(
			'url' => '#a7w-modal-' . $order->get_id(),
			'name' => esc_html(get_option('a7w_button_label', __('Odstąp od umowy tutaj', 'studio-a7-odstap'))),
			'class' => 'a7w-open-modal button',
			'attrs' => array(
				'data-order-id' => $order->get_id(),
			),
		);

		// Dołącz modal HTML do strony (jeden raz na zamówienie)
		add_action(
			'wp_footer',
			function () use ($order) {
				$this->render_modal($order);
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
	public function render_withdrawal_section(int $order_id): void
	{
		$order = wc_get_order($order_id);
		if (!$order) {
			return;
		}

		// Sprawdź czy można odstąpić
		$can = $this->handler->can_withdraw($order);

		// Załaduj widok
		$this->load_view(
			'button.php',
			array(
				'order' => $order,
				'can_withdraw' => $can,
				'days_remaining' => true === $can ? $this->handler->get_days_remaining($order) : 0,
				'handler' => $this->handler,
			)
		);

		if (true === $can) {
			$this->render_modal($order);
		}
	}

	/**
	 * Renderuje modal z formularzem odstąpienia (krok 1 i krok 2).
	 *
	 * @param \WC_Order $order Zamówienie.
	 */
	private function render_modal(\WC_Order $order): void
	{
		$this->load_view(
			'modal.php',
			array(
				'order' => $order,
				'handler' => $this->handler,
				'nonce1' => wp_create_nonce('a7w_step1_' . $order->get_id()),
			)
		);
	}

	// =========================================================================
	// AJAX handlers
	// =========================================================================

	/**
	 * AJAX: Przetworzenie kroku 1 (formularz).
	 */
	public function ajax_step1(): void
	{
		$result = $this->handler->process_step1(wp_unslash($_POST)); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		wp_send_json($result);
	}

	/**
	 * AJAX: Przetworzenie kroku 2 (potwierdzenie).
	 */
	public function ajax_step2(): void
	{
		$result = $this->handler->process_step2(wp_unslash($_POST)); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		wp_send_json($result);
	}

	/** Registers the customer return-history endpoint without touching order storage. */
	public function register_return_history_endpoint(): void
	{
		add_rewrite_endpoint('a7w-returns', EP_ROOT | EP_PAGES);
	}

	/** @param array<string, string> $items @return array<string, string> */
	public function add_return_history_endpoint(array $items): array
	{
		$items['a7w-returns'] = __('Zwroty i odstąpienia', 'studio-a7-odstap');
		return $items;
	}

	/** Renders only the authenticated account holder's request history. */
	public function render_return_history(): void
	{
		if (!is_user_logged_in()) {
			return;
		}
		$requests = A7_Withdrawal_DB::get_instance()->get_customer_requests(get_current_user_id());
		?>
		<h2><?php esc_html_e('Zwroty i odstąpienia', 'studio-a7-odstap'); ?></h2>
		<?php if (!$requests): ?>
			<p><?php esc_html_e('Nie masz jeszcze złożonych wniosków.', 'studio-a7-odstap'); ?></p><?php return; endif; ?>
		<table class="woocommerce-table woocommerce-table--order-details shop_table order_details">
			<thead>
				<tr>
					<th><?php esc_html_e('Zamówienie', 'studio-a7-odstap'); ?></th>
					<th><?php esc_html_e('Status', 'studio-a7-odstap'); ?></th>
					<th><?php esc_html_e('Data', 'studio-a7-odstap'); ?></th>
					<th><?php esc_html_e('Zwrot przesyłki', 'studio-a7-odstap'); ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($requests as $request): ?>
					<?php $shipping = json_decode((string) $request->shipping_data, true);
					$shipping = is_array($shipping) ? $shipping : array(); ?>
					<tr>
						<td>#<?php echo esc_html($request->order_id); ?></td>
						<td><?php echo esc_html($request->status); ?></td>
						<td><?php echo esc_html(wp_date(get_option('date_format'), strtotime($request->created_at))); ?></td>
						<td><?php if (in_array($request->status, array('confirmed', 'approved'), true)): ?>
								<form class="a7w-shipping-update" data-id="<?php echo esc_attr($request->id); ?>"
									data-nonce="<?php echo esc_attr(wp_create_nonce('a7w_shipping_' . $request->id)); ?>"><input
										type="text" name="return_method"
										value="<?php echo esc_attr($shipping['return_method'] ?? ''); ?>"
										placeholder="<?php esc_attr_e('Metoda', 'studio-a7-odstap'); ?>"><input type="text"
										name="tracking_number" value="<?php echo esc_attr($shipping['tracking_number'] ?? ''); ?>"
										placeholder="<?php esc_attr_e('Numer śledzenia', 'studio-a7-odstap'); ?>"><button type="submit"
										class="button"><?php esc_html_e('Zapisz', 'studio-a7-odstap'); ?></button></form>
							<?php else: ?><span class="a7w-dash">—</span><?php endif; ?>
						</td>
						<td><?php if (in_array($request->status, array('pending', 'confirmed'), true)): ?><button type="button"
									class="button a7w-cancel-withdrawal" data-id="<?php echo esc_attr($request->id); ?>"
									data-nonce="<?php echo esc_attr(wp_create_nonce('a7w_cancel_' . $request->id)); ?>"><?php esc_html_e('Anuluj wniosek', 'studio-a7-odstap'); ?></button><?php endif; ?>
						</td>
					</tr><?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/** Cancels an own request; guest cancellation is bound to the existing server-side session. */
	public function ajax_cancel_withdrawal(): void
	{
		$id = absint($_POST['withdrawal_id'] ?? 0); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$nonce = sanitize_text_field(wp_unslash($_POST['_wpnonce'] ?? '')); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if (!$id || !wp_verify_nonce($nonce, 'a7w_cancel_' . $id)) {
			wp_send_json_error(array('message' => __('Błąd bezpieczeństwa.', 'studio-a7-odstap')), 403);
		}
		$request = A7_Withdrawal_DB::get_instance()->get($id);
		$authorized = $request && ((int) $request->customer_id === get_current_user_id() || (0 === (int) $request->customer_id && $this->handler->get_guest_session_order_id() === (int) $request->order_id));
		if (!$authorized || !A7_Withdrawal_DB::get_instance()->cancel($id, get_current_user_id())) {
			wp_send_json_error(array('message' => __('Nie można anulować tego wniosku.', 'studio-a7-odstap')), 403);
		}
		do_action('a7w_withdrawal_cancelled', $request, wc_get_order((int) $request->order_id));
		wp_send_json_success(array('message' => __('Wniosek został anulowany.', 'studio-a7-odstap')));
	}

	/** Updates shipping details for the current authenticated customer only. */
	public function ajax_update_shipping(): void
	{
		$id = absint($_POST['withdrawal_id'] ?? 0); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$nonce = sanitize_text_field(wp_unslash($_POST['_wpnonce'] ?? '')); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if (!$id || !wp_verify_nonce($nonce, 'a7w_shipping_' . $id)) {
			wp_send_json_error(array('message' => __('Błąd bezpieczeństwa.', 'studio-a7-odstap')), 403);
		}
		$request = A7_Withdrawal_DB::get_instance()->get($id);
		$authorized = $request && is_user_logged_in() && (int) $request->customer_id === get_current_user_id();
		if (!$authorized) {
			wp_send_json_error(array('message' => __('Brak dostępu do tego wniosku.', 'studio-a7-odstap')), 403);
		}
		$shipping = array(
			'return_method' => sanitize_text_field(wp_unslash($_POST['return_method'] ?? '')), // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'tracking_number' => sanitize_text_field(wp_unslash($_POST['tracking_number'] ?? '')), // phpcs:ignore WordPress.Security.NonceVerification.Missing
		);
		if (!A7_Withdrawal_DB::get_instance()->update_shipping($id, $shipping, get_current_user_id())) {
			wp_send_json_error(array('message' => __('Nie można zapisać danych przesyłki.', 'studio-a7-odstap')), 400);
		}
		do_action('a7w_withdrawal_shipping_updated', $request, wc_get_order((int) $request->order_id));
		wp_send_json_success(array('message' => __('Dane przesyłki zostały zapisane.', 'studio-a7-odstap')));
	}

	// =========================================================================
	// Cron – sprzątanie wygasłych wniosków
	// =========================================================================

	public function cleanup_pending_withdrawals(): void
	{
		A7_Withdrawal_DB::get_instance()->cleanup_expired_pending(24);
		A7_Withdrawal_DB::get_instance()->cleanup_expired_confirmed(
			max(1, absint(get_option('a7w_retention_months', 24)))
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
	private function load_view(string $view_file, array $vars = array()): void
	{
		extract($vars, EXTR_SKIP); // phpcs:ignore
		$path = A7W_PLUGIN_DIR . 'public/views/' . $view_file;
		if (file_exists($path)) {
			include $path;
		}
	}
}
