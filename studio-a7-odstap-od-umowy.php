<?php
/**
 * Plugin Name:       Studio A7 – Odstąpienie od umowy for WooCommerce
 * Plugin URI:        https://studio-a7.pl
 * Description:       Narzędzie do obsługi oświadczeń o odstąpieniu od umowy dla sklepów korzystających z WooCommerce.
 * Version:           2.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Studio A7
 * Author URI:        https://studio-a7.pl
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       studio-a7-odstap
 * Domain Path:       /languages
 * WC requires at least: 7.0
 * WC tested up to:      9.x
 *
 * @package StudioA7_Withdrawal
 */

defined('ABSPATH') || exit;

// -------------------------------------------------------------------------
// Stałe wtyczki
// -------------------------------------------------------------------------
define('A7W_VERSION', '2.0.0');
define('A7W_PLUGIN_FILE', __FILE__);
define('A7W_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('A7W_PLUGIN_URL', plugin_dir_url(__FILE__));
define('A7W_PLUGIN_BASE', plugin_basename(__FILE__));
define('A7W_TEXT_DOMAIN', 'studio-a7-odstap');

// -------------------------------------------------------------------------
// Sprawdzenie zależności (WooCommerce)
// -------------------------------------------------------------------------
function a7w_check_dependencies(): bool
{
	$active_plugins = (array) apply_filters('active_plugins', get_option('active_plugins', array()));

	if (in_array('woocommerce/woocommerce.php', $active_plugins, true)) {
		return true;
	}

	if (is_multisite()) {
		$network_plugins = (array) get_site_option('active_sitewide_plugins', array());
		return isset($network_plugins['woocommerce/woocommerce.php']);
	}

	return class_exists('WooCommerce');
}

// -------------------------------------------------------------------------
// Komunikat o braku WooCommerce
// -------------------------------------------------------------------------
function a7w_missing_woocommerce_notice(): void
{
	echo '<div class="notice notice-error"><p>'
		. esc_html__('Studio A7 – Odstąpienie od umowy for WooCommerce wymaga zainstalowanego i aktywnego WooCommerce.', 'studio-a7-odstap')
		. '</p></div>';
}

/**
 * Wyświetla komunikat, gdy plik wymagany przez wtyczkę nie został wdrożony.
 *
 * @param string $relative_path Ścieżka względna pliku.
 */
function a7w_missing_plugin_file_notice(string $relative_path): void
{
	echo '<div class="notice notice-error"><p>'
		. esc_html(
			sprintf(
				/* translators: %s: relative plugin file path. */
				__('Studio A7 – Odstąpienie od umowy for WooCommerce nie może zostać uruchomiona, ponieważ brakuje pliku: %s.', 'studio-a7-odstap'),
				$relative_path
			)
		)
		. '</p></div>';
}

/**
 * Ładuje plik wtyczki tylko, jeśli istnieje i jest czytelny.
 *
 * @param string $relative_path Ścieżka względna do katalogu wtyczki.
 * @return bool
 */
function a7w_load_plugin_file(string $relative_path): bool
{
	$path = A7W_PLUGIN_DIR . ltrim($relative_path, '/\\');

	if (!is_readable($path)) {
		add_action(
			'admin_notices',
			static function () use ($relative_path): void {
				a7w_missing_plugin_file_notice($relative_path);
			}
		);
		return false;
	}

	require_once $path;
	return true;
}

// -------------------------------------------------------------------------
// Deklaracja zgodności z HPOS (High-Performance Order Storage)
// -------------------------------------------------------------------------
add_action(
	'before_woocommerce_init',
	function (): void {
		if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				__FILE__,
				true
			);
		}
	}
);

// -------------------------------------------------------------------------
// Inicjalizacja wtyczki
// -------------------------------------------------------------------------
function a7w_init(): void
{
	load_plugin_textdomain(
		A7W_TEXT_DOMAIN,
		false,
		dirname(A7W_PLUGIN_BASE) . '/languages'
	);

	if (!a7w_check_dependencies()) {
		add_action('admin_notices', 'a7w_missing_woocommerce_notice');
		return;
	}

	// Ładowanie klas bez ryzyka fatal error dla niepełnej paczki wdrożeniowej.
	$required_files = array(
		'includes/class-a7-withdrawal-db.php',
		'includes/class-a7-withdrawal-form-fields.php',
		'includes/class-a7-withdrawal-rules.php',
		'includes/class-a7-withdrawal-handler.php',
		'includes/class-a7-withdrawal-email.php',
		'includes/class-a7-withdrawal-admin.php',
		'includes/class-a7-withdrawal-main.php',
	);

	foreach ($required_files as $required_file) {
		if (!a7w_load_plugin_file($required_file)) {
			return;
		}
	}

	if (!class_exists('A7_Withdrawal_DB') || !class_exists('A7_Withdrawal_Main') || !class_exists('A7_Withdrawal_Admin')) {
		add_action('admin_notices', 'a7w_missing_woocommerce_notice');
		return;
	}

	// Uruchomienie modułów
	A7_Withdrawal_DB::get_instance();
	if (A7W_VERSION !== get_option('a7w_db_version')) {
		A7_Withdrawal_DB::create_table();
	}
	A7_Withdrawal_Main::get_instance();
	A7_Withdrawal_Admin::get_instance();
}
add_action('plugins_loaded', 'a7w_init', 20);

// -------------------------------------------------------------------------
// Aktywacja: tworzenie tabeli w bazie danych
// -------------------------------------------------------------------------
function a7w_activate(): void
{
	if (!a7w_check_dependencies()) {
		deactivate_plugins(A7W_PLUGIN_BASE);
		wp_die(esc_html__('Studio A7 – Odstąpienie od umowy for WooCommerce wymaga aktywnego WooCommerce.', 'studio-a7-odstap'));
	}

	if (!a7w_load_plugin_file('includes/class-a7-withdrawal-db.php') || !class_exists('A7_Withdrawal_DB')) {
		deactivate_plugins(A7W_PLUGIN_BASE);
		wp_die(esc_html__('Nie można aktywować wtyczki: brakuje wymaganego pliku bazy danych.', 'studio-a7-odstap'));
	}

	A7_Withdrawal_DB::create_table();

	// Domyślne opcje
	$defaults = array(
		'withdrawal_days' => 14,
		'allowed_statuses' => array('wc-completed', 'wc-processing'),
		'excluded_categories' => array(),
		'exclude_virtual' => 'yes',
		'exclude_downloadable' => 'yes',
		'admin_email' => get_option('admin_email'),
		'button_label' => __('Odstąp od umowy', 'studio-a7-odstap'),
		'show_days_remaining' => 'yes',
		'require_reason' => 'no',
		'notify_admin' => 'yes',
		'retention_months' => 24,
		'form_fields' => array(),
		'eligibility_rules' => array(),
		'approval_action' => 'none',
		'coupon_amount' => 0,
		'refund_amount' => 0,
		'refund_payment' => 'no',
		'delete_data_on_uninstall' => 'no',
	);

	foreach ($defaults as $key => $value) {
		if (false === get_option('a7w_' . $key)) {
			add_option('a7w_' . $key, $value);
		}
	}

	flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'a7w_activate');

// -------------------------------------------------------------------------
// Deaktywacja
// -------------------------------------------------------------------------
function a7w_deactivate(): void
{
	flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'a7w_deactivate');

// -------------------------------------------------------------------------
// Link do ustawień na liście wtyczek
// -------------------------------------------------------------------------
add_filter(
	'plugin_action_links_' . A7W_PLUGIN_BASE,
	function (array $links): array {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url(admin_url('admin.php?page=a7w-settings')),
			esc_html__('Ustawienia', 'studio-a7-odstap')
		);
		array_unshift($links, $settings_link);
		return $links;
	}
);
