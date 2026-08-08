<?php
/**
 * Skrypt czyszczenia bazy danych po odinstalowaniu wtyczki.
 *
 * Ten plik jest uruchamiany przez WordPress gdy użytkownik kliknie
 * "Odinstaluj" w panelu wtyczek. Usuwa tabelę z bazy i wszystkie opcje.
 *
 * @package StudioA7_Withdrawal
 */

// Zabezpieczenie – plik może być wywołany tylko przez WordPress uninstall
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

if ( 'yes' !== get_option( 'a7w_delete_data_on_uninstall', 'no' ) ) {
	wp_clear_scheduled_hook( 'a7w_cleanup_pending' );
	return;
}

// Usuń tabelę
$table = $wpdb->prefix . 'a7_withdrawals';
$wpdb->query("DROP TABLE IF EXISTS {$table}"); // phpcs:ignore

// Usuń wszystkie opcje wtyczki
$options = array(
	'a7w_withdrawal_days',
	'a7w_allowed_statuses',
	'a7w_button_label',
	'a7w_show_days_remaining',
	'a7w_require_reason',
	'a7w_order_status_after_withdrawal',
	'a7w_exclude_virtual',
	'a7w_exclude_downloadable',
	'a7w_excluded_categories',
	'a7w_notify_admin',
	'a7w_admin_email',
	'a7w_retention_months',
	'a7w_delete_data_on_uninstall',
	'a7w_db_version',
	// Opcje emaili WooCommerce
	'woocommerce_a7w_customer_withdrawal_settings',
	'woocommerce_a7w_admin_notification_settings',
);

foreach ( $options as $option ) {
	delete_option( $option );
}

// Usuń zaplanowane zadania cron
wp_clear_scheduled_hook( 'a7w_cleanup_pending' );
