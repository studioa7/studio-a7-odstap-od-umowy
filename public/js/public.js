/**
 * Studio A7 – Odstąp od umowy
 * JavaScript frontendu – obsługa modala, AJAX, walidacja
 *
 * @version 1.0.0
 */

(function ($) {
	'use strict';

	// =========================================================================
	// Stan aplikacji
	// =========================================================================
	const state = {
		currentToken: '',
		currentOrderId: 0,
		currentNonce2: '',
	};

	// =========================================================================
	// Inicjalizacja
	// =========================================================================
	$(
		function () {
			bindOpenModal();
			bindCloseModal();
			bindStep1Submit();
			bindStep2Confirm();
			bindBackToStep1();
			bindKeyboard();
		}
	);

	// =========================================================================
	// Otwarcie modala
	// =========================================================================
	function bindOpenModal() {
		$(document).on(
			'click',
			'.a7w-open-modal, .a7w_withdraw',
			function (e) {
				e.preventDefault();

				let orderId = $(this).data('order-id');

				// Jeśli data-order-id nie istnieje (np. tabela zamówień WC ignoruje własne atrybuty i klasy)
				if (!orderId) {
					const href = $(this).attr('href');
					if (href && href.indexOf('#a7w-modal-') !== -1) {
						orderId = href.split('#a7w-modal-')[1];
					}
				}

				if (!orderId) {
					return;
				}

				state.currentOrderId = orderId;

				const $overlay = $('#a7w-modal-' + orderId);
				if (!$overlay.length) {
					return;
				}

				// Resetuj do kroku 1
				resetModal(orderId);

				// Pokaż overlay
				$overlay.removeAttr('hidden');
				$('body').addClass('a7w-modal-open');
				document.body.style.overflow = 'hidden';

				// Focus na zamknięcie (dostępność)
				setTimeout(
					function () {
						$overlay.find('.a7w-modal__close').trigger('focus');
					},
					50
				);
			}
		);
	}

	// =========================================================================
	// Zamknięcie modala
	// =========================================================================
	function bindCloseModal() {
		$(document).on(
			'click',
			'.a7w-close-modal, .a7w-modal-overlay',
			function (e) {
				// Zamknij tylko jeśli klik na overlay (nie na sam modal)
				if (
					$(this).hasClass('a7w-modal-overlay') &&
					!$(e.target).hasClass('a7w-modal-overlay')
				) {
					return;
				}
				closeModal(state.currentOrderId);
			}
		);
	}

	function closeModal(orderId) {
		const $overlay = $('#a7w-modal-' + orderId);
		$overlay.attr('hidden', '');
		$('body').removeClass('a7w-modal-open');
		document.body.style.overflow = '';

		// Zwróć focus na przycisk który otworzył modal
		$('#a7w-btn-' + orderId).trigger('focus');
	}

	// =========================================================================
	// Obsługa klawiatury (Escape)
	// =========================================================================
	function bindKeyboard() {
		$(document).on(
			'keydown',
			function (e) {
				if ('Escape' === e.key && state.currentOrderId) {
					closeModal(state.currentOrderId);
				}
			}
		);
	}

	// =========================================================================
	// KROK 1 – Przesłanie formularza
	// =========================================================================
	function bindStep1Submit() {
		$(document).on(
			'submit',
			'[id^="a7w-form-step1-"]',
			function (e) {
				e.preventDefault();

				const $form = $(this);
				const orderId = $form.find('[name="order_id"]').val();
				const $error = $('#a7w-error-step1-' + orderId);
				const $overlay = $('#a7w-modal-' + orderId);

				// Walidacja frontendu
				if (!$form.find('[name="consent"]').is(':checked')) {
					showError($error, a7wData.i18n.error_generic || 'Zaznacz wymagane zgody.');
					return;
				}

				const requireReason = $form.find('[name="reason"][required]').length > 0;
				if (requireReason && !$form.find('[name="reason"]').val().trim()) {
					showError($error, 'Podaj powód odstąpienia od umowy.');
					return;
				}

				hideError($error);
				setLoading($overlay, true);

				$.ajax(
					{
						url: a7wData.ajaxUrl,
						method: 'POST',
						data: $form.serialize(),
					}
				)
					.done(
						function (response) {
							setLoading($overlay, false);

							if (response.success && response.token) {
								// Zapisz token i przejdź do kroku 2
								state.currentToken = response.token;

								// Ustaw nonce2 i token w panelu kroku 2
								$('#a7w-token-' + orderId).val(response.token);

								if (response.nonce2) {
									$('#a7w-nonce2-' + orderId).val(response.nonce2);
								}

								goToPanel($overlay, '2');
								updateStepIndicator($overlay, 2);

							} else {
								showError($error, response.message || a7wData.i18n.error_generic);
							}
						}
					)
					.fail(
						function () {
							setLoading($overlay, false);
							showError($error, a7wData.i18n.error_generic);
						}
					);
			}
		);
	}

	// =========================================================================
	// KROK 2 – Potwierdzenie
	// =========================================================================
	function bindStep2Confirm() {
		$(document).on(
			'click',
			'.a7w-confirm-step2',
			function () {
				const orderId = $(this).data('order-id');
				const $error = $('#a7w-error-step2-' + orderId);
				const $overlay = $('#a7w-modal-' + orderId);

				const token = $('#a7w-token-' + orderId).val();
				const nonce2 = $('#a7w-nonce2-' + orderId).val();

				if (!token) {
					showError($error, a7wData.i18n.error_generic);
					return;
				}

				hideError($error);
				setLoading($overlay, true);

				$.ajax(
					{
						url: a7wData.ajaxUrl,
						method: 'POST',
						data: {
							action: 'a7w_step2',
							token: token,
							_wpnonce: nonce2 || wp_nonce_from_dom(orderId),
						},
					}
				)
					.done(
						function (response) {
							setLoading($overlay, false);

							if (response.success) {
								// Pokaż panel sukcesu
								goToPanel($overlay, 'success');
								updateStepIndicator($overlay, 'done');

								// Odśwież stronę po 3 sekundach
								setTimeout(
									function () {
										window.location.reload();
									},
									3500
								);
							} else {
								showError($error, response.message || a7wData.i18n.error_generic);
							}
						}
					)
					.fail(
						function () {
							setLoading($overlay, false);
							showError($error, a7wData.i18n.error_generic);
						}
					);
			}
		);
	}

	// =========================================================================
	// Powrót do kroku 1
	// =========================================================================
	function bindBackToStep1() {
		$(document).on(
			'click',
			'.a7w-back-to-step1',
			function () {
				const orderId = $(this).data('order-id');
				const $overlay = $('#a7w-modal-' + orderId);
				goToPanel($overlay, '1');
				updateStepIndicator($overlay, 1);
			}
		);
	}

	// =========================================================================
	// Helpery – UI
	// =========================================================================

	function goToPanel($overlay, panelId) {
		$overlay.find('.a7w-step-panel').attr('hidden', '');
		$overlay.find('.a7w-modal__loading').attr('hidden', '');
		$overlay.find('[data-panel="' + panelId + '"]').removeAttr('hidden');
	}

	function setLoading($overlay, isLoading) {
		if (isLoading) {
			$overlay.find('.a7w-step-panel').attr('hidden', '');
			$overlay.find('.a7w-modal__loading').removeAttr('hidden');
		} else {
			$overlay.find('.a7w-modal__loading').attr('hidden', '');
		}
	}

	function updateStepIndicator($overlay, step) {
		const $steps = $overlay.find('.a7w-step');

		$steps.removeClass('a7w-step--active a7w-step--done');

		if ('done' === step) {
			$steps.addClass('a7w-step--done');
			return;
		}

		$steps.each(
			function () {
				const stepNum = parseInt($(this).data('step'), 10);
				if (stepNum < step) {
					$(this).addClass('a7w-step--done');
				} else if (stepNum === step) {
					$(this).addClass('a7w-step--active');
				}
			}
		);
	}

	function resetModal(orderId) {
		const $overlay = $('#a7w-modal-' + orderId);
		goToPanel($overlay, '1');
		updateStepIndicator($overlay, 1);

		// Resetuj formularz
		$overlay.find('textarea[name="reason"]').val('');
		$overlay.find('input[name="consent"]').prop('checked', false);
		hideError($overlay.find('.a7w-form__error'));

		state.currentToken = '';
	}

	function showError($el, message) {
		$el.text(message).removeAttr('hidden');
		$el[0] && $el[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
	}

	function hideError($el) {
		$el.attr('hidden', '').text('');
	}

	function wp_nonce_from_dom(orderId) {
		// Fallback – nonce powinien być w DOM
		return $('#a7w-nonce2-' + orderId).val() || '';
	}

})(jQuery);
