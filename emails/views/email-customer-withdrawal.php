<?php
/**
 * Szablon HTML emaila potwierdzającego odstąpienie od umowy (dla klienta).
 *
 * Szablon zawiera dane niezbędne do potwierdzenia złożenia oświadczenia.
 *
 * @var object        $withdrawal  Rekord wniosku z bazy danych.
 * @var \WC_Order|null $order       Obiekt zamówienia WooCommerce.
 * @var string        $email_heading Nagłówek emaila.
 * @var \WC_Email     $email        Instancja klasy email.
 *
 * @package StudioA7_Withdrawal
 */

defined( 'ABSPATH' ) || exit;

// Standardowy nagłówek WooCommerce
do_action( 'woocommerce_email_header', $email_heading, $email );

$confirmed_date = $withdrawal->confirmed_at
	? wp_date( get_option( 'date_format' ), strtotime( $withdrawal->confirmed_at ) )
	: wp_date( get_option( 'date_format' ) );

$confirmed_time = $withdrawal->confirmed_at
	? wp_date( get_option( 'time_format' ), strtotime( $withdrawal->confirmed_at ) )
	: wp_date( get_option( 'time_format' ) );

$order_number = $order ? $order->get_order_number() : $withdrawal->order_id;
?>

<p>
	<?php
	printf(
		/* translators: %s: imię i nazwisko klienta */
		esc_html__( 'Szanowna/y %s,', 'studio-a7-odstap' ),
		esc_html( $withdrawal->customer_name )
	);
	?>
</p>

<p><?php esc_html_e( 'Niniejszym potwierdzamy przyjęcie Pani/Pana oświadczenia o odstąpieniu od umowy sprzedaży zawartej na odległość.', 'studio-a7-odstap' ); ?>
</p>

<?php /* Blok kluczowych danych – wymagany przez dyrektywę */ ?>
<table cellspacing="0" cellpadding="6" style="width:100%;border:1px solid #e0e0e0;border-radius:6px;margin:24px 0;">
	<thead>
		<tr>
			<th colspan="2"
				style="background-color:#f8f8f8;padding:12px 16px;text-align:left;font-size:13px;font-weight:600;color:#333;border-bottom:1px solid #e0e0e0;">
				<?php esc_html_e( 'Szczegóły oświadczenia', 'studio-a7-odstap' ); ?>
			</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<th
				style="padding:10px 16px;text-align:left;font-weight:600;color:#555;width:45%;border-bottom:1px solid #f0f0f0;">
				<?php esc_html_e( 'Numer zamówienia', 'studio-a7-odstap' ); ?>
			</th>
			<td style="padding:10px 16px;border-bottom:1px solid #f0f0f0;color:#333;">
				#<?php echo esc_html( $order_number ); ?>
			</td>
		</tr>
		<tr style="background-color:#fafafa;">
			<th style="padding:10px 16px;text-align:left;font-weight:600;color:#555;border-bottom:1px solid #f0f0f0;">
				<?php esc_html_e( 'Data złożenia oświadczenia', 'studio-a7-odstap' ); ?>
			</th>
			<td style="padding:10px 16px;border-bottom:1px solid #f0f0f0;color:#333;">
				<?php echo esc_html( $confirmed_date ); ?>
			</td>
		</tr>
		<tr>
			<th style="padding:10px 16px;text-align:left;font-weight:600;color:#555;border-bottom:1px solid #f0f0f0;">
				<?php esc_html_e( 'Godzina złożenia oświadczenia', 'studio-a7-odstap' ); ?>
			</th>
			<td style="padding:10px 16px;border-bottom:1px solid #f0f0f0;color:#333;">
				<?php echo esc_html( $confirmed_time ); ?>
			</td>
		</tr>
		<?php if ( $order ) : ?>
			<tr style="background-color:#fafafa;">
				<th style="padding:10px 16px;text-align:left;font-weight:600;color:#555;border-bottom:1px solid #f0f0f0;">
					<?php esc_html_e( 'Data zamówienia', 'studio-a7-odstap' ); ?>
				</th>
				<td style="padding:10px 16px;border-bottom:1px solid #f0f0f0;color:#333;">
					<?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?>
				</td>
			</tr>
		<?php endif; ?>
		<?php if ( ! empty( $withdrawal->reason ) ) : ?>
			<tr>
				<th style="padding:10px 16px;text-align:left;font-weight:600;color:#555;">
					<?php esc_html_e( 'Powód odstąpienia', 'studio-a7-odstap' ); ?>
				</th>
				<td style="padding:10px 16px;color:#333;">
					<?php echo esc_html( $withdrawal->reason ); ?>
				</td>
			</tr>
		<?php endif; ?>
	</tbody>
</table>

<?php if ( $order ) : ?>
	<h3 style="font-size:14px;font-weight:700;color:#333;margin:24px 0 12px;">
		<?php esc_html_e( 'Zamówione produkty', 'studio-a7-odstap' ); ?></h3>
	<table cellspacing="0" cellpadding="6" style="width:100%;border:1px solid #e0e0e0;border-radius:6px;margin:0 0 24px;">
		<thead>
			<tr style="background-color:#f8f8f8;">
				<th
					style="padding:10px 16px;text-align:left;font-size:12px;font-weight:600;color:#555;border-bottom:1px solid #e0e0e0;">
					<?php esc_html_e( 'Produkt', 'studio-a7-odstap' ); ?></th>
				<th
					style="padding:10px 16px;text-align:center;font-size:12px;font-weight:600;color:#555;border-bottom:1px solid #e0e0e0;">
					<?php esc_html_e( 'Ilość', 'studio-a7-odstap' ); ?></th>
				<th
					style="padding:10px 16px;text-align:right;font-size:12px;font-weight:600;color:#555;border-bottom:1px solid #e0e0e0;">
					<?php esc_html_e( 'Cena', 'studio-a7-odstap' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $order->get_items() as $item ) : /** @var WC_Order_Item_Product $item */ ?>
				<tr>
					<td style="padding:10px 16px;border-bottom:1px solid #f0f0f0;color:#333;">
						<?php echo esc_html( $item->get_name() ); ?>
					</td>
					<td style="padding:10px 16px;text-align:center;border-bottom:1px solid #f0f0f0;color:#555;">
						<?php echo esc_html( $item->get_quantity() ); ?>
					</td>
					<td style="padding:10px 16px;text-align:right;border-bottom:1px solid #f0f0f0;color:#333;">
						<?php echo wp_kses_post( wc_price( $item->get_total() ) ); ?>
					</td>
				</tr>
			<?php endforeach; ?>
			<tr style="background-color:#f8f8f8;">
				<th colspan="2" style="padding:10px 16px;text-align:right;font-weight:700;color:#333;">
					<?php esc_html_e( 'Łącznie', 'studio-a7-odstap' ); ?></th>
				<td style="padding:10px 16px;text-align:right;font-weight:700;color:#333;">
					<?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></td>
			</tr>
		</tbody>
	</table>
<?php endif; ?>

<div style="background-color:#fff8e6;border:1px solid #f5c842;border-radius:6px;padding:16px 20px;margin:24px 0;">
	<h4 style="margin:0 0 8px;font-size:13px;font-weight:700;color:#856404;">
		<?php esc_html_e( 'Co dalej?', 'studio-a7-odstap' ); ?>
	</h4>
	<p style="margin:0;font-size:13px;color:#664d03;line-height:1.6;">
		<?php esc_html_e( 'Instrukcje dotyczące zwrotu, w tym adres zwrotu, prześle obsługa sklepu. Szczegółowe warunki zwrotu wynikają z regulaminu sklepu i obowiązujących przepisów.', 'studio-a7-odstap' ); ?>
	</p>
</div>

<?php
// Dodatkowa treść z ustawień emaila
$additional_content = $email->get_option( 'additional_content', '' );
if ( ! empty( $additional_content ) ) {
	echo '<p>' . wp_kses_post( wpautop( $additional_content ) ) . '</p>';
}

// Stopka WooCommerce
do_action( 'woocommerce_email_footer', $email );
