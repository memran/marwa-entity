<?php

namespace Marwa\Entity\Validation;

use Marwa\Entity\Contracts\RuleInterface;
use Psr\Container\ContainerInterface;

/**
 * Instance-level registry that:
 *  - Lets you register/resolver rules per app instance.
 *  - Supports PSR-11 container for callable-based rules.
 */
final class RuleRegistry
{
      /** @var array<string, callable(array $params, array $providers): RuleInterface> */
      private array $resolvers = [];

      public function __construct(private readonly ?ContainerInterface $container = null) {}

      public function register(string $name, callable $resolver): void
      {
            $this->resolvers[strtolower($name)] = $resolver;
      }

      public function make(string $name, array $params = [], array $providers = []): RuleInterface
      {
            $key = strtolower($name);

            // Merge container into providers so resolvers can access it
            if ($this->container && !isset($providers['container'])) {
                  $providers['container'] = $this->container;
            }

            if (isset($this->resolvers[$key])) {
                  return ($this->resolvers[$key])($params, $providers);
            }

            // Fallback to static RuleFactory (built-ins)
            return RuleFactory::make($key, $params, $providers);
      }
}
