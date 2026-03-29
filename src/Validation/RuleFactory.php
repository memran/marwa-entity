<?php

declare(strict_types=1);

namespace Marwa\Entity\Validation;

use Marwa\Entity\Contracts\RuleInterface;
use Marwa\Entity\Validation\Rules\{
    Between,
    BooleanRule,
    Confirmed,
    DateRule,
    Email,
    Exists,
    InArray,
    IntegerRule,
    IpAddress,
    Max,
    Min,
    Nullable,
    Regex,
    Required,
    StringRule,
    Unique,
    Url
};

/**
 * Factory to build RuleInterface instances from a name + params.
 * - Works out of the box for all built-in rules.
 * - Supports custom rule registrations (closures or class names).
 * - Supports callables for Unique/Exists via providers['callable'].
 *
 * Usage:
 *   $rule = RuleFactory::make('min', ['min' => 3]);
 *   $uniq = RuleFactory::make('unique', [], ['callable' => fn($v)=>...]);
 */
final class RuleFactory
{
    /** @var array<string, callable(array<string, mixed>, array<string, mixed>): RuleInterface> */
    private static array $custom = [];

    /**
     * Register a custom rule resolver.
     * The resolver receives ($params, $providers) and must return RuleInterface.
     */
    public static function register(string $name, callable $resolver): void
    {
        self::$custom[strtolower($name)] = $resolver;
    }

    /**
     * Build a rule by name.
     * @param array<string, mixed> $params Arbitrary params from schema (e.g., ['min' => 3])
     * @param array<string, mixed> $providers Extra providers (e.g., ['callable' => fn(...) => ...])
     */
    public static function make(string $name, array $params = [], array $providers = []): RuleInterface
    {
        $key = strtolower($name);

        // Custom registration wins first
        if (isset(self::$custom[$key])) {
            return (self::$custom[$key])($params, $providers);
        }

        // Built-ins
        return match ($key) {
            'required'  => new Required(self::message($params, 'The :field field is required.')),
            'string'    => new StringRule(self::message($params, 'The :field must be a string.')),
            'integer'   => new IntegerRule(self::message($params, 'The :field must be an integer.')),
            'boolean'   => new BooleanRule(self::message($params, 'The :field must be true or false.')),
            'min'       => new Min(
                min: self::intParam($params, 'min'),
                message: self::message($params, 'The :field must be at least :min.'),
            ),
            'max'       => new Max(
                max: self::intParam($params, 'max'),
                message: self::message($params, 'The :field must not exceed :max.'),
            ),
            'between'   => new Between(
                min: self::intParam($params, 'min'),
                max: self::intParam($params, 'max'),
                message: self::message($params, 'The :field must be between :min and :max.'),
            ),
            'email'     => new Email(self::message($params, 'The :field must be a valid email address.')),
            'regex'     => new Regex(
                pattern: self::stringParam($params, 'pattern'),
                message: self::message($params, 'The :field format is invalid.'),
            ),
            'in'        => new InArray(
                allowed: self::stringListParam($params, 'values'),
                message: self::message($params, 'The :field must be one of the allowed values.'),
            ),
            'url'       => new Url(self::message($params, 'The :field must be a valid URL.')),
            'ip'        => new IpAddress(self::message($params, 'The :field must be a valid IP address.')),
            'date'      => new DateRule(self::message($params, 'The :field must be a valid date (Y-m-d).')),
            'confirmed' => new Confirmed(
                confirmField: self::stringParam($params, 'confirm'),
                message: self::message($params, 'The :field confirmation does not match.'),
            ),
            'nullable'  => new Nullable(),

            'unique'    => self::makeCallableRule(Unique::class, $params, $providers, 'The :field must be unique.'),
            'exists'    => self::makeCallableRule(Exists::class, $params, $providers, 'The :field value does not exist.'),

            default     => throw new \InvalidArgumentException("Unknown rule: {$name}"),
        };
    }

    /**
     * Helper to create callable-based rules (Unique/Exists).
     * Accepts:
     *  - $providers['callable'] as a direct callable
     *  - or $providers['container'] + $params['service'] to fetch callable from PSR-11
     *
     * @param array<string, mixed> $params
     * @param array<string, mixed> $providers
     */
    private static function makeCallableRule(
        string $class,
        array $params,
        array $providers,
        string $defaultMessage,
    ): RuleInterface {
        $callable = $providers['callable'] ?? null;

        if ($callable === null && isset($providers['container'], $params['service'])) {
            /** @var \Psr\Container\ContainerInterface $c */
            $c = $providers['container'];
            $service = self::stringParam($params, 'service');
            $resolved = $c->get($service);
            if (!is_callable($resolved)) {
                throw new \InvalidArgumentException("Service '{$service}' is not callable for rule.");
            }
            $callable = $resolved;
        }

        if (!is_callable($callable)) {
            throw new \InvalidArgumentException("Missing callable provider for callable-based rule.");
        }

        $message = self::message($params, $defaultMessage);

        /** @var RuleInterface $rule */
        $rule = new $class($callable, $message);
        return $rule;
    }

    /**
     * @param array<string, mixed> $params
     */
    private static function message(array $params, string $default): string
    {
        return self::stringParam($params, 'message', $default);
    }

    /**
     * @param array<string, mixed> $params
     */
    private static function stringParam(array $params, string $key, string $default = ''): string
    {
        $value = $params[$key] ?? $default;

        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value) || is_bool($value) || $value instanceof \Stringable) {
            return (string) $value;
        }

        return $default;
    }

    /**
     * @param array<string, mixed> $params
     */
    private static function intParam(array $params, string $key, int $default = 0): int
    {
        $value = $params[$key] ?? $default;

        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return $default;
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return list<string>
     */
    private static function stringListParam(array $params, string $key): array
    {
        return array_values(array_filter(
            (array) ($params[$key] ?? []),
            static fn(mixed $value): bool => is_string($value),
        ));
    }
}
