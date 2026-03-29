<?php

declare(strict_types=1);

namespace Marwa\Entity\Validation\Rules;

final class Max extends AbstractRule
{
    public function __construct(private readonly int|float $max, string $message = 'The :field must not exceed :max.')
    {
        $this->message = $message;
        $this->params = ['max' => $this->max];
    }

    public function name(): string
    {
        return 'max';
    }

    /** @param array<string, mixed> $context */
    public function validate(mixed $value, array $context = []): bool
    {
        if ($value === null) {
            return true;
        }
        if (is_string($value)) {
            return mb_strlen($value) <= $this->max;
        }
        if (is_numeric($value)) {
            return $value <= $this->max;
        }
        if (is_array($value)) {
            return count($value) <= $this->max;
        }
        return false;
    }
}
