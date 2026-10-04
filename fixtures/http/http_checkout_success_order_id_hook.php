<?php
/*
  Harness shim: set $GLOBALS['order_id'] on checkout_success.php (copied into catalog at runtime).

  Released under the GNU General Public License
*/

class hook_shop_siteWide_http_checkout_success_order_id {

    public function listen_startApplication() {
        if (!defined('HTTP_TEST_CHECKOUT_SUCCESS_ORDER_ID_SHIM_STATUS')
            || HTTP_TEST_CHECKOUT_SUCCESS_ORDER_ID_SHIM_STATUS !== 'True') {
            return;
        }

        if (basename(Request::get_page()) !== 'checkout_success.php') {
            return;
        }

        if (!isset($_SESSION['customer_id'])) {
            return;
        }

        $orders_query = $GLOBALS['db']->query(
            'SELECT orders_id FROM orders WHERE customers_id = '
            . (int) $_SESSION['customer_id']
            . ' ORDER BY date_purchased DESC LIMIT 1'
        );

        if (!mysqli_num_rows($orders_query)) {
            return;
        }

        $orders = $orders_query->fetch_assoc();
        $GLOBALS['order_id'] = (int) $orders['orders_id'];
    }

}
