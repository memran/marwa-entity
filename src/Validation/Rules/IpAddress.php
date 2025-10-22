<?php

namespace Marwa\Entity\Validation\Rules;

final class IpAddress extends AbstractRule
{
      public function __construct(string $message = 'The :field must be a valid IP address.')
      {
            $this->message = $message;
      }

      public function name(): string
      {
            return 'ip';
      }

      public function validate(mixed $value, array $context = []): bool
      {
            return $value === null || filter_var($value, FILTER_VALIDATE_IP) !== false;
      }
}
