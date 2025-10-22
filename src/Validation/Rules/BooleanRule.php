<?php

namespace Marwa\Entity\Validation\Rules;

final class BooleanRule extends AbstractRule
{
      public function __construct(string $message = 'The :field must be true or false.')
      {
            $this->message = $message;
      }

      public function name(): string
      {
            return 'boolean';
      }

      public function validate(mixed $value, array $context = []): bool
      {
            return $value === null || is_bool($value)
                  || in_array($value, [0, 1, '0', '1', 'true', 'false', true, false], true);
      }
}
