<?php
/*
  Payment module fixture for phoenix-cart-tests HTTP suite (copied into the catalog at runtime).

  Released under the GNU General Public License
*/

  class http_local_redirect extends abstract_payment_module {

    const CONFIG_KEY_BASE = 'MODULE_PAYMENT_HTTP_LOCAL_REDIRECT_';

    const FIXTURE_TOKEN = 'phoenix-cart-tests-local-redirect-token';

    public $form_action_url;

    public function __construct() {
      parent::__construct();

      $this->form_action_url = Guarantor::ensure_global('Linker')->build(
        'ext/modules/payment/http_local_redirect/return.php'
      );
    }

    public function process_button() {
      return new Input('http_local_redirect_token', ['type' => 'hidden', 'value' => self::FIXTURE_TOKEN]);
    }

    public function before_process() {
      if (Request::value('http_local_redirect_token') !== self::FIXTURE_TOKEN) {
        Href::redirect(Guarantor::ensure_global('Linker')->build('checkout_payment.php', ['payment_error' => $this->code]));
      }
    }

    protected function get_parameters() {
      return [
        $this->config_key_base . 'STATUS' => [
          'title' => 'Enable HTTP Local Redirect Fixture',
          'value' => 'True',
          'desc' => 'phoenix-cart-tests only — exercises off-site confirmation form posting locally.',
          'set_func' => "Config::select_one(['True', 'False'], ",
        ],
        $this->config_key_base . 'SORT_ORDER' => [
          'title' => 'Sort order of display.',
          'value' => '0',
          'desc' => 'Sort order of display. Lowest is displayed first.',
        ],
        $this->config_key_base . 'ZONE' => [
          'title' => 'Payment Zone',
          'value' => '0',
          'desc' => 'If a zone is selected, only enable this payment method for that zone.',
          'use_func' => 'geo_zone::fetch_name',
          'set_func' => 'Config::select_geo_zone(',
        ],
        $this->config_key_base . 'ORDER_STATUS_ID' => [
          'title' => 'Set Order Status',
          'value' => '0',
          'desc' => 'Set the status of orders made with this payment module to this value',
          'set_func' => 'Config::select_order_status(',
          'use_func' => 'order_status::fetch_name',
        ],
      ];
    }

  }
