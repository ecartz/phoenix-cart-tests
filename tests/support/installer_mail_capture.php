<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\support;

/**
 * Reads messages captured by {@see scripts/capture-installer-mail.php} on the installer PHP server.
 */
final class installer_mail_capture
{
    public static function is_enabled(): bool
    {
        $flag = getenv('PHOENIX_INSTALLER_MAIL_CAPTURE');

        return $flag !== false && $flag !== '' && $flag !== '0';
    }

    public static function directory(): string
    {
        $configured = getenv('PHOENIX_INSTALLER_MAIL_DIR');
        if (is_string($configured) && $configured !== '') {
            return rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $configured), DIRECTORY_SEPARATOR);
        }

        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'working' . DIRECTORY_SEPARATOR . 'installer-mail';
    }

    public static function clear(): void
    {
        $directory = self::directory();
        if (!is_dir($directory)) {
            return;
        }

        foreach (glob($directory . DIRECTORY_SEPARATOR . 'msg-*') ?: [] as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public static function read_combined(): string
    {
        $directory = self::directory();
        if (!is_dir($directory)) {
            return '';
        }

        $files = glob($directory . DIRECTORY_SEPARATOR . 'msg-*.eml') ?: [];
        sort($files);

        $combined = '';
        foreach ($files as $file) {
            $combined .= (string) file_get_contents($file);
        }

        return $combined;
    }
}
