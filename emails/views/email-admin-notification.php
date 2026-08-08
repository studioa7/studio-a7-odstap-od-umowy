<?php
/**
 * Szablon HTML emaila powiadomienia dla administratora.
 *
 * @var object        $withdrawal   Rekord wniosku z bazy danych.
 * @var \WC_Order|null $order        Obiekt zamówienia WooCommerce.
 * @var string        $email_heading Nagłówek emaila.
 * @var \WC_Email     $email         Instancja klasy email.
 *
 * @package StudioA7_Withdrawal
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email_heading, $email );

$confirmed_date = $withdrawal->confirmed_at
	? wp_date( get_option( 'date_format' ), strtotime( $withdrawal->confirmed_at ) )
	: '—';

$confirmed_time = $withdrawal->confirmed_at
	? wp_date( get_option( 'time_format' ), strtotime( $withdrawal->confirmed_at ) )
	: '—';

$order_number = $order ? $order->get_order_number() : $withdrawal->order_id;
$admin_url    = admin_url( 'admin.php?page=a7w-requests' );
?>

<p><?php esc_html_e( 'Klient złożył oświadczenie o odstąpieniu od umowy. Poniżej znajdziesz szczegóły zgłoszenia.', 'studio-a7-odstap' ); ?></p>

<table cellspacing="0" cellpadding="6" style="width:100%;border:1px solid #e0e0e0;border-radius:6px;margin:20px 0;">
	<thead>
		<tr>
			<th colspan="2" style="background-color:#f0f0f0;padding:12px 16px;text-align:left;font-size:13px;font-weight:700;color:#333;border-bottom:1px solid #e0e0e0;">
				<?php esc_html_e( 'Dane zgłoszenia', 'studio-a7-odstap' ); ?>
			</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<th style="padding:10px 16px;text-align:left;font-weight:600;color:#555;width:40%;border-bottom:1px solid #f0f0f0;"><?php esc_html_e( 'Numer zamówienia', 'studio-a7-odstap' ); ?></th>
			<td style="padding:10px 16px;border-bottom:1px solid #f0f0f0;">
				<?php if ( $order ) : ?>
					<a href="<?php echo esc_url( $order->get_edit_order_url() ); ?>">#<?php echo esc_html( $order_number ); ?></a>
				<?php else : ?>
					#<?php echo esc_html( $order_number ); ?>
				<?php endif; ?>
			</td>
		</tr>
		<tr style="background-color:#fafafa;">
			<th style="padding:10px 16px;text-align:left;font-weight:600;color:#555;border-bottom:1px solid #f0f0f0;"><?php esc_html_e( 'Klient', 'studio-a7-odstap' ); ?></th>
			<td style="padding:10px 16px;border-bottom:1px solid #f0f0f0;"><?php echo esc_html( $withdrawal->customer_name ); ?></td>
		</tr>
		<tr>
			<th style="padding:10px 16px;text-align:left;font-weight:600;color:#555;border-bottom:1px solid #f0f0f0;"><?php esc_html_e( 'E-mail klienta', 'studio-a7-odstap' ); ?></th>
			<td style="padding:10px 16px;border-bottom:1px solid #f0f0f0;">
				<a href="mailto:<?php echo esc_attr( $withdrawal->customer_email ); ?>"><?php echo esc_html( $withdrawal->customer_email ); ?></a>
			</td>
		</tr>
		<tr style="background-color:#fafafa;">
			<th style="padding:10px 16px;text-align:left;font-weight:600;color:#555;border-bottom:1px solid #f0f0f0;"><?php esc_html_e( 'Data i godzina', 'studio-a7-odstap' ); ?></th>
			<td style="padding:10px 16px;border-bottom:1px solid #f0f0f0;"><?php echo esc_html( $confirmed_date . ' ' . $confirmed_time ); ?></td>
		</tr>
		<tr>
			<th style="padding:10px 16px;text-align:left;font-weight:600;color:#555;border-bottom:1px solid #f0f0f0;"><?php esc_html_e( 'Adres IP', 'studio-a7-odstap' ); ?></th>
			<td style="padding:10px 16px;border-bottom:1px solid #f0f0f0;font-family:monospace;"><?php echo esc_html( $withdrawal->ip_address ); ?></td>
		</tr>
		<tr style="background-color:#fafafa;">
			<th style="padding:10px 16px;text-align:left;font-weight:600;color:#555;"><?php esc_html_e( 'Powód odstąpienia', 'studio-a7-odstap' ); ?></th>
			<td style="padding:10px 16px;">
				<?php
				echo ! empty( $withdrawal->reason )
					? esc_html( $withdrawal->reason )
					: '<em style="color:#999;">' . esc_html__( 'Nie podano', 'studio-a7-odstap' ) . '</em>';
				?>
			</td>
		</tr>
	</tbody>
</table>

<p style="margin:24px 0;">
	<a href="<?php echo esc_url( $admin_url ); ?>"
		style="display:inline-block;background-color:#2271b1;color:#fff;text-decoration:none;padding:10px 20px;border-radius:4px;font-size:13px;font-weight:600;">
		<?php esc_html_e( 'Przejdź do panelu zgłoszeń', 'studio-a7-odstap' ); ?>
	</a>
</p>

<?php do_action( 'woocommerce_email_footer', $email ); ?>
