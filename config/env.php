<?php

if (! function_exists('busamas_env')) {
    function busamas_env(string $key, mixed $default = null): mixed
    {
        static $loaded = false;
        static $values = [];

        if (! $loaded) {
            $loaded = true;
            $path = dirname(__DIR__) . '/.env';

            if (is_readable($path)) {
                foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                    $line = trim($line);

                    if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                        continue;
                    }

                    [$name, $value] = array_map('trim', explode('=', $line, 2));
                    $values[$name] = trim($value, "\"'");
                }
            }
        }

        $value = getenv($key);

        if ($value !== false) {
            return $value;
        }

        return $values[$key] ?? $default;
    }
}
