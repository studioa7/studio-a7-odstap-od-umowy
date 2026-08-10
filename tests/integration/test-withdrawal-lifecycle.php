<?php
/** @group a7w */
class Test_A7_Withdrawal_Lifecycle extends WP_UnitTestCase
{
    private A7_Withdrawal_DB $db;

    public function set_up(): void
    {
        parent::set_up();
        A7_Withdrawal_DB::create_table();
        $this->db = A7_Withdrawal_DB::get_instance();
    }

    public function test_form_field_definitions_drop_unsafe_types_and_html(): void
    {
        $fields = new A7_Withdrawal_Form_Fields();
        $definitions = $fields->sanitize_definitions('[{"key":"photo","type":"upload","label":"Photo","required":true},{"key":"x","type":"script","label":"X"},{"key":"notice","type":"html","html":"<script>x</script><strong>OK</strong>"}]');

        $this->assertCount(2, $definitions);
        $this->assertSame('upload', $definitions[0]['type']);
        $this->assertStringNotContainsString('<script>', $definitions[1]['html']);
    }

    public function test_confirm_decide_and_shipping_update_are_audited(): void
    {
        $request_id = $this->db->insert(array(
            'order_id' => 987654,
            'customer_id' => 123,
            'customer_email' => 'customer@example.test',
            'customer_name' => 'Customer Test',
            'token' => wp_generate_password(64, false, false),
            'item_quantities' => wp_json_encode(array(9 => 1)),
        ));
        $this->assertIsInt($request_id);
        $this->assertTrue($this->db->confirm($request_id));
        $this->assertTrue($this->db->update_shipping($request_id, array('return_method' => 'courier', 'tracking_number' => 'ABC123'), 123));
        $this->assertTrue($this->db->decide($request_id, 'approved', 'Approved by test', 1));

        $record = $this->db->get($request_id);
        $this->assertSame('approved', $record->status);
        $this->assertSame('ABC123', json_decode($record->shipping_data, true)['tracking_number']);
        $audit = json_decode($record->audit_log, true);
        $this->assertSame(array('confirmed', 'shipping_updated', 'decision'), wp_list_pluck($audit, 'event'));
    }

    public function test_only_confirmed_requests_can_receive_staff_decision(): void
    {
        $request_id = $this->db->insert(array('order_id' => 765432, 'token' => wp_generate_password(64, false, false)));
        $this->assertFalse($this->db->decide($request_id, 'approved', '', 1));
        $this->assertFalse($this->db->decide($request_id, 'unexpected', '', 1));
    }
}
