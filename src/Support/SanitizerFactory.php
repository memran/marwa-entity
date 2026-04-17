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
            $result = (self::$custom[$key])($params);
            /** @var callable(mixed): mixed */
            return $result;
        }

        $allowed = array_values(array_filter(
            (array) ($params['allowed'] ?? []),
            static fn(mixed $tag): bool => is_string($tag),
        ));

        return match ($key) {
            'trim'       => static fn($v) => is_string($v) ? trim($v) : $v,
            'lower'      => static fn($v) => is_string($v) ? Str::lower($v) : $v,
            'strip_tags' => static fn($v) => is_string($v) ? Str::stripTags($v, implode('', array_map(static fn(string $tag): string => "<{$tag}>", $allowed))) : $v,
            default    => throw new \InvalidArgumentException("Unknown sanitizer: {$name}"),
        };
    }
}
