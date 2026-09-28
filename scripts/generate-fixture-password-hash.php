<?php

declare(strict_types=1);

require dirname(__DIR__) . '/tests/bootstrap.php';

echo Password::hash('phoenix-test') . PHP_EOL;
