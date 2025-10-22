<?php

namespace Marwa\Entity\Support;

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
      /** @var array<string, callable(array $params): callable> */
      private static array $custom = [];

      public static function register(string $name, callable $resolver): void
      {
            self::$custom[strtolower($name)] = $resolver;
      }

      public static function make(string $name, array $params = []): callable
      {
            $key = strtolower($name);

            if (isset(self::$custom[$key])) {
                  return (self::$custom[$key])($params);
            }

            return match ($key) {
                  'trim'       => Sanitizers::trim(),
                  'lower'      => Sanitizers::lower(),
                  'strip_tags' => Sanitizers::stripTags((array)($params['allowed'] ?? [])),
                  default      => throw new \InvalidArgumentException("Unknown sanitizer: {$name}")
            };
      }
}
