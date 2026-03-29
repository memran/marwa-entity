<?php

declare(strict_types=1);

namespace Marwa\Entity\Validation\Rules;

final class DateRule extends AbstractRule
{
    public function __construct(string $message = 'The :field must be a valid date (Y-m-d).')
    {
        $this->message = $message;
    }

    public function name(): string
    {
        return 'date';
    }

    /** @param array<string, mixed> $context */
    public function validate(mixed $value, array $context = []): bool
    {
        if ($value === null) {
            return true;
        }
        if (!is_string($value)) {
            return false;
        }
        $d = \DateTime::createFromFormat('Y-m-d', $value);
        return $d && $d->format('Y-m-d') === $value;
    }
}
