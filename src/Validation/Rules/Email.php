<?php

namespace Marwa\Entity\Validation\Rules;

final class Email extends AbstractRule
{
      public function __construct(string $message = 'The :field must be a valid email address.')
      {
            $this->message = $message;
      }

      public function name(): string
      {
            return 'email';
      }

      public function validate(mixed $value, array $context = []): bool
      {
            return $value === null || filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
      }
}
