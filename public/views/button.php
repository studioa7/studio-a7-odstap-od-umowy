<?php
/**
 * Widok: Przycisk odstąpienia od umowy (strona szczegółów zamówienia).
 *
 * @var \WC_Order       $order          Obiekt zamówienia.
 * @var true|\WP_Error  $can_withdraw   Wynik sprawdzenia kwalifikowalności.
 * @var int             $days_remaining Pozostałe dni na odstąpienie.
 *
 * @package StudioA7_Withdrawal
 */

defined('ABSPATH') || exit;

$button_label = esc_html(get_option('a7w_button_label', __('Odstąp od umowy tutaj', 'studio-a7-odstap')));
$show_days = 'yes' === get_option('a7w_show_days_remaining', 'yes');

if (true === $can_withdraw):
	?>

	<section class="a7w-withdrawal-section" id="a7w-section-<?php echo esc_attr($order->get_id()); ?>">
		<div class="a7w-withdrawal-box a7w-withdrawal-box--active">
			<div class="a7w-withdrawal-icon">
				<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
					stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" />
					<path d="M3 3v5h5" />
					<line x1="12" y1="9" x2="12" y2="13" />
					<line x1="12" y1="17" x2="12.01" y2="17" />
				</svg>
			</div>
			<div class="a7w-withdrawal-content">
				<p class="a7w-withdrawal-title">
					<?php esc_html_e('Prawo do odstąpienia od umowy', 'studio-a7-odstap'); ?>
				</p>
				<?php if ($show_days && $days_remaining > 0): ?>
					<p class="a7w-withdrawal-timer">
						<?php
						printf(
							/* translators: %d: liczba dni */
							esc_html(
								_n(
									'Możesz odstąpić jeszcze przez %d dzień.',
									'Możesz odstąpić jeszcze przez %d dni.',
									$days_remaining,
									'studio-a7-odstap'
								)
							),
							(int) $days_remaining
						);
						?>
					</p>
				<?php endif; ?>
			</div>
			<button type="button" class="a7w-btn a7w-btn--primary a7w-open-modal"
				data-order-id="<?php echo esc_attr($order->get_id()); ?>"
				data-nonce="<?php echo esc_attr(wp_create_nonce('a7w_step1_' . $order->get_id())); ?>"
				id="a7w-btn-<?php echo esc_attr($order->get_id()); ?>">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
					stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
					<polyline points="9 14 4 9 9 4" />
					<path d="M20 20v-7a4 4 0 0 0-4-4H4" />
				</svg>
				<?php echo $button_label; // phpcs:ignore ?>
			</button>
		</div>
	</section>

	<?php
elseif (is_wp_error($can_withdraw)):

	// Sprawdź jakiego rodzaju błąd – nie pokazuj komunikatu dla błędu uprawnień
	$error_code = $can_withdraw->get_error_code();
	if ('not_owner' !== $error_code):
		?>

		<section class="a7w-withdrawal-section">
			<div
				class="a7w-withdrawal-box a7w-withdrawal-box--<?php echo 'already_withdrawn' === $error_code ? 'done' : 'expired'; ?>">
				<div class="a7w-withdrawal-icon">
					<?php if ('already_withdrawn' === $error_code): ?>
						<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
							stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
							<polyline points="22 4 12 14.01 9 11.01" />
						</svg>
					<?php else: ?>
						<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
							stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<circle cx="12" cy="12" r="10" />
							<line x1="12" y1="8" x2="12" y2="12" />
							<line x1="12" y1="16" x2="12.01" y2="16" />
						</svg>
					<?php endif; ?>
				</div>
				<div class="a7w-withdrawal-content">
					<p class="a7w-withdrawal-title">
						<?php esc_html_e('Odstąpienie od umowy', 'studio-a7-odstap'); ?>
					</p>
					<p class="a7w-withdrawal-message">
						<?php echo esc_html($can_withdraw->get_error_message()); ?>
					</p>
				</div>
			</div>
		</section>

		<?php
	endif;
endif;
