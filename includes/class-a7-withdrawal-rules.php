<?php
/** Eligibility rules and explicit, opt-in WooCommerce actions. */
defined('ABSPATH') || exit;

class A7_Withdrawal_Rules
{
    public function evaluate(\WC_Order $order): true|\WP_Error
    {
        $rules = get_option('a7w_eligibility_rules', array());
        $rules = is_array($rules) ? $rules : array();
        if (!empty($rules['payment_methods']) && !in_array($order->get_payment_method(), array_map('sanitize_key', (array) $rules['payment_methods']), true)) {
            return new \WP_Error('payment_method_excluded', __('Wybrana metoda płatności nie kwalifikuje się do odstąpienia.', 'studio-a7-odstap'));
        }
        if (!empty($rules['shipping_methods']) && !in_array($order->get_shipping_method(), array_map('sanitize_text_field', (array) $rules['shipping_methods']), true)) {
            return new \WP_Error('shipping_method_excluded', __('Wybrana metoda dostawy nie kwalifikuje się do odstąpienia.', 'studio-a7-odstap'));
        }
        if (!empty($rules['user_roles'])) {
            $user = $order->get_customer_id() ? get_user_by('id', $order->get_customer_id()) : false;
            if (!$user || !array_intersect((array) $user->roles, array_map('sanitize_key', (array) $rules['user_roles']))) {
                return new \WP_Error('role_excluded', __('Twoje konto nie kwalifikuje się do odstąpienia.', 'studio-a7-odstap'));
            }
        }
        $excluded_products = array_map('absint', (array) ($rules['excluded_products'] ?? array()));
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product && in_array($product->get_id(), $excluded_products, true)) {
                return new \WP_Error('product_excluded', __('Zamówienie zawiera produkt wyłączony z odstąpienia.', 'studio-a7-odstap'));
            }
        }
        return true;
    }
}

class A7_Withdrawal_Actions
{
    public function apply(object $withdrawal, \WC_Order $order): true|\WP_Error
    {
        $action = sanitize_key((string) get_option('a7w_approval_action', 'none'));
        if ('none' === $action) {
            return true;
        }
        if ('coupon' === $action) {
            $amount = max(0, (float) get_option('a7w_coupon_amount', 0));
            if ($amount <= 0) {
                return new \WP_Error('invalid_coupon_amount', __('Skonfiguruj dodatnią wartość kuponu.', 'studio-a7-odstap'));
            }
            $coupon = new \WC_Coupon();
            $coupon->set_code('A7W-' . $withdrawal->id . '-' . wp_generate_password(8, false, false));
            $coupon->set_discount_type('fixed_cart');
            $coupon->set_amount($amount);
            $coupon->set_email_restrictions(array(sanitize_email($withdrawal->customer_email)));
            $coupon->set_description(sprintf('Withdrawal #%d', $withdrawal->id));
            $coupon->save();
            $order->add_order_note(sprintf(__('Utworzono kupon rekompensacyjny: %s.', 'studio-a7-odstap'), $coupon->get_code()));
            return true;
        }
        if ('refund' === $action) {
            $amount = max(0, (float) get_option('a7w_refund_amount', 0));
            if ($amount <= 0 || $amount > (float) $order->get_remaining_refund_amount()) {
                return new \WP_Error('invalid_refund_amount', __('Kwota zwrotu jest nieprawidłowa.', 'studio-a7-odstap'));
            }
            $refund = wc_create_refund(array('amount' => $amount, 'reason' => sprintf('Withdrawal #%d', $withdrawal->id), 'order_id' => $order->get_id(), 'refund_payment' => 'yes' === get_option('a7w_refund_payment', 'no'), 'restock_items' => false));
            return is_wp_error($refund) ? $refund : true;
        }
        return new \WP_Error('invalid_approval_action', __('Nieprawidłowa akcja po zatwierdzeniu.', 'studio-a7-odstap'));
    }
}
