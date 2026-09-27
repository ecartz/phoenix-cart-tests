#!/usr/bin/env php
<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use PhoenixCart\Tests\Support\payment_sandbox_bootstrap;

if (!payment_sandbox_bootstrap::is_enabled()) {
    fwrite(STDERR, "Set PHOENIX_PAYMENT_SANDBOX_ENABLED=1 and Stripe test key env vars.\n");
    exit(1);
}

payment_sandbox_bootstrap::apply_to_database();

fwrite(STDOUT, "Applied Stripe SCA test configuration from environment.\n");
