<?php
/**
 * Widok: Strona ustawień wtyczki Studio A7 – Odstąp od umowy.
 *
 * @package StudioA7_Withdrawal
 */

defined( 'ABSPATH' ) || exit;

if ( ! current_user_can( 'manage_woocommerce' ) ) {
	wp_die( esc_html__( 'Brak uprawnień.', 'studio-a7-odstap' ) );
}

// Pobierz dostępne statusy zamówień WooCommerce
$wc_statuses = wc_get_order_statuses();

// Pobierz kategorie produktów
$product_cats = get_terms(
	array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => false,
		'number'     => 200,
	)
);

// Aktualne wartości
$days                = (int) get_option( 'a7w_withdrawal_days', 14 );
$allowed             = (array) get_option( 'a7w_allowed_statuses', array( 'wc-completed', 'wc-processing' ) );
$button_label        = get_option( 'a7w_button_label', __( 'Odstąp od umowy', 'studio-a7-odstap' ) );
$show_days           = get_option( 'a7w_show_days_remaining', 'yes' );
$exc_virtual         = get_option( 'a7w_exclude_virtual', 'yes' );
$exc_download        = get_option( 'a7w_exclude_downloadable', 'yes' );
$exc_cats            = (array) get_option( 'a7w_excluded_categories', array() );
$notify_admin        = get_option( 'a7w_notify_admin', 'yes' );
$admin_email         = get_option( 'a7w_admin_email', get_option( 'admin_email' ) );
$status_after        = get_option( 'a7w_order_status_after_withdrawal', '' );
$require_reason      = get_option( 'a7w_require_reason', 'no' );
$retention_months    = (int) get_option( 'a7w_retention_months', 24 );
$delete_on_uninstall = get_option( 'a7w_delete_data_on_uninstall', 'no' );
?>

<div class="wrap a7w-admin-wrap">
	<div class="a7w-admin-header">
		<div class="a7w-admin-header__logo">
			<span class="a7w-admin-header__brand">Studio A7</span>
		</div>
		<h1 class="a7w-admin-header__title">
			<?php esc_html_e( 'Ustawienia – Odstąp od umowy', 'studio-a7-odstap' ); ?>
		</h1>
		<span class="a7w-version">v<?php echo esc_html( A7W_VERSION ); ?></span>
	</div>

	<?php settings_errors( 'a7w_settings' ); ?>

	<form method="post" action="options.php">
		<?php settings_fields( 'a7w_settings' ); ?>

		<div class="a7w-settings-grid">

			<!-- ============================================================
				SEKCJA 1: Ogólne
				============================================================ -->
			<div class="a7w-card">
				<div class="a7w-card__header">
					<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
						stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<circle cx="12" cy="12" r="3" />
						<path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14" />
					</svg>
					<h2><?php esc_html_e( 'Ustawienia ogólne', 'studio-a7-odstap' ); ?></h2>
				</div>
				<div class="a7w-card__body">

					<div class="a7w-field">
						<label for="a7w_withdrawal_days" class="a7w-field__label">
							<?php esc_html_e( 'Termin na odstąpienie (dni)', 'studio-a7-odstap' ); ?>
						</label>
						<div class="a7w-field__control">
							<input type="number" id="a7w_withdrawal_days" name="a7w_withdrawal_days"
								value="<?php echo esc_attr( $days ); ?>" min="1" max="30"
								class="a7w-input a7w-input--short">
						</div>
						<p class="a7w-field__desc">
							<?php esc_html_e( 'Zgodnie z przepisami UE standardowy termin wynosi 14 dni. Zalecamy nie zmieniać tej wartości bez konsultacji prawnej.', 'studio-a7-odstap' ); ?>
						</p>
					</div>

					<div class="a7w-field">
						<label class="a7w-field__label">
							<?php esc_html_e( 'Kwalifikujące statusy zamówień', 'studio-a7-odstap' ); ?>
						</label>
						<div class="a7w-field__control a7w-checkboxes">
							<?php foreach ( $wc_statuses as $status_key => $status_name ) : ?>
								<label class="a7w-checkbox-label">
									<input type="checkbox" name="a7w_allowed_statuses[]"
										value="<?php echo esc_attr( $status_key ); ?>" <?php checked( in_array( $status_key, $allowed, true ) ); ?>>
									<?php echo esc_html( $status_name ); ?>
								</label>
							<?php endforeach; ?>
						</div>
						<p class="a7w-field__desc">
							<?php esc_html_e( 'Przycisk "Odstąp od umowy" będzie widoczny tylko dla zamówień w wybranych statusach.', 'studio-a7-odstap' ); ?>
						</p>
					</div>

					<div class="a7w-field">
						<label for="a7w_button_label" class="a7w-field__label">
							<?php esc_html_e( 'Treść przycisku', 'studio-a7-odstap' ); ?>
						</label>
						<div class="a7w-field__control">
							<input type="text" id="a7w_button_label" name="a7w_button_label"
								value="<?php echo esc_attr( $button_label ); ?>" class="a7w-input a7w-input--wide">
						</div>
					</div>

					<div class="a7w-field">
						<label class="a7w-field__label">
							<?php esc_html_e( 'Pokaż licznik pozostałych dni', 'studio-a7-odstap' ); ?>
						</label>
						<label class="a7w-toggle">
							<input type="checkbox" name="a7w_show_days_remaining" value="yes" <?php checked( $show_days, 'yes' ); ?>>
							<span class="a7w-toggle__slider"></span>
							<span
								class="a7w-toggle__label"><?php esc_html_e( 'Wyświetlaj informację o pozostałych dniach pod przyciskiem', 'studio-a7-odstap' ); ?></span>
						</label>
					</div>

					<div class="a7w-field">
						<label for="a7w_order_status_after_withdrawal" class="a7w-field__label">
							<?php esc_html_e( 'Status zamówienia po odstąpieniu', 'studio-a7-odstap' ); ?>
						</label>
						<div class="a7w-field__control">
							<select id="a7w_order_status_after_withdrawal" name="a7w_order_status_after_withdrawal"
								class="a7w-select">
								<option value=""><?php esc_html_e( '— Nie zmieniaj statusu —', 'studio-a7-odstap' ); ?>
								</option>
								<?php foreach ( $wc_statuses as $status_key => $status_name ) : ?>
									<option value="<?php echo esc_attr( str_replace( 'wc-', '', $status_key ) ); ?>" <?php selected( $status_after, str_replace( 'wc-', '', $status_key ) ); ?>>
										<?php echo esc_html( $status_name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
						<p class="a7w-field__desc">
							<?php esc_html_e( 'Opcjonalnie: automatyczna zmiana statusu zamówienia po złożeniu oświadczenia o odstąpieniu.', 'studio-a7-odstap' ); ?>
						</p>
					</div>

				</div>
			</div>

			<!-- ============================================================
				SEKCJA 2: Formularz
				============================================================ -->
			<div class="a7w-card">
				<div class="a7w-card__header">
					<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
						stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
						<polyline points="14 2 14 8 20 8" />
						<line x1="16" y1="13" x2="8" y2="13" />
						<line x1="16" y1="17" x2="8" y2="17" />
						<polyline points="10 9 9 9 8 9" />
					</svg>
					<h2><?php esc_html_e( 'Formularz', 'studio-a7-odstap' ); ?></h2>
				</div>
				<div class="a7w-card__body">

					<div class="a7w-field">
						<label class="a7w-field__label">
							<?php esc_html_e( 'Pole powodu odstąpienia', 'studio-a7-odstap' ); ?>
						</label>
						<div class="a7w-radio-group">
							<label class="a7w-radio-label">
								<input type="radio" name="a7w_require_reason" value="no" <?php checked( $require_reason, 'no' ); ?>>
								<?php esc_html_e( 'Opcjonalne (zalecane)', 'studio-a7-odstap' ); ?>
							</label>
							<label class="a7w-radio-label">
								<input type="radio" name="a7w_require_reason" value="yes" <?php checked( $require_reason, 'yes' ); ?>>
								<?php esc_html_e( 'Obowiązkowe', 'studio-a7-odstap' ); ?>
							</label>
						</div>
						<p class="a7w-field__desc">
							<?php esc_html_e( 'Prawo UE zabrania uzależniać skuteczność odstąpienia od podania powodu. Pole opcjonalne to dobra praktyka zbierania feedbacku.', 'studio-a7-odstap' ); ?>
						</p>
					</div>

				</div>
			</div>

			<!-- ============================================================
				SEKCJA 3: Wyjątki produktów
				============================================================ -->
			<div class="a7w-card">
				<div class="a7w-card__header">
					<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
						stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<circle cx="12" cy="12" r="10" />
						<line x1="4.93" y1="4.93" x2="19.07" y2="19.07" />
					</svg>
					<h2><?php esc_html_e( 'Wyjątki produktów', 'studio-a7-odstap' ); ?></h2>
				</div>
				<div class="a7w-card__body">

					<div class="a7w-notice a7w-notice--info">
						<?php esc_html_e( 'Art. 38 Ustawy o prawach konsumenta wymienia kategorie produktów wyłączonych z prawa odstąpienia od umowy.', 'studio-a7-odstap' ); ?>
					</div>

					<div class="a7w-field">
						<label class="a7w-toggle">
							<input type="checkbox" name="a7w_exclude_virtual" value="yes" <?php checked( $exc_virtual, 'yes' ); ?>>
							<span class="a7w-toggle__slider"></span>
							<span class="a7w-toggle__label">
								<strong><?php esc_html_e( 'Wyklucz produkty wirtualne/usługi', 'studio-a7-odstap' ); ?></strong>
								<span><?php esc_html_e( 'Usuwa przycisk dla zamówień zawierających produkty wirtualne.', 'studio-a7-odstap' ); ?></span>
							</span>
						</label>
					</div>

					<div class="a7w-field">
						<label class="a7w-toggle">
							<input type="checkbox" name="a7w_exclude_downloadable" value="yes" <?php checked( $exc_download, 'yes' ); ?>>
							<span class="a7w-toggle__slider"></span>
							<span class="a7w-toggle__label">
								<strong><?php esc_html_e( 'Wyklucz treści cyfrowe (po pobraniu)', 'studio-a7-odstap' ); ?></strong>
								<span><?php esc_html_e( 'Usuwa przycisk jeśli klient pobrał już plik cyfrowy.', 'studio-a7-odstap' ); ?></span>
							</span>
						</label>
					</div>

					<?php if ( ! is_wp_error( $product_cats ) && ! empty( $product_cats ) ) : ?>
						<div class="a7w-field">
							<label class="a7w-field__label">
								<?php esc_html_e( 'Wykluczone kategorie produktów', 'studio-a7-odstap' ); ?>
							</label>
							<div class="a7w-field__control a7w-checkboxes a7w-checkboxes--grid">
								<?php foreach ( $product_cats as $cat ) : ?>
									<label class="a7w-checkbox-label">
										<input type="checkbox" name="a7w_excluded_categories[]"
											value="<?php echo esc_attr( $cat->term_id ); ?>" <?php checked( in_array( (string) $cat->term_id, array_map( 'strval', $exc_cats ), true ) ); ?>>
										<?php echo esc_html( $cat->name ); ?>
										<span class="a7w-cat-count">(<?php echo esc_html( $cat->count ); ?>)</span>
									</label>
								<?php endforeach; ?>
							</div>
							<p class="a7w-field__desc">
								<?php esc_html_e( 'Np. "Towary szyte na miarę", "Produkty na zamówienie", "Żywność i napoje".', 'studio-a7-odstap' ); ?>
							</p>
						</div>
					<?php endif; ?>

				</div>
			</div>

			<!-- ============================================================
				SEKCJA 4: Powiadomienia
				============================================================ -->
			<div class="a7w-card">
				<div class="a7w-card__header">
					<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
						stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path
							d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.72 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.63 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.6a16 16 0 0 0 5.55 5.55l.96-.96a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21 16.92z" />
					</svg>
					<h2><?php esc_html_e( 'Powiadomienia e-mail', 'studio-a7-odstap' ); ?></h2>
				</div>
				<div class="a7w-card__body">

					<div class="a7w-field">
						<label class="a7w-toggle">
							<input type="checkbox" name="a7w_notify_admin" value="yes" <?php checked( $notify_admin, 'yes' ); ?>>
							<span class="a7w-toggle__slider"></span>
							<span class="a7w-toggle__label">
								<strong><?php esc_html_e( 'Powiadamiaj administratora o nowych wnioskach', 'studio-a7-odstap' ); ?></strong>
							</span>
						</label>
					</div>

					<div class="a7w-field">
						<label for="a7w_admin_email" class="a7w-field__label">
							<?php esc_html_e( 'Email administratora / obsługi klienta', 'studio-a7-odstap' ); ?>
						</label>
						<div class="a7w-field__control">
							<input type="email" id="a7w_admin_email" name="a7w_admin_email"
								value="<?php echo esc_attr( $admin_email ); ?>" class="a7w-input a7w-input--wide">
						</div>
						<p class="a7w-field__desc">
							<?php esc_html_e( 'Możesz wpisać wiele adresów rozdzielonych przecinkiem.', 'studio-a7-odstap' ); ?>
						</p>
					</div>

					<div class="a7w-notice a7w-notice--tip">
						<?php
						printf(
							/* translators: %s: link do ustawień emaili WooCommerce */
							esc_html__( 'Treść emaila do klienta możesz edytować w %s.', 'studio-a7-odstap' ),
							'<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=email' ) ) . '">' . esc_html__( 'WooCommerce → Ustawienia → Email', 'studio-a7-odstap' ) . '</a>'
						);
						?>
					</div>

					<div class="a7w-field">
						<label for="a7w_retention_months" class="a7w-field__label">
							<?php esc_html_e( 'Okres przechowywania potwierdzonych zgłoszeń (miesiące)', 'studio-a7-odstap' ); ?>
						</label>
						<div class="a7w-field__control">
							<input type="number" id="a7w_retention_months" name="a7w_retention_months"
								value="<?php echo esc_attr( $retention_months ); ?>" min="1" max="120"
								class="a7w-input a7w-input--short">
						</div>
						<p class="a7w-field__desc">
							<?php esc_html_e( 'Oczekujące zgłoszenia są usuwane po 24 godzinach. Potwierdzone zgłoszenia wraz z ograniczonymi danymi dowodowymi są automatycznie usuwane po tym okresie.', 'studio-a7-odstap' ); ?>
						</p>
					</div>

					<div class="a7w-field">
						<label class="a7w-toggle">
							<input type="checkbox" name="a7w_delete_data_on_uninstall" value="yes" <?php checked( $delete_on_uninstall, 'yes' ); ?>>
							<span class="a7w-toggle__slider"></span>
							<span class="a7w-toggle__label">
								<strong><?php esc_html_e( 'Usuń dane podczas odinstalowania', 'studio-a7-odstap' ); ?></strong>
								<span><?php esc_html_e( 'Domyślnie dane są zachowywane jako dowód zgłoszeń. Włącz tę opcję tylko, gdy nie musisz ich zachować.', 'studio-a7-odstap' ); ?></span>
							</span>
						</label>
					</div>

				</div>
			</div>

		</div><!-- /.a7w-settings-grid -->

		<div class="a7w-settings-footer">
			<?php submit_button( __( 'Zapisz ustawienia', 'studio-a7-odstap' ), 'primary a7w-save-btn', 'submit', false ); ?>
		</div>

	</form>
</div>