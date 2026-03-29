<?php

declare(strict_types=1);

namespace Marwa\Entity\Validation\Rules;

final class Url extends AbstractRule
{
    public function __construct(string $message = 'The :field must be a valid URL.')
    {
        $this->message = $message;
    }

    public function name(): string
    {
        return 'url';
    }

    /** @param array<string, mixed> $context */
    public function validate(mixed $value, array $context = []): bool
    {
        return $value === null || filter_var($value, FILTER_VALIDATE_URL) !== false;
    }
}
