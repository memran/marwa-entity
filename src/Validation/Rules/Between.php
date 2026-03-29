<?php

declare(strict_types=1);

namespace Marwa\Entity\Validation\Rules;

final class Between extends AbstractRule
{
    public function __construct(
        private readonly int|float $min,
        private readonly int|float $max,
        string $message = 'The :field must be between :min and :max.',
    ) {
        $this->message = $message;
        $this->params = ['min' => $this->min, 'max' => $this->max];
    }

    public function name(): string
    {
        return 'between';
    }

    /** @param array<string, mixed> $context */
    public function validate(mixed $value, array $context = []): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            $len = mb_strlen($value);
            return $len >= $this->min && $len <= $this->max;
        }

        if (is_numeric($value)) {
            return $value >= $this->min && $value <= $this->max;
        }

        if (is_array($value)) {
            $count = count($value);
            return $count >= $this->min && $count <= $this->max;
        }

        return false;
    }
}
