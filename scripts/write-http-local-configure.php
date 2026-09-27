#!/usr/bin/env php
<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use PhoenixCart\Tests\Support\http_bootstrap;

if (!http_bootstrap::is_enabled()) {
    fwrite(STDERR, "Set PHOENIX_HTTP_ENABLED=1 before writing includes/local/configure.php.\n");
    exit(1);
}

http_bootstrap::write_local_configure();

fwrite(STDOUT, "Wrote catalog includes/local/configure.php for HTTP acceptance.\n");
