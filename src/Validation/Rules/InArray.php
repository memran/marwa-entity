<?php

declare(strict_types=1);

namespace Marwa\Entity\Validation\Rules;

final class InArray extends AbstractRule
{
    /** @param list<string> $allowed */
    public function __construct(
        private readonly array $allowed,
        string $message = 'The :field must be one of the allowed values.',
    ) {
        $this->message = $message;
        $this->params = ['values' => implode(', ', $allowed)];
    }

    public function name(): string
    {
        return 'in';
    }

    /** @param array<string, mixed> $context */
    public function validate(mixed $value, array $context = []): bool
    {
        return $value === null || in_array($value, $this->allowed, true);
    }
}
