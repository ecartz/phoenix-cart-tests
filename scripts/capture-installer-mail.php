<?php

declare(strict_types=1);

/**
 * Captures PHP mail() on stdin when used as sendmail_path (Linux installer server).
 *
 * @see scripts/installer-server.sh
 */

$mail_dir = getenv('PHOENIX_INSTALLER_MAIL_DIR');
if (!is_string($mail_dir) || $mail_dir === '') {
    $mail_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'working' . DIRECTORY_SEPARATOR . 'installer-mail';
}

if (!is_dir($mail_dir) && !mkdir($mail_dir, 0777, true) && !is_dir($mail_dir)) {
    fwrite(STDERR, "capture-installer-mail: cannot create {$mail_dir}\n");
    exit(1);
}

$payload = stream_get_contents(STDIN);
if ($payload === false) {
    $payload = '';
}

$filename = $mail_dir . DIRECTORY_SEPARATOR . 'msg-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.eml';
file_put_contents($filename, $payload);

if ($argc > 1) {
    file_put_contents($filename . '.argv', implode("\n", array_slice($argv, 1)));
}

exit(0);
