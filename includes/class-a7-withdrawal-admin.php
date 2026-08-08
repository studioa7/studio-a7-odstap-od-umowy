<?php
/**
 * Panel administracyjny wtyczki.
 *
 * @package StudioA7_Withdrawal
 */

defined('ABSPATH') || exit;

/**
 * A7_Withdrawal_Admin
 *
 * Odpowiada za:
 *  - stronę listy zgłoszeń (WP_List_Table),
 *  - stronę ustawień wtyczki,
 *  - eksport CSV,
 *  - kolumnę w panelu zamówień WooCommerce.
 */
class A7_Withdrawal_Admin
{


	/** @var A7_Withdrawal_Admin|null */
	private static ?A7_Withdrawal_Admin $instance = null;

	/** @var A7_Withdrawal_DB */
	private A7_Withdrawal_DB $db;

	public static function get_instance(): self
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct()
	{
		$this->db = A7_Withdrawal_DB::get_instance();
		$this->init_hooks();
	}

	private function init_hooks(): void
	{
		add_action('admin_menu', array($this, 'register_menu'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
		add_action('admin_init', array($this, 'register_settings'));
		add_action('admin_init', array($this, 'handle_export_csv'));

		// Kolumna w panelu zamówień
		add_filter('manage_woocommerce_page_wc-orders_columns', array($this, 'add_orders_column'));
		add_filter('manage_edit-shop_order_columns', array($this, 'add_orders_column')); // legacy
		add_action('manage_woocommerce_page_wc-orders_custom_column', array($this, 'render_orders_column'), 10, 2);
		add_action('manage_shop_order_posts_custom_column', array($this, 'render_orders_column_legacy'), 10, 2);

		// Metabox w szczegółach zamówienia (panel admina)
		add_action('add_meta_boxes', array($this, 'add_order_metabox'));
	}

	// =========================================================================
	// Menu
	// =========================================================================

	public function register_menu(): void
	{
		// Podstrona pod WooCommerce
		add_submenu_page(
			'woocommerce',
			__('Odstąpienia od umowy', 'studio-a7-odstap'),
			__('Odstąpienia', 'studio-a7-odstap'),
			'manage_woocommerce',
			'a7w-requests',
			array($this, 'render_requests_page')
		);

		add_submenu_page(
			'woocommerce',
			__('Ustawienia – Odstąp od umowy', 'studio-a7-odstap'),
			__('Odstąp – Ustawienia', 'studio-a7-odstap'),
			'manage_woocommerce',
			'a7w-settings',
			array($this, 'render_settings_page')
		);
	}

	// =========================================================================
	// Assety admina
	// =========================================================================

	public function enqueue_assets(string $hook): void
	{
		$pages = array('woocommerce_page_a7w-requests', 'woocommerce_page_a7w-settings');
		if (!in_array($hook, $pages, true)) {
			return;
		}

		wp_enqueue_style(
			'a7w-admin',
			A7W_PLUGIN_URL . 'admin/css/admin.css',
			array(),
			A7W_VERSION
		);
	}

	// =========================================================================
	// Rejestracja ustawień (Settings API)
	// =========================================================================

	public function register_settings(): void
	{
		// Sekcja ogólna
		register_setting(
			'a7w_settings',
			'a7w_withdrawal_days',
			array(
				'type' => 'integer',
				'default' => 14,
			)
		);
		register_setting(
			'a7w_settings',
			'a7w_allowed_statuses',
			array(
				'type' => 'array',
				'default' => array('wc-completed', 'wc-processing'),
			)
		);
		register_setting(
			'a7w_settings',
			'a7w_button_label',
			array(
				'type' => 'string',
				'default' => __('Odstąp od umowy', 'studio-a7-odstap'),
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		register_setting(
			'a7w_settings',
			'a7w_show_days_remaining',
			array(
				'type' => 'string',
				'default' => 'yes',
			)
		);
		register_setting(
			'a7w_settings',
			'a7w_require_reason',
			array(
				'type' => 'string',
				'default' => 'no',
			)
		);
		register_setting(
			'a7w_settings',
			'a7w_order_status_after_withdrawal',
			array(
				'type' => 'string',
				'default' => '',
			)
		);

		// Sekcja wyjątków
		register_setting(
			'a7w_settings',
			'a7w_exclude_virtual',
			array(
				'type' => 'string',
				'default' => 'yes',
			)
		);
		register_setting(
			'a7w_settings',
			'a7w_exclude_downloadable',
			array(
				'type' => 'string',
				'default' => 'yes',
			)
		);
		register_setting(
			'a7w_settings',
			'a7w_excluded_categories',
			array(
				'type' => 'array',
				'default' => array(),
			)
		);

		// Sekcja powiadomień
		register_setting(
			'a7w_settings',
			'a7w_notify_admin',
			array(
				'type' => 'string',
				'default' => 'yes',
			)
		);
		register_setting(
			'a7w_settings',
			'a7w_admin_email',
			array(
				'type' => 'string',
				'default' => get_option('admin_email'),
				'sanitize_callback' => array($this, 'sanitize_recipients'),
			)
		);
		register_setting(
			'a7w_settings',
			'a7w_retention_months',
			array(
				'type' => 'integer',
				'default' => 24,
				'sanitize_callback' => static function ($value): int {
					return min(120, max(1, absint($value)));
				},
			)
		);
		register_setting(
			'a7w_settings',
			'a7w_delete_data_on_uninstall',
			array(
				'type' => 'string',
				'default' => 'no',
				'sanitize_callback' => static function ($value): string {
					return 'yes' === $value ? 'yes' : 'no';
				},
			)
		);
	}

	/**
	 * Normalizuje adresy odbiorców rozdzielone przecinkami.
	 *
	 * @param mixed $value Wartość ustawienia.
	 * @return string
	 */
	public function sanitize_recipients($value): string
	{
		$recipients = array_filter(
			array_map('sanitize_email', array_map('trim', explode(',', (string) $value)))
		);

		return implode(',', array_unique($recipients));
	}

	// =========================================================================
	// Strona listy zgłoszeń
	// =========================================================================

	public function render_requests_page(): void
	{
		if (!current_user_can('manage_woocommerce')) {
			wp_die(esc_html__('Brak uprawnień.', 'studio-a7-odstap'));
		}

		$view_path = A7W_PLUGIN_DIR . 'admin/views/requests-list.php';
		if (file_exists($view_path)) {
			// Przekaż dane do widoku
			$status = isset($_GET['status']) ? sanitize_key(wp_unslash($_GET['status'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$page = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$data = $this->db->get_list(
				array(
					'status' => $status,
					'search' => $search,
					'per_page' => 20,
					'page' => $page,
				)
			);
			include $view_path;
		}
	}

	// =========================================================================
	// Strona ustawień
	// =========================================================================

	public function render_settings_page(): void
	{
		if (!current_user_can('manage_woocommerce')) {
			wp_die(esc_html__('Brak uprawnień.', 'studio-a7-odstap'));
		}

		$view_path = A7W_PLUGIN_DIR . 'admin/views/settings-page.php';
		if (file_exists($view_path)) {
			include $view_path;
		}
	}

	// =========================================================================
	// Eksport CSV
	// =========================================================================

	public function handle_export_csv(): void
	{
		$export_type = isset($_GET['a7w_export']) ? sanitize_key(wp_unslash($_GET['a7w_export'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if (
			'csv' !== $export_type
			|| !current_user_can('manage_woocommerce')
			|| !check_admin_referer('a7w_export_csv')
		) {
			return;
		}

		$data = $this->db->get_list(array('per_page' => 9999));
		$rows = $data['items'];

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename="odstapienia-' . wp_date('Y-m-d') . '.csv"');
		header('Pragma: no-cache');

		$output = fopen('php://output', 'w'); // phpcs:ignore

		// UTF-8 BOM dla Excela
		fputs($output, "\xEF\xBB\xBF"); // phpcs:ignore

		// Nagłówki
		fputcsv(
			$output,
			array(
				'ID',
				__('Nr zamówienia', 'studio-a7-odstap'),
				__('Klient', 'studio-a7-odstap'),
				__('E-mail', 'studio-a7-odstap'),
				__('Status', 'studio-a7-odstap'),
				__('Data złożenia', 'studio-a7-odstap'),
				__('Data potwierdzenia', 'studio-a7-odstap'),
				__('Powód', 'studio-a7-odstap'),
				'IP',
			),
			';'
		);

		foreach ($rows as $row) {
			fputcsv(
				$output,
				array(
					$row->id,
					$row->order_id,
					$row->customer_name,
					$row->customer_email,
					$this->get_status_label($row->status),
					$row->created_at,
					$row->confirmed_at ?? '',
					$row->reason,
					$row->ip_address,
				),
				';'
			);
		}

		fclose($output); // phpcs:ignore
		exit;
	}

	// =========================================================================
	// Kolumna w tabeli zamówień WC
	// =========================================================================

	public function add_orders_column(array $columns): array
	{
		$new = array();
		foreach ($columns as $key => $label) {
			$new[$key] = $label;
			if ('order_status' === $key) {
				$new['a7w_status'] = __('Odstąpienie', 'studio-a7-odstap');
			}
		}
		return $new;
	}

	/**
	 * Renderuje komórkę kolumny (HPOS).
	 *
	 * @param string    $column_name Nazwa kolumny.
	 * @param \WC_Order $order       Obiekt zamówienia.
	 */
	public function render_orders_column(string $column_name, \WC_Order $order): void
	{
		if ('a7w_status' !== $column_name) {
			return;
		}
		$this->render_column_cell($order->get_id());
	}

	/**
	 * Renderuje komórkę kolumny (legacy post-based).
	 *
	 * @param string $column_name Nazwa kolumny.
	 * @param int    $post_id     ID posta/zamówienia.
	 */
	public function render_orders_column_legacy(string $column_name, int $post_id): void
	{
		if ('a7w_status' !== $column_name) {
			return;
		}
		$this->render_column_cell($post_id);
	}

	private function render_column_cell(int $order_id): void
	{
		$withdrawal = $this->db->get_by_order($order_id);
		if (!$withdrawal) {
			echo '<span class="a7w-col-none">—</span>';
			return;
		}

		$labels = array(
			'pending' => array(
				'label' => __('Oczekuje', 'studio-a7-odstap'),
				'class' => 'a7w-status-pending',
			),
			'confirmed' => array(
				'label' => __('Złożone', 'studio-a7-odstap'),
				'class' => 'a7w-status-confirmed',
			),
			'rejected' => array(
				'label' => __('Odrzucone', 'studio-a7-odstap'),
				'class' => 'a7w-status-rejected',
			),
		);

		$info = $labels[$withdrawal->status] ?? array(
			'label' => esc_html($withdrawal->status),
			'class' => '',
		);

		printf(
			'<span class="a7w-status-badge %s" title="%s">%s</span>',
			esc_attr($info['class']),
			esc_attr($withdrawal->confirmed_at ?? $withdrawal->created_at),
			esc_html($info['label'])
		);
	}

	// =========================================================================
	// Metabox w szczegółach zamówienia
	// =========================================================================

	public function add_order_metabox(): void
	{
		add_meta_box(
			'a7w-withdrawal-metabox',
			__('Odstąpienie od umowy', 'studio-a7-odstap'),
			array($this, 'render_order_metabox'),
			array('shop_order', 'woocommerce_page_wc-orders'),
			'side',
			'default'
		);
	}

	public function render_order_metabox($post_or_order): void
	{
		$order_id = $post_or_order instanceof \WC_Order
			? $post_or_order->get_id()
			: (int) $post_or_order->ID;

		$withdrawal = $this->db->get_by_order($order_id);

		if (!$withdrawal) {
			echo '<p class="a7w-meta-none">' . esc_html__('Brak zgłoszenia odstąpienia dla tego zamówienia.', 'studio-a7-odstap') . '</p>';
			return;
		}

		$status_labels = array(
			'pending' => __('Oczekuje na potwierdzenie', 'studio-a7-odstap'),
			'confirmed' => __('Potwierdzone', 'studio-a7-odstap'),
			'rejected' => __('Odrzucone', 'studio-a7-odstap'),
		);

		?>
		<table class="a7w-meta-table">
			<tr>
				<th><?php esc_html_e('Status', 'studio-a7-odstap'); ?></th>
				<td><strong><?php echo esc_html($status_labels[$withdrawal->status] ?? $withdrawal->status); ?></strong>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e('Data złożenia', 'studio-a7-odstap'); ?></th>
				<td><?php echo esc_html($withdrawal->created_at); ?></td>
			</tr>
			<?php if ($withdrawal->confirmed_at): ?>
				<tr>
					<th><?php esc_html_e('Data potwierdzenia', 'studio-a7-odstap'); ?></th>
					<td><?php echo esc_html($withdrawal->confirmed_at); ?></td>
				</tr>
			<?php endif; ?>
			<?php if (!empty($withdrawal->reason)): ?>
				<tr>
					<th><?php esc_html_e('Powód', 'studio-a7-odstap'); ?></th>
					<td><?php echo esc_html($withdrawal->reason); ?></td>
				</tr>
			<?php endif; ?>
			<tr>
				<th>IP</th>
				<td><code><?php echo esc_html($withdrawal->ip_address); ?></code></td>
			</tr>
		</table>
		<?php
	}

	// =========================================================================
	// Pomocnicze
	// =========================================================================

	/**
	 * Zwraca czytelną etykietę statusu.
	 *
	 * @param string $status Status z bazy.
	 * @return string
	 */
	private function get_status_label(string $status): string
	{
		$labels = array(
			'pending' => __('Oczekuje', 'studio-a7-odstap'),
			'confirmed' => __('Potwierdzone', 'studio-a7-odstap'),
			'rejected' => __('Odrzucone', 'studio-a7-odstap'),
		);
		return $labels[$status] ?? $status;
	}
}
