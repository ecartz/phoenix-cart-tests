<?php
/*
  ext return script for phoenix-cart-tests HTTP local redirect payment fixture.

  Copied to ext/modules/payment/http_local_redirect/return.php during checkout_local_redirect_test.

  Released under the GNU General Public License
*/

  chdir('../../../../');
  require 'includes/application_top.php';

  const HTTP_LOCAL_REDIRECT_FIXTURE_TOKEN = 'phoenix-cart-tests-local-redirect-token';

  const HTTP_LOCAL_REDIRECT_FIXTURE_MODULE_CODE = 'http_local_redirect';

  const HTTP_LOCAL_REDIRECT_FIXTURE_TOKEN_INPUT = 'http_local_redirect_token';

  $submitted_token = Request::value(HTTP_LOCAL_REDIRECT_FIXTURE_TOKEN_INPUT);

  if ($submitted_token !== HTTP_LOCAL_REDIRECT_FIXTURE_TOKEN) {
    Href::redirect(Guarantor::ensure_global('Linker')->build(
      'checkout_payment.php',
      ['payment_error' => HTTP_LOCAL_REDIRECT_FIXTURE_MODULE_CODE]
    ));
  }

  Href::redirect(Guarantor::ensure_global('Linker')->build('checkout_process.php'));
