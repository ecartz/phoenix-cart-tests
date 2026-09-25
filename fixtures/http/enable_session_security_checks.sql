# Enable session user-agent and IP validation for HTTP acceptance tests (install defaults are False).
UPDATE configuration SET configuration_value = 'True' WHERE configuration_key = 'SESSION_CHECK_USER_AGENT';
UPDATE configuration SET configuration_value = 'True' WHERE configuration_key = 'SESSION_CHECK_IP_ADDRESS';
