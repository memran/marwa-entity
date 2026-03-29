<?php

declare(strict_types=1);

namespace Marwa\Entity\Validation\Rules;

final class StringRule extends AbstractRule
{
    public function __construct(string $message = 'The :field must be a string.')
    {
        $this->message = $message;
    }

    public function name(): string
    {
        return 'string';
    }

    /** @param array<string, mixed> $context */
    public function validate(mixed $value, array $context = []): bool
    {
        return $value === null || is_string($value);
    }
}
