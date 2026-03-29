<?php

declare(strict_types=1);

namespace Marwa\Entity\Validation\Rules;

final class Required extends AbstractRule
{
    public function __construct(string $message = 'The :field field is required.')
    {
        $this->message = $message;
    }

    public function name(): string
    {
        return 'required';
    }

    /** @param array<string, mixed> $context */
    public function validate(mixed $value, array $context = []): bool
    {
        return !($value === null || $value === '' || (is_array($value) && count($value) === 0));
    }
}
