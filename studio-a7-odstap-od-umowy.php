<?php
/**
 * Plugin Name:       Studio A7 – Odstąp od umowy
 * Plugin URI:        https://studio-a7.pl
 * Description:       Profesjonalny przycisk odstąpienia od umowy dla WooCommerce zgodny z Dyrektywą UE 2023/2673 (obowiązuje od 19 czerwca 2026 r.). Dwuetapowy proces, potwierdzenie e-mail, panel zarządzania wnioskami.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Studio A7
 * Author URI:        https://studio-a7.pl
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       studio-a7-odstap
 * Domain Path:       /languages
 * WC requires at least: 7.0
 * WC tested up to:      9.x
 *
 * @package StudioA7_Withdrawal
 */

defined( 'ABSPATH' ) || exit;

// -------------------------------------------------------------------------
// Stałe wtyczki
// -------------------------------------------------------------------------
define( 'A7W_VERSION', '1.0.0' );
define( 'A7W_PLUGIN_FILE', __FILE__ );
define( 'A7W_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'A7W_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'A7W_PLUGIN_BASE', plugin_basename( __FILE__ ) );
define( 'A7W_TEXT_DOMAIN', 'studio-a7-odstap' );

// -------------------------------------------------------------------------
// Sprawdzenie zależności (WooCommerce)
// -------------------------------------------------------------------------
function a7w_check_dependencies(): bool {
	return class_exists( 'WooCommerce' );
}

// -------------------------------------------------------------------------
// Komunikat o braku WooCommerce
// -------------------------------------------------------------------------
function a7w_missing_woocommerce_notice(): void {
	echo '<div class="notice notice-error"><p>'
		. esc_html__( 'Studio A7 – Odstąp od umowy wymaga zainstalowanego i aktywnego WooCommerce.', 'studio-a7-odstap' )
		. '</p></div>';
}

// -------------------------------------------------------------------------
// Deklaracja zgodności z HPOS (High-Performance Order Storage)
// -------------------------------------------------------------------------
add_action(
	'before_woocommerce_init',
	function (): void {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
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
function a7w_init(): void {
	load_plugin_textdomain(
		A7W_TEXT_DOMAIN,
		false,
		dirname( A7W_PLUGIN_BASE ) . '/languages'
	);

	if ( ! a7w_check_dependencies() ) {
		add_action( 'admin_notices', 'a7w_missing_woocommerce_notice' );
		return;
	}

	// Ładowanie klas
	require_once A7W_PLUGIN_DIR . 'includes/class-a7-withdrawal-db.php';
	require_once A7W_PLUGIN_DIR . 'includes/class-a7-withdrawal-handler.php';
	require_once A7W_PLUGIN_DIR . 'includes/class-a7-withdrawal-email.php';
	require_once A7W_PLUGIN_DIR . 'includes/class-a7-withdrawal-admin.php';
	require_once A7W_PLUGIN_DIR . 'includes/class-a7-withdrawal-main.php';

	// Uruchomienie modułów
	A7_Withdrawal_DB::get_instance();
	A7_Withdrawal_Main::get_instance();
	A7_Withdrawal_Admin::get_instance();
}
add_action( 'plugins_loaded', 'a7w_init', 20 );

// -------------------------------------------------------------------------
// Aktywacja: tworzenie tabeli w bazie danych
// -------------------------------------------------------------------------
function a7w_activate(): void {
	require_once A7W_PLUGIN_DIR . 'includes/class-a7-withdrawal-db.php';
	A7_Withdrawal_DB::create_table();

	// Domyślne opcje
	$defaults = array(
		'withdrawal_days'      => 14,
		'allowed_statuses'     => array( 'wc-completed', 'wc-processing' ),
		'excluded_categories'  => array(),
		'exclude_virtual'      => 'yes',
		'exclude_downloadable' => 'yes',
		'admin_email'          => get_option( 'admin_email' ),
		'button_label'         => __( 'Odstąp od umowy', 'studio-a7-odstap' ),
		'show_days_remaining'  => 'yes',
		'require_reason'       => 'no',
		'notify_admin'         => 'yes',
	);

	foreach ( $defaults as $key => $value ) {
		if ( false === get_option( 'a7w_' . $key ) ) {
			add_option( 'a7w_' . $key, $value );
		}
	}

	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'a7w_activate' );

// -------------------------------------------------------------------------
// Deaktywacja
// -------------------------------------------------------------------------
function a7w_deactivate(): void {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'a7w_deactivate' );

// -------------------------------------------------------------------------
// Link do ustawień na liście wtyczek
// -------------------------------------------------------------------------
add_filter(
	'plugin_action_links_' . A7W_PLUGIN_BASE,
	function ( array $links ): array {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=a7w-settings' ) ),
			esc_html__( 'Ustawienia', 'studio-a7-odstap' )
		);
		array_unshift( $links, $settings_link );
		return $links;
	}
);
