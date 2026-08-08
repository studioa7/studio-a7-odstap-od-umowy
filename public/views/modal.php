<?php
/**
 * Widok: Modal z formularzem odstąpienia (krok 1 i krok 2).
 *
 * Modal zawiera dwa kroki:
 *  - Krok 1: Formularz z danymi i opcjonalnym powodem
 *  - Krok 2: Potwierdzenie decyzji (wymóg dyrektywy UE 2023/2673)
 *  - Sukces: Komunikat po złożeniu oświadczenia
 *
 * @var \WC_Order $order  Obiekt zamówienia.
 * @var string    $nonce1 Nonce dla kroku 1.
 *
 * @package StudioA7_Withdrawal
 */

defined('ABSPATH') || exit;

$order_id = $order->get_id();
$customer_name = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
$require_reason = 'yes' === get_option('a7w_require_reason', 'no');
$withdrawal_days = max(1, absint(get_option('a7w_withdrawal_days', 14)));
?>

<div class="a7w-modal-overlay" id="a7w-modal-<?php echo esc_attr($order_id); ?>" role="dialog" aria-modal="true"
	aria-labelledby="a7w-modal-title-<?php echo esc_attr($order_id); ?>" hidden>
	<div class="a7w-modal">

		<!-- Nagłówek modala -->
		<div class="a7w-modal__header">
			<div class="a7w-modal__title-wrap">
				<div class="a7w-modal__icon">
					<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
						stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" />
						<path d="M3 3v5h5" />
					</svg>
				</div>

				<div class="a7w-form__group">
					<fieldset>
						<legend class="a7w-form__label"><?php esc_html_e('Zakres odstąpienia', 'studio-a7-odstap'); ?>
						</legend>
						<p class="a7w-form__optional">
							<?php esc_html_e('Wybierz towary i ilości. Aby odstąpić od całego zamówienia, wybierz wszystkie pozycje w pełnej ilości.', 'studio-a7-odstap'); ?>
						</p>
						<?php foreach ($order->get_items() as $item_id => $item): ?>
							<label class="a7w-form__label" for="a7w-item-<?php echo esc_attr($item_id); ?>">
								<?php echo esc_html($item->get_name()); ?>
								<input type="number" id="a7w-item-<?php echo esc_attr($item_id); ?>"
									name="items[<?php echo esc_attr($item_id); ?>]" min="0"
									max="<?php echo esc_attr($item->get_quantity()); ?>" value="0"
									class="a7w-form__input">
							</label>
						<?php endforeach; ?>
					</fieldset>
				</div>
				<h2 class="a7w-modal__title" id="a7w-modal-title-<?php echo esc_attr($order_id); ?>">
					<?php esc_html_e('Odstąpienie od umowy', 'studio-a7-odstap'); ?>
				</h2>
			</div>
			<button type="button" class="a7w-modal__close a7w-close-modal"
				aria-label="<?php esc_attr_e('Zamknij', 'studio-a7-odstap'); ?>">
				<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
					stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
					<line x1="18" y1="6" x2="6" y2="18" />
					<line x1="6" y1="6" x2="18" y2="18" />
				</svg>
			</button>
		</div>

		<!-- Wskaźnik kroków -->
		<div class="a7w-steps" aria-hidden="true">
			<div class="a7w-step a7w-step--active" data-step="1">
				<span class="a7w-step__num">1</span>
				<span class="a7w-step__label"><?php esc_html_e('Oświadczenie', 'studio-a7-odstap'); ?></span>
			</div>
			<div class="a7w-step__connector"></div>
			<div class="a7w-step" data-step="2">
				<span class="a7w-step__num">2</span>
				<span class="a7w-step__label"><?php esc_html_e('Potwierdzenie', 'studio-a7-odstap'); ?></span>
			</div>
		</div>

		<!-- Krok 1: Formularz -->
		<div class="a7w-modal__body a7w-step-panel" data-panel="1">
			<p class="a7w-modal__intro">
				<?php
				printf(
					/* translators: 1: numer zamówienia, 2: liczba dni */
					esc_html__('Składasz oświadczenie o odstąpieniu od umowy sprzedaży dotyczącej zamówienia nr %1$s. Termin skonfigurowany przez sklep wynosi %2$d dni.', 'studio-a7-odstap'),
					'<strong>#' . esc_html($order->get_order_number()) . '</strong>',
					absint($withdrawal_days)
				);
				?>
			</p>

			<form class="a7w-form" id="a7w-form-step1-<?php echo esc_attr($order_id); ?>" novalidate>
				<input type="hidden" name="action" value="a7w_step1">
				<input type="hidden" name="order_id" value="<?php echo esc_attr($order_id); ?>">
				<input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce1); ?>">

				<!-- Dane klienta (readonly - pre-fill) -->
				<div class="a7w-form__group">
					<label class="a7w-form__label" for="a7w-name-<?php echo esc_attr($order_id); ?>">
						<?php esc_html_e('Imię i nazwisko', 'studio-a7-odstap'); ?>
					</label>
					<input type="text" id="a7w-name-<?php echo esc_attr($order_id); ?>" class="a7w-form__input"
						value="<?php echo esc_attr($customer_name); ?>" readonly aria-readonly="true">
				</div>

				<div class="a7w-form__group">
					<label class="a7w-form__label" for="a7w-email-<?php echo esc_attr($order_id); ?>">
						<?php esc_html_e('Adres e-mail', 'studio-a7-odstap'); ?>
					</label>
					<input type="email" id="a7w-email-<?php echo esc_attr($order_id); ?>" class="a7w-form__input"
						value="<?php echo esc_attr($order->get_billing_email()); ?>" readonly aria-readonly="true">
				</div>

				<!-- Powód (opcjonalny) -->
				<div class="a7w-form__group">
					<label class="a7w-form__label" for="a7w-reason-<?php echo esc_attr($order_id); ?>">
						<?php esc_html_e('Powód odstąpienia', 'studio-a7-odstap'); ?>
						<?php if (!$require_reason): ?>
							<span
								class="a7w-form__optional"><?php esc_html_e('(opcjonalnie)', 'studio-a7-odstap'); ?></span>
						<?php endif; ?>
					</label>
					<textarea id="a7w-reason-<?php echo esc_attr($order_id); ?>" name="reason"
						class="a7w-form__textarea" rows="3"
						placeholder="<?php esc_attr_e('Np. produkt nie spełnił moich oczekiwań…', 'studio-a7-odstap'); ?>"
						<?php echo $require_reason ? 'required' : ''; ?>></textarea>
				</div>

				<!-- Checkbox zgody -->
				<div class="a7w-form__group a7w-form__group--checkbox">
					<label class="a7w-checkbox" for="a7w-consent-<?php echo esc_attr($order_id); ?>">
						<input type="checkbox" id="a7w-consent-<?php echo esc_attr($order_id); ?>" name="consent"
							required class="a7w-checkbox__input">
						<span class="a7w-checkbox__box" aria-hidden="true"></span>
						<span class="a7w-checkbox__text">
							<?php esc_html_e('Oświadczam, że zapoznałam/em się z warunkami odstąpienia od umowy i chcę skorzystać z przysługującego mi prawa.', 'studio-a7-odstap'); ?>
						</span>
					</label>
				</div>

				<!-- Komunikat błędu -->
				<div class="a7w-form__error" id="a7w-error-step1-<?php echo esc_attr($order_id); ?>" role="alert"
					hidden></div>

				<!-- Przyciski -->
				<div class="a7w-form__actions">
					<button type="button" class="a7w-btn a7w-btn--ghost a7w-close-modal">
						<?php esc_html_e('Anuluj', 'studio-a7-odstap'); ?>
					</button>
					<button type="submit" class="a7w-btn a7w-btn--primary"
						id="a7w-submit-step1-<?php echo esc_attr($order_id); ?>">
						<?php esc_html_e('Dalej', 'studio-a7-odstap'); ?>
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
							stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
							<polyline points="9 18 15 12 9 6" />
						</svg>
					</button>
				</div>
			</form>
		</div>

		<!-- Krok 2: Potwierdzenie (wymóg dyrektywy UE) -->
		<div class="a7w-modal__body a7w-step-panel" data-panel="2" hidden>
			<div class="a7w-confirm-box">
				<div class="a7w-confirm-box__icon">
					<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
						stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
						<path
							d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
						<line x1="12" y1="9" x2="12" y2="13" />
						<line x1="12" y1="17" x2="12.01" y2="17" />
					</svg>
				</div>
				<h3 class="a7w-confirm-box__title">
					<?php esc_html_e('Potwierdź odstąpienie od umowy', 'studio-a7-odstap'); ?>
				</h3>
				<p class="a7w-confirm-box__text">
					<?php esc_html_e('Czy na pewno chcesz odstąpić od umowy? Po potwierdzeniu oświadczenie zostanie zarejestrowane i wysłany zostanie email potwierdzający z datą i godziną złożenia oświadczenia.', 'studio-a7-odstap'); ?>
				</p>
				<p class="a7w-confirm-box__legal">
					<?php esc_html_e('Tej operacji nie można cofnąć. Prawo do odstąpienia zostanie wykonane zgodnie z art. 27 Ustawy z dnia 30 maja 2014 r. o prawach konsumenta.', 'studio-a7-odstap'); ?>
				</p>
			</div>

			<!-- Ukryte pole tokena -->
			<input type="hidden" id="a7w-token-<?php echo esc_attr($order_id); ?>" name="token" value="">
			<input type="hidden" name="action" value="a7w_step2">
			<input type="hidden" id="a7w-nonce2-<?php echo esc_attr($order_id); ?>" name="_wpnonce" value="">

			<!-- Komunikat błędu -->
			<div class="a7w-form__error" id="a7w-error-step2-<?php echo esc_attr($order_id); ?>" role="alert" hidden>
			</div>

			<!-- Przyciski -->
			<div class="a7w-form__actions">
				<button type="button" class="a7w-btn a7w-btn--ghost a7w-back-to-step1"
					data-order-id="<?php echo esc_attr($order_id); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
						stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
						<polyline points="15 18 9 12 15 6" />
					</svg>
					<?php esc_html_e('Wróć', 'studio-a7-odstap'); ?>
				</button>
				<button type="button" class="a7w-btn a7w-btn--danger a7w-confirm-step2"
					data-order-id="<?php echo esc_attr($order_id); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
						stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
						<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
						<polyline points="22 4 12 14.01 9 11.01" />
					</svg>
					<?php esc_html_e('Potwierdź odstąpienie od umowy', 'studio-a7-odstap'); ?>
				</button>
			</div>
		</div>

		<!-- Stan sukcesu -->
		<div class="a7w-modal__body a7w-step-panel" data-panel="success" hidden>
			<div class="a7w-success">
				<div class="a7w-success__icon">
					<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none"
						stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
						<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
						<polyline points="22 4 12 14.01 9 11.01" />
					</svg>
				</div>
				<h3 class="a7w-success__title">
					<?php esc_html_e('Oświadczenie złożone pomyślnie', 'studio-a7-odstap'); ?>
				</h3>
				<p class="a7w-success__text">
					<?php esc_html_e('Twoje oświadczenie o odstąpieniu od umowy zostało przyjęte. Na podany adres e-mail wysłaliśmy potwierdzenie z datą i godziną złożenia oświadczenia.', 'studio-a7-odstap'); ?>
				</p>
				<button type="button" class="a7w-btn a7w-btn--primary a7w-close-modal" style="margin-top:8px;">
					<?php esc_html_e('Zamknij', 'studio-a7-odstap'); ?>
				</button>
			</div>
		</div>

		<!-- Stan ładowania -->
		<div class="a7w-modal__loading" id="a7w-loading-<?php echo esc_attr($order_id); ?>" hidden aria-live="polite">
			<div class="a7w-spinner" aria-hidden="true"></div>
			<span><?php esc_html_e('Proszę czekać…', 'studio-a7-odstap'); ?></span>
		</div>

	</div><!-- /.a7w-modal -->
</div><!-- /.a7w-modal-overlay -->