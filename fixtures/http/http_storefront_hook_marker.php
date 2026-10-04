<?php
/*
  Storefront hook fixture for phoenix-cart-tests (copied into the catalog at runtime).

  Released under the GNU General Public License
*/

class hook_shop_siteWide_http_storefront_hook_marker {

  public function listen_injectBodyEnd() {
    if (!defined('HTTP_TEST_STOREFRONT_HOOK_MARKER_STATUS')
      || HTTP_TEST_STOREFRONT_HOOK_MARKER_STATUS !== 'True') {
      return '';
    }

    return PHP_EOL . '<!-- phoenix-cart-tests-storefront-hook-marker -->' . PHP_EOL;
  }

}
