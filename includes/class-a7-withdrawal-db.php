<?php
/**
 * Klasa zarządzania bazą danych.
 *
 * @package StudioA7_Withdrawal
 */

defined('ABSPATH') || exit;

/**
 * A7_Withdrawal_DB
 *
 * Zarządza tabelą `{prefix}_a7_withdrawals` przechowującą wnioski odstąpień.
 */
class A7_Withdrawal_DB
{


	/** @var A7_Withdrawal_DB|null Singleton instance */
	private static ?A7_Withdrawal_DB $instance = null;

	/** Nazwa tabeli (bez prefiksu) */
	const TABLE = 'a7_withdrawals';

	/**
	 * Zwraca instancję singletona.
	 */
	public static function get_instance(): self
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct()
	{
	}

	// -------------------------------------------------------------------------
	// DDL – tworzenie tabeli
	// -------------------------------------------------------------------------

	/**
	 * Tworzy lub aktualizuje tabelę w bazie danych.
	 * Wywołane przy aktywacji wtyczki.
	 */
	public static function create_table(): void
	{
		global $wpdb;

		$table_name = $wpdb->prefix . self::TABLE;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			order_id       BIGINT UNSIGNED NOT NULL,
			customer_id    BIGINT UNSIGNED NOT NULL DEFAULT 0,
			customer_email VARCHAR(200)    NOT NULL DEFAULT '',
			customer_name  VARCHAR(200)    NOT NULL DEFAULT '',
			reason         TEXT,
			status         VARCHAR(50)     NOT NULL DEFAULT 'pending',
			token          VARCHAR(64)     NOT NULL DEFAULT '',
			ip_address     VARCHAR(45)     NOT NULL DEFAULT '',
			user_agent     TEXT,
			item_quantities LONGTEXT,
			form_data      LONGTEXT,
			shipping_data  LONGTEXT,
			audit_log      LONGTEXT,
			admin_note     TEXT,
			decided_by     BIGINT UNSIGNED NOT NULL DEFAULT 0,
			decided_at     DATETIME        DEFAULT NULL,
			updated_at     DATETIME        DEFAULT NULL,
			created_at     DATETIME        NOT NULL,
			confirmed_at   DATETIME        DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY   token    (token),
			KEY          order_id (order_id),
			KEY          status   (status)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql);
		// Wersje wcześniejsze tworzyły unikalny indeks, który blokował częściowe odstąpienia.
		$indexes = $wpdb->get_results("SHOW INDEX FROM {$table_name} WHERE Key_name = 'order_id'"); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ($indexes && !empty($indexes[0]->Non_unique) === false) {
			$wpdb->query("ALTER TABLE {$table_name} DROP INDEX order_id, ADD KEY order_id (order_id)"); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		update_option('a7w_db_version', A7W_VERSION);
	}

	// -------------------------------------------------------------------------
	// CRUD
	// -------------------------------------------------------------------------

	/**
	 * Zwraca pełną nazwę tabeli (z prefiksem).
	 */
	public function get_table(): string
	{
		global $wpdb;
		return $wpdb->prefix . self::TABLE;
	}

	/**
	 * Wstawia nowy wniosek (krok 1 – oczekujący na potwierdzenie).
	 *
	 * @param array $data Dane wniosku.
	 * @return int|false ID wstawionego rekordu lub false przy błędzie.
	 */
	public function insert(array $data): int|false
	{
		global $wpdb;

		$defaults = array(
			'order_id' => 0,
			'customer_id' => 0,
			'customer_email' => '',
			'customer_name' => '',
			'reason' => '',
			'status' => 'pending',
			'token' => '',
			'ip_address' => '',
			'user_agent' => '',
			'item_quantities' => '',
			'form_data' => '',
			'shipping_data' => '',
			'audit_log' => '',
			'admin_note' => '',
			'decided_by' => 0,
			'decided_at' => null,
			'updated_at' => null,
			'created_at' => current_time('mysql'),
			'confirmed_at' => null,
		);

		$data = wp_parse_args($data, $defaults);

		$result = $wpdb->insert(
			$this->get_table(),
			$data,
			array(
				'%d', // order_id
				'%d', // customer_id
				'%s', // customer_email
				'%s', // customer_name
				'%s', // reason
				'%s', // status
				'%s', // token
				'%s', // ip_address
				'%s', // user_agent
				'%s', // item_quantities
				'%s', // form_data
				'%s', // shipping_data
				'%s', // audit_log
				'%s', // admin_note
				'%d', // decided_by
				'%s', // decided_at
				'%s', // updated_at
				'%s', // created_at
				'%s', // confirmed_at
			)
		);

		return $result ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Records a staff decision and its audit details.
	 *
	 * @param int    $id         Withdrawal ID.
	 * @param string $status     Approved or rejected status.
	 * @param string $admin_note Internal or customer-facing decision note.
	 * @param int    $user_id    Staff user ID.
	 * @return bool
	 */
	public function decide(int $id, string $status, string $admin_note, int $user_id): bool
	{
		if (!in_array($status, array('approved', 'rejected'), true)) {
			return false;
		}

		global $wpdb;
		$now = current_time('mysql');
		$withdrawal = $this->get($id);
		if (!$withdrawal || 'confirmed' !== $withdrawal->status) {
			return false;
		}

		$result = $wpdb->update(
			$this->get_table(),
			array(
				'status' => $status,
				'admin_note' => $admin_note,
				'decided_by' => $user_id,
				'decided_at' => $now,
				'updated_at' => $now,
				'audit_log' => $this->append_audit_entry((string) $withdrawal->audit_log, 'decision', $user_id, $status, $admin_note),
			),
			array('id' => $id, 'status' => 'confirmed'),
			array('%s', '%s', '%d', '%s', '%s', '%s'),
			array('%d', '%s')
		);

		return 1 === $result;
	}

	/**
	 * Cancels a request while it is still awaiting staff processing.
	 *
	 * @param int $id Withdrawal ID.
	 * @param int $customer_id Customer ID; zero is accepted only after guest access was verified by the caller.
	 * @return bool
	 */
	public function cancel(int $id, int $customer_id = 0): bool
	{
		$withdrawal = $this->get($id);
		if (!$withdrawal || !in_array($withdrawal->status, array('pending', 'confirmed'), true)) {
			return false;
		}

		if ($customer_id > 0 && $customer_id !== (int) $withdrawal->customer_id) {
			return false;
		}

		global $wpdb;
		$now = current_time('mysql');
		return 1 === $wpdb->update(
			$this->get_table(),
			array(
				'status' => 'cancelled',
				'updated_at' => $now,
				'audit_log' => $this->append_audit_entry((string) $withdrawal->audit_log, 'cancelled', $customer_id, 'cancelled', ''),
			),
			array('id' => $id, 'status' => $withdrawal->status),
			array('%s', '%s', '%s'),
			array('%d', '%s')
		);
	}

	/**
	 * Updates return shipment information for a request that is still in the
	 * customer-service lifecycle. Authorization is performed by the caller.
	 *
	 * @param array<string, string> $shipping_data
	 */
	public function update_shipping(int $id, array $shipping_data, int $actor_id = 0): bool
	{
		$withdrawal = $this->get($id);
		if (!$withdrawal || !in_array($withdrawal->status, array('confirmed', 'approved'), true)) {
			return false;
		}

		$shipping_data = array(
			'return_method' => sanitize_text_field((string) ($shipping_data['return_method'] ?? '')),
			'tracking_number' => sanitize_text_field((string) ($shipping_data['tracking_number'] ?? '')),
		);
		global $wpdb;
		$now = current_time('mysql');
		return 1 === $wpdb->update(
			$this->get_table(),
			array(
				'shipping_data' => wp_json_encode($shipping_data),
				'updated_at' => $now,
				'audit_log' => $this->append_audit_entry((string) $withdrawal->audit_log, 'shipping_updated', $actor_id, $withdrawal->status, ''),
			),
			array('id' => $id, 'status' => $withdrawal->status),
			array('%s', '%s', '%s'),
			array('%d', '%s')
		);
	}

	/** Adds an audit record without changing the lifecycle status. */
	public function add_audit_event(int $id, string $event, int $actor_id, string $note = ''): bool
	{
		$withdrawal = $this->get($id);
		if (!$withdrawal) {
			return false;
		}
		global $wpdb;
		return 1 === $wpdb->update(
			$this->get_table(),
			array(
				'audit_log' => $this->append_audit_entry((string) $withdrawal->audit_log, $event, $actor_id, (string) $withdrawal->status, $note),
				'updated_at' => current_time('mysql'),
			),
			array('id' => $id),
			array('%s', '%s'),
			array('%d')
		);
	}

	/**
	 * Returns requests belonging to a customer or a verified guest email/order pair.
	 *
	 * @return array<int, object>
	 */
	public function get_customer_requests(int $customer_id, string $email = '', int $order_id = 0): array
	{
		global $wpdb;
		$table = $this->get_table();
		if ($customer_id > 0) {
			return (array) $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE customer_id = %d ORDER BY created_at DESC", $customer_id)); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		if ('' === $email || $order_id <= 0) {
			return array();
		}

		return (array) $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE customer_id = 0 AND customer_email = %s AND order_id = %d ORDER BY created_at DESC", $email, $order_id)); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Adds a structured, immutable audit event to the request record.
	 */
	private function append_audit_entry(string $audit_log, string $event, int $user_id, string $status, string $note): string
	{
		$entries = json_decode($audit_log, true);
		$entries = is_array($entries) ? $entries : array();
		$entries[] = array(
			'event' => sanitize_key($event),
			'user_id' => absint($user_id),
			'status' => sanitize_key($status),
			'note' => sanitize_textarea_field($note),
			'at' => current_time('mysql'),
		);
		return wp_json_encode($entries);
	}

	/**
	 * Aktualizuje status i datę potwierdzenia wniosku.
	 *
	 * @param int    $id     ID wniosku.
	 * @param string $status Nowy status.
	 * @return bool
	 */
	public function confirm(int $id, string $status = 'confirmed'): bool
	{
		global $wpdb;

		$withdrawal = $this->get($id);
		if (!$withdrawal || 'pending' !== $withdrawal->status) {
			return false;
		}
		$now = current_time('mysql');
		$result = $wpdb->update(
			$this->get_table(),
			array(
				'status' => $status,
				'confirmed_at' => $now,
				'updated_at' => $now,
				'audit_log' => $this->append_audit_entry((string) $withdrawal->audit_log, 'confirmed', (int) $withdrawal->customer_id, $status, ''),
			),
			array(
				'id' => $id,
				'status' => 'pending',
			),
			array('%s', '%s', '%s'),
			array('%d', '%s')
		);

		return 1 === $result;
	}

	/**
	 * Pobiera wniosek po tokenie.
	 *
	 * @param string $token Token potwierdzający.
	 * @return object|null
	 */
	public function get_by_token(string $token): ?object
	{
		global $wpdb;

		$table = $this->get_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row(
			$wpdb->prepare("SELECT * FROM {$table} WHERE token = %s LIMIT 1", $token)
		);
	}

	/**
	 * Pobiera wniosek po ID zamówienia.
	 *
	 * @param int $order_id ID zamówienia.
	 * @return object|null
	 */
	public function get_by_order(int $order_id): ?object
	{
		global $wpdb;

		$table = $this->get_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row(
			$wpdb->prepare("SELECT * FROM {$table} WHERE order_id = %d ORDER BY id DESC LIMIT 1", $order_id)
		);
	}

	/**
	 * Zwraca łączną liczbę potwierdzonych ilości dla pozycji zamówienia.
	 *
	 * @param int $order_id ID zamówienia.
	 * @return array<int, int>
	 */
	public function get_confirmed_item_quantities(int $order_id): array
	{
		global $wpdb;

		$table = $this->get_table();
		$rows = $wpdb->get_col($wpdb->prepare("SELECT item_quantities FROM {$table} WHERE order_id = %d AND status IN ('confirmed', 'approved')", $order_id)); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$quantities = array();

		foreach ($rows as $row) {
			$data = json_decode((string) $row, true);
			if (!is_array($data)) {
				continue;
			}
			foreach ($data as $item_id => $quantity) {
				$item_id = absint($item_id);
				$quantities[$item_id] = ($quantities[$item_id] ?? 0) + absint($quantity);
			}
		}

		return $quantities;
	}

	/**
	 * Pobiera listę wniosków z filtrowaniem i paginacją.
	 *
	 * @param array $args Argumenty zapytania.
	 * @return array{items: array, total: int}
	 */
	public function get_list(array $args = array()): array
	{
		global $wpdb;

		$defaults = array(
			'status' => '',
			'search' => '',
			'per_page' => 20,
			'page' => 1,
			'orderby' => 'created_at',
			'order' => 'DESC',
		);

		$args = wp_parse_args($args, $defaults);
		$table = $this->get_table();
		$where = array();
		$values = array();

		if (!empty($args['status'])) {
			$where[] = 'status = %s';
			$values[] = $args['status'];
		}

		if (!empty($args['search'])) {
			$where[] = '(customer_email LIKE %s OR customer_name LIKE %s OR order_id = %d)';
			$like = '%' . $wpdb->esc_like($args['search']) . '%';
			$values[] = $like;
			$values[] = $like;
			$values[] = (int) $args['search'];
		}

		$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

		// Bezpieczna lista kolumn dla ORDER BY
		$allowed_orderby = array('id', 'order_id', 'customer_email', 'status', 'created_at', 'confirmed_at');
		$orderby = in_array($args['orderby'], $allowed_orderby, true) ? $args['orderby'] : 'created_at';
		$order = 'ASC' === strtoupper($args['order']) ? 'ASC' : 'DESC';

		$offset = ((int) $args['page'] - 1) * (int) $args['per_page'];

		// Całkowita liczba rekordów
		$count_sql = "SELECT COUNT(*) FROM {$table} {$where_sql}";
		$total = (int) ($values
			? $wpdb->get_var($wpdb->prepare($count_sql, $values)) // phpcs:ignore
			: $wpdb->get_var($count_sql)); // phpcs:ignore

		// Rekordy
		$data_sql = "SELECT * FROM {$table} {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
		$all_values = array_merge($values, array((int) $args['per_page'], $offset));
		$items = $wpdb->get_results($wpdb->prepare($data_sql, $all_values)); // phpcs:ignore

		return array(
			'items' => $items ? $items : array(),
			'total' => $total,
		);
	}

	/**
	 * Pobiera wniosek po ID.
	 *
	 * @param int $id ID wniosku.
	 * @return object|null
	 */
	public function get(int $id): ?object
	{
		global $wpdb;

		$table = $this->get_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row(
			$wpdb->prepare("SELECT * FROM {$table} WHERE id = %d LIMIT 1", $id)
		);
	}

	/**
	 * Sprawdza czy istnieje potwierdzony wniosek dla danego zamówienia.
	 *
	 * @param int $order_id               ID zamówienia.
	 * @param int $ignored_withdrawal_id ID wniosku pomijanego podczas sprawdzania.
	 * @return bool
	 */
	public function order_has_withdrawal(int $order_id, int $ignored_withdrawal_id = 0): bool
	{
		global $wpdb;

		$table = $this->get_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = "SELECT COUNT(*) FROM {$table} WHERE order_id = %d AND status IN ('pending','confirmed')";
		$values = array($order_id);

		if ($ignored_withdrawal_id > 0) {
			$sql .= ' AND id != %d';
			$values[] = $ignored_withdrawal_id;
		}

		$count = $wpdb->get_var($wpdb->prepare($sql, $values)); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return (int) $count > 0;
	}

	/**
	 * Usuwa przestarzałe wnioski w statusie 'pending' starsze niż X godzin.
	 *
	 * @param int $hours Liczba godzin.
	 * @return int Liczba usuniętych rekordów.
	 */
	public function cleanup_expired_pending(int $hours = 24): int
	{
		global $wpdb;

		$table = $this->get_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE status = 'pending' AND created_at < DATE_SUB(NOW(), INTERVAL %d HOUR)",
				$hours
			)
		);
	}

	/**
	 * Usuwa potwierdzone wnioski po zakończeniu okresu retencji.
	 *
	 * @param int $months Liczba miesięcy retencji.
	 * @return int Liczba usuniętych rekordów.
	 */
	public function cleanup_expired_confirmed(int $months = 24): int
	{
		global $wpdb;

		$table = $this->get_table();
		return (int) $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE status = 'confirmed' AND confirmed_at < DATE_SUB(NOW(), INTERVAL %d MONTH)",
				max(1, $months)
			)
		);
	}
}
