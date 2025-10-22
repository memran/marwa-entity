<?php

namespace Marwa\Entity\Validation\Rules;

final class Confirmed extends AbstractRule
{
      public function __construct(
            private readonly string $confirmField = '',
            string $message = 'The :field confirmation does not match.'
      ) {
            $this->message = $message;
            $this->params = ['confirm' => $confirmField];
      }

      public function name(): string
      {
            return 'confirmed';
      }

      public function validate(mixed $value, array $context = []): bool
      {
            if (!isset($context['request']) && !isset($context['input'])) {
                  return true;
            }

            $data = $context['input'] ?? [];
            if (isset($context['request']) && method_exists($context['request'], 'getParsedBody')) {
                  $data = array_merge($data, (array)$context['request']->getParsedBody());
            }

            $confirmKey = $this->confirmField ?: "{$context['field']}_confirmation";
            return isset($data[$confirmKey]) && $data[$confirmKey] === $value;
      }
}
