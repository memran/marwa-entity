<?php
namespace Marwa\Entity\Validation\Rules;

final class Min extends AbstractRule
{
    public function __construct(private readonly int $min, string $message = 'The :field must be at least :min.')
    {
        $this->message = $message;
        $this->params = ['min' => $this->min];
    }

    public function name(): string { return 'min'; }

    public function validate(mixed $value, array $context = []): bool
    {
        if ($value === null) return true;
        if (is_string($value)) return mb_strlen($value) >= $this->min;
        if (is_numeric($value)) return $value >= $this->min;
        return false;
    }
}
