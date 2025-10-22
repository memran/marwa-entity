<?php

namespace Marwa\Entity\Validation\Rules;

final class IntegerRule extends AbstractRule
{
      public function __construct(string $message = 'The :field must be an integer.')
      {
            $this->message = $message;
      }

      public function name(): string
      {
            return 'integer';
      }

      public function validate(mixed $value, array $context = []): bool
      {
            return $value === null || filter_var($value, FILTER_VALIDATE_INT) !== false;
      }
}
