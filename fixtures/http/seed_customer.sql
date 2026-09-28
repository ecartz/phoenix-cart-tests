# HTTP acceptance fixture customer (login, account.php, gdpr.php).
# Password plaintext for tests: phoenix-test (regenerate hash: php scripts/generate-fixture-password-hash.php)

DELETE FROM customers WHERE customers_email_address = 'phoenix-http-fixture@example.com';

INSERT INTO customers (
  customers_id,
  customers_gender,
  customers_firstname,
  customers_lastname,
  customers_dob,
  customers_email_address,
  customers_default_address_id,
  customers_telephone,
  customers_fax,
  customers_password,
  customers_newsletter,
  status
) VALUES (
  1,
  'm',
  'Fixture',
  'Customer',
  NULL,
  'phoenix-http-fixture@example.com',
  1,
  '555-0100',
  NULL,
  '$2y$12$yLp3Jl/6JtaqZru2oUgwnO5fL.t9i8ZwPKt3URtZqRJ52gST.G44.',
  '0',
  1
);

INSERT INTO address_book (
  address_book_id,
  customers_id,
  entry_gender,
  entry_company,
  entry_firstname,
  entry_lastname,
  entry_street_address,
  entry_suburb,
  entry_postcode,
  entry_city,
  entry_state,
  entry_country_id,
  entry_zone_id
) VALUES (
  1,
  1,
  'm',
  '',
  'Fixture',
  'Customer',
  '1 Test Street',
  '',
  '90210',
  'Testville',
  'Florida',
  223,
  18
);

INSERT INTO customers_info (
  customers_info_id,
  customers_info_date_of_last_logon,
  customers_info_number_of_logons,
  customers_info_date_account_created,
  customers_info_date_account_last_modified,
  global_product_notifications,
  password_reset_key,
  password_reset_date
) VALUES (
  1,
  NULL,
  0,
  NOW(),
  NOW(),
  0,
  NULL,
  NULL
);
