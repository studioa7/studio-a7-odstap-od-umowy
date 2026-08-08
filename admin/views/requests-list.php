<?php
/**
 * Widok: Lista zgłoszeń odstąpień od umowy.
 *
 * @var array{items: array, total: int} $data    Dane z bazy.
 * @var string                          $status  Aktywny filtr statusu.
 * @var string                          $search  Wyszukiwana fraza.
 * @var int                             $page    Aktualna strona.
 *
 * @package StudioA7_Withdrawal
 */

defined( 'ABSPATH' ) || exit;

if ( ! current_user_can( 'manage_woocommerce' ) ) {
	wp_die( esc_html__( 'Brak uprawnień.', 'studio-a7-odstap' ) );
}

$items     = $data['items'];
$total     = $data['total'];
$per_page  = 20;
$num_pages = ceil( $total / $per_page );

$status_labels = [
	''          => __( 'Wszystkie', 'studio-a7-odstap' ),
	'pending'   => __( 'Oczekujące', 'studio-a7-odstap' ),
	'confirmed' => __( 'Potwierdzone', 'studio-a7-odstap' ),
	'rejected'  => __( 'Odrzucone', 'studio-a7-odstap' ),
];

$export_url = wp_nonce_url(
	add_query_arg( [ 'a7w_export' => 'csv' ], admin_url( 'admin.php?page=a7w-requests' ) ),
	'a7w_export_csv'
);
?>

<div class="wrap a7w-admin-wrap">

	<div class="a7w-admin-header">
		<div class="a7w-admin-header__logo">
			<span class="a7w-admin-header__brand">Studio A7</span>
		</div>
		<h1 class="a7w-admin-header__title">
			<?php esc_html_e( 'Odstąpienia od umowy', 'studio-a7-odstap' ); ?>
			<span class="a7w-badge"><?php echo esc_html( $total ); ?></span>
		</h1>
		<a href="<?php echo esc_url( $export_url ); ?>" class="a7w-btn-export button">
			<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
			<?php esc_html_e( 'Eksport CSV', 'studio-a7-odstap' ); ?>
		</a>
	</div>

	<!-- Filtry statusów -->
	<ul class="a7w-status-filters subsubsub">
		<?php foreach ( $status_labels as $key => $label ) :
			$url = add_query_arg( [ 'page' => 'a7w-requests', 'status' => $key, 'paged' => 1 ], admin_url( 'admin.php' ) );
		?>
			<li>
				<a href="<?php echo esc_url( $url ); ?>"
				   class="<?php echo $status === $key ? 'current' : ''; ?>">
					<?php echo esc_html( $label ); ?>
				</a>
				<?php echo $key !== array_key_last( $status_labels ) ? '|' : ''; ?>
			</li>
		<?php endforeach; ?>
	</ul>

	<!-- Wyszukiwarka -->
	<form method="get" class="a7w-search-form">
		<input type="hidden" name="page" value="a7w-requests">
		<input type="hidden" name="status" value="<?php echo esc_attr( $status ); ?>">
		<input
			type="search"
			name="s"
			class="a7w-search-input"
			value="<?php echo esc_attr( $search ); ?>"
			placeholder="<?php esc_attr_e( 'Szukaj po emailu, nazwisku, nr zamówienia…', 'studio-a7-odstap' ); ?>"
		>
		<button type="submit" class="button"><?php esc_html_e( 'Szukaj', 'studio-a7-odstap' ); ?></button>
		<?php if ( $search ) : ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=a7w-requests' ) ); ?>" class="button">
				<?php esc_html_e( 'Wyczyść', 'studio-a7-odstap' ); ?>
			</a>
		<?php endif; ?>
	</form>

	<!-- Tabela zgłoszeń -->
	<?php if ( empty( $items ) ) : ?>

		<div class="a7w-empty-state">
			<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
				<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
				<polyline points="14 2 14 8 20 8"/>
			</svg>
			<p><?php esc_html_e( 'Brak zgłoszeń spełniających kryteria.', 'studio-a7-odstap' ); ?></p>
		</div>

	<?php else : ?>

		<table class="a7w-table widefat striped">
			<thead>
				<tr>
					<th class="check-column"><input type="checkbox" disabled></th>
					<th><?php esc_html_e( 'ID', 'studio-a7-odstap' ); ?></th>
					<th><?php esc_html_e( 'Zamówienie', 'studio-a7-odstap' ); ?></th>
					<th><?php esc_html_e( 'Klient', 'studio-a7-odstap' ); ?></th>
					<th><?php esc_html_e( 'E-mail', 'studio-a7-odstap' ); ?></th>
					<th><?php esc_html_e( 'Status', 'studio-a7-odstap' ); ?></th>
					<th><?php esc_html_e( 'Data złożenia', 'studio-a7-odstap' ); ?></th>
					<th><?php esc_html_e( 'Data potwierdzenia', 'studio-a7-odstap' ); ?></th>
					<th><?php esc_html_e( 'Powód', 'studio-a7-odstap' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $items as $row ) :
					$order     = wc_get_order( $row->order_id );
					$order_url = $order ? $order->get_edit_order_url() : '#';
					$status_class = [
						'pending'   => 'a7w-status-pending',
						'confirmed' => 'a7w-status-confirmed',
						'rejected'  => 'a7w-status-rejected',
					];
					$status_text = [
						'pending'   => __( 'Oczekuje', 'studio-a7-odstap' ),
						'confirmed' => __( 'Potwierdzone', 'studio-a7-odstap' ),
						'rejected'  => __( 'Odrzucone', 'studio-a7-odstap' ),
					];
				?>
					<tr>
						<th class="check-column"><input type="checkbox" disabled></th>
						<td><code>#<?php echo esc_html( $row->id ); ?></code></td>
						<td>
							<a href="<?php echo esc_url( $order_url ); ?>" target="_blank">
								#<?php echo esc_html( $row->order_id ); ?>
							</a>
						</td>
						<td><?php echo esc_html( $row->customer_name ); ?></td>
						<td>
							<a href="mailto:<?php echo esc_attr( $row->customer_email ); ?>">
								<?php echo esc_html( $row->customer_email ); ?>
							</a>
						</td>
						<td>
							<span class="a7w-status-badge <?php echo esc_attr( $status_class[ $row->status ] ?? '' ); ?>">
								<?php echo esc_html( $status_text[ $row->status ] ?? $row->status ); ?>
							</span>
						</td>
						<td><?php echo esc_html( wp_date( 'd.m.Y H:i', strtotime( $row->created_at ) ) ); ?></td>
						<td>
							<?php echo $row->confirmed_at
								? esc_html( wp_date( 'd.m.Y H:i', strtotime( $row->confirmed_at ) ) )
								: '<span class="a7w-dash">—</span>';
							?>
						</td>
						<td>
							<?php echo ! empty( $row->reason )
								? '<span title="' . esc_attr( $row->reason ) . '">' . esc_html( wp_trim_words( $row->reason, 8 ) ) . '</span>'
								: '<span class="a7w-dash">—</span>';
							?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<!-- Paginacja -->
		<?php if ( $num_pages > 1 ) : ?>
			<div class="a7w-pagination tablenav-pages">
				<?php
				$base_url = add_query_arg(
					[ 'page' => 'a7w-requests', 'status' => $status, 's' => $search ],
					admin_url( 'admin.php' )
				);

				echo paginate_links( [ // phpcs:ignore
					'base'      => $base_url . '%_%',
					'format'    => '&paged=%#%',
					'current'   => max( 1, $page ),
					'total'     => $num_pages,
					'prev_text' => '&laquo;',
					'next_text' => '&raquo;',
				] );
				?>
			</div>
		<?php endif; ?>

	<?php endif; ?>

</div>
