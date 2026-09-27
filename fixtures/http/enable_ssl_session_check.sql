# Enable SSL session id validation for HTTPS acceptance tests (install default is False).
UPDATE configuration SET configuration_value = 'True' WHERE configuration_key = 'SESSION_CHECK_SSL_SESSION_ID';
