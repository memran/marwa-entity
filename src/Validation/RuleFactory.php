<?php

namespace Marwa\Entity\Validation;

use Marwa\Entity\Contracts\RuleInterface;
use Marwa\Entity\Validation\Rules\{
      Required,
      StringRule,
      IntegerRule,
      BooleanRule,
      Min,
      Max,
      Between,
      Email,
      Regex,
      InArray,
      Url,
      IpAddress,
      DateRule,
      Confirmed,
      Nullable,
      Unique,
      Exists
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
      /** @var array<string, callable(array $params, array $providers): RuleInterface> */
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
       * @param array $params   Arbitrary params from schema (e.g., ['min'=>3])
       * @param array $providers Extra providers (e.g., ['callable'=>fn(...)=>...])
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
                  'required'  => new Required($params['message'] ?? 'The :field field is required.'),
                  'string'    => new StringRule($params['message'] ?? 'The :field must be a string.'),
                  'integer'   => new IntegerRule($params['message'] ?? 'The :field must be an integer.'),
                  'boolean'   => new BooleanRule($params['message'] ?? 'The :field must be true or false.'),
                  'min'       => new Min(
                        min: (int)($params['min'] ?? 0),
                        message: $params['message'] ?? 'The :field must be at least :min.'
                  ),
                  'max'       => new Max(
                        max: (int)($params['max'] ?? 0),
                        message: $params['message'] ?? 'The :field must not exceed :max.'
                  ),
                  'between'   => new Between(
                        min: (int)($params['min'] ?? 0),
                        max: (int)($params['max'] ?? 0),
                        message: $params['message'] ?? 'The :field must be between :min and :max.'
                  ),
                  'email'     => new Email($params['message'] ?? 'The :field must be a valid email address.'),
                  'regex'     => new Regex(
                        pattern: (string)($params['pattern'] ?? ''),
                        message: $params['message'] ?? 'The :field format is invalid.'
                  ),
                  'in'        => new InArray(
                        allowed: (array)($params['values'] ?? []),
                        message: $params['message'] ?? 'The :field must be one of the allowed values.'
                  ),
                  'url'       => new Url($params['message'] ?? 'The :field must be a valid URL.'),
                  'ip'        => new IpAddress($params['message'] ?? 'The :field must be a valid IP address.'),
                  'date'      => new DateRule($params['message'] ?? 'The :field must be a valid date (Y-m-d).'),
                  'confirmed' => new Confirmed(
                        confirmField: (string)($params['confirm'] ?? ''),
                        message: $params['message'] ?? 'The :field confirmation does not match.'
                  ),
                  'nullable'  => new Nullable(),

                  'unique'    => self::makeCallableRule(Unique::class, $params, $providers, 'The :field must be unique.'),
                  'exists'    => self::makeCallableRule(Exists::class, $params, $providers, 'The :field value does not exist.'),

                  default     => throw new \InvalidArgumentException("Unknown rule: {$name}")
            };
      }

      /**
       * Helper to create callable-based rules (Unique/Exists).
       * Accepts:
       *  - $providers['callable'] as a direct callable
       *  - or $providers['container'] + $params['service'] to fetch callable from PSR-11
       */
      private static function makeCallableRule(
            string $class,
            array $params,
            array $providers,
            string $defaultMessage
      ): RuleInterface {
            $callable = $providers['callable'] ?? null;

            if ($callable === null && isset($providers['container'], $params['service'])) {
                  /** @var \Psr\Container\ContainerInterface $c */
                  $c = $providers['container'];
                  $service = (string)$params['service'];
                  $resolved = $c->get($service);
                  if (!is_callable($resolved)) {
                        throw new \InvalidArgumentException("Service '{$service}' is not callable for rule.");
                  }
                  $callable = $resolved;
            }

            if (!is_callable($callable)) {
                  throw new \InvalidArgumentException("Missing callable provider for callable-based rule.");
            }

            $message = $params['message'] ?? $defaultMessage;

            /** @var RuleInterface $rule */
            $rule = new $class($callable, $message);
            return $rule;
      }
}
