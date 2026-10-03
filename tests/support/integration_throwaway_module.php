<?php

declare(strict_types=1);

/**
 * Minimal {@see abstract_module} used only for Integration install/remove tests.
 */
class integration_throwaway_module extends abstract_module
{
    public const CONFIG_KEY_BASE = 'MODULE_PHOENIX_INTEGRATION_PROBE_';

    protected function get_parameters(): array {
        $base = self::CONFIG_KEY_BASE;

        return [
            $base . 'STATUS' => [
                'title' => 'Integration probe status',
                'value' => 'False',
                'desc' => 'Ephemeral configuration rows for PHPUnit wave 3 part 3.',
                'set_func' => "Config::select_one(['True', 'False'], ",
            ],
            $base . 'SORT_ORDER' => [
                'title' => 'Sort Order',
                'value' => '0',
                'desc' => 'Sort order of display. Lowest is displayed first.',
            ],
        ];
    }
}
