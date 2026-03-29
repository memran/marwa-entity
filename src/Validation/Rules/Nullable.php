<?php

declare(strict_types=1);

namespace Marwa\Entity\Validation\Rules;

/**
 * Marker rule — allows null/empty value to skip other rules.
 * Used internally by Validator if needed.
 */
final class Nullable extends AbstractRule
{
    public function __construct()
    {
        $this->message = '';
    }
    public function name(): string
    {
        return 'nullable';
    }
    /** @param array<string, mixed> $context */
    public function validate(mixed $value, array $context = []): bool
    {
        return true;
    }
}
