<?php
namespace Marwa\Entity\Validation\Rules;

final class StringRule extends AbstractRule
{
    public function __construct(string $message = 'The :field must be a string.')
    {
        $this->message = $message;
    }

    public function name(): string { return 'string'; }

    public function validate(mixed $value, array $context = []): bool
    {
        return $value === null || is_string($value);
    }
}
