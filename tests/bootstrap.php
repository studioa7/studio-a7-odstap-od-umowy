<?php
/** PHPUnit bootstrap for a WordPress + WooCommerce test installation. */

$_tests_dir = getenv('WP_TESTS_DIR');
if (!$_tests_dir) {
    $_tests_dir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . '/wordpress-tests-lib';
}

require_once $_tests_dir . '/includes/functions.php';
tests_add_filter('muplugins_loaded', static function (): void {
    require dirname(__DIR__) . '/studio-a7-odstap-od-umowy.php';
});
require $_tests_dir . '/includes/bootstrap.php';
