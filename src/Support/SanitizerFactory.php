<?php

declare(strict_types=1);

namespace Marwa\Entity\Support;

use Marwa\Support\Str;

/**
 * Factory for sanitizers used by Field::sanitize().
 * Returns closures (callable) by name.
 *
 * Usage:
 *   $trim = SanitizerFactory::make('trim');
 *   $strip = SanitizerFactory::make('strip_tags', ['allowed' => ['b','i']]);
 */
final class SanitizerFactory
{
    /** @var array<string, callable> */
    private static array $custom = [];

    public static function register(string $name, callable $resolver): void
    {
        self::$custom[Str::lower($name)] = $resolver;
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return callable(mixed): mixed
     */
    public static function make(string $name, array $params = []): callable
    {
        $key = Str::lower($name);

        if (isset(self::$custom[$key])) {
            return (self::$custom[$key])($params);
        }

        return match ($key) {
            'trim'       => Sanitizers::trim(),
            'lower'      => Sanitizers::lower(),
            'strip_tags' => Sanitizers::stripTags(array_values(array_filter(
                (array) ($params['allowed'] ?? []),
                static fn(mixed $tag): bool => is_string($tag),
            ))),
            default      => throw new \InvalidArgumentException("Unknown sanitizer: {$name}"),
        };
    }
}
