<?php

namespace Marwa\Entity\Validation\Rules;

/**
 * Provide a callable: fn(mixed $value, array $context): bool
 * Must return true if the value exists (e.g., in DB or external system).
 */
final class Exists extends AbstractRule
{
      /** @var callable */
      private $checker;

      public function __construct(callable $checker, string $message = 'The :field value does not exist.')
      {
            $this->checker = $checker;
            $this->message = $message;
      }

      public function name(): string
      {
            return 'exists';
      }

      public function validate(mixed $value, array $context = []): bool
      {
            if ($value === null) return true;
            return (bool) call_user_func($this->checker, $value, $context);
      }
}
