# Enable cd_newsletter so account_newsletters.php and account dashboard links work in HTTP tests.

UPDATE configuration
SET configuration_value = CONCAT(configuration_value, ';cd_newsletter.php')
WHERE configuration_key = 'MODULE_CUSTOMER_DATA_INSTALLED'
  AND configuration_value NOT LIKE '%cd_newsletter.php%';

INSERT INTO configuration (
  configuration_title,
  configuration_key,
  configuration_value,
  configuration_description,
  configuration_group_id,
  sort_order,
  date_added
) VALUES (
  'Enable Newsletter Module',
  'MODULE_CUSTOMER_DATA_NEWSLETTER_STATUS',
  'True',
  'Do you want to add the module to your shop?',
  6,
  1,
  NOW()
) ON DUPLICATE KEY UPDATE configuration_value = 'True';

INSERT INTO configuration (
  configuration_title,
  configuration_key,
  configuration_value,
  configuration_description,
  configuration_group_id,
  sort_order,
  date_added
) VALUES (
  'Customer data group',
  'MODULE_CUSTOMER_DATA_NEWSLETTER_GROUP',
  '3',
  'In what group should this appear?',
  6,
  2,
  NOW()
) ON DUPLICATE KEY UPDATE configuration_value = VALUES(configuration_value);

INSERT INTO configuration (
  configuration_title,
  configuration_key,
  configuration_value,
  configuration_description,
  configuration_group_id,
  sort_order,
  date_added
) VALUES (
  'Pages',
  'MODULE_CUSTOMER_DATA_NEWSLETTER_PAGES',
  'account_newsletters;create_account;customers',
  'On what pages should this appear?',
  6,
  5,
  NOW()
) ON DUPLICATE KEY UPDATE configuration_value = VALUES(configuration_value);

INSERT INTO configuration (
  configuration_title,
  configuration_key,
  configuration_value,
  configuration_description,
  configuration_group_id,
  sort_order,
  date_added
) VALUES (
  'Require Newsletter (if enabled)',
  'MODULE_CUSTOMER_DATA_NEWSLETTER_REQUIRED',
  'False',
  'Do you want the newsletter to be required in customer registration?',
  6,
  3,
  NOW()
) ON DUPLICATE KEY UPDATE configuration_value = 'False';

INSERT INTO configuration (
  configuration_title,
  configuration_key,
  configuration_value,
  configuration_description,
  configuration_group_id,
  sort_order,
  date_added
) VALUES (
  'Sort Order',
  'MODULE_CUSTOMER_DATA_NEWSLETTER_SORT_ORDER',
  '5800',
  'Sort order of display. Lowest is displayed first.',
  6,
  6,
  NOW()
) ON DUPLICATE KEY UPDATE configuration_value = VALUES(configuration_value);
