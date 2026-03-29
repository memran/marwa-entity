<?php

declare(strict_types=1);

namespace Marwa\Entity\Validation\Rules;

/**
 * Provide a callable: fn(mixed $value, array $context): bool
 * Must return true if value is unique (i.e., no existing record clashes).
 */
final class Unique extends AbstractRule
{
    /** @var callable */
    private $checker;

    public function __construct(callable $checker, string $message = 'The :field must be unique.')
    {
        $this->checker = $checker;
        $this->message = $message;
    }

    public function name(): string
    {
        return 'unique';
    }

    /** @param array<string, mixed> $context */
    public function validate(mixed $value, array $context = []): bool
    {
        if ($value === null) {
            return true;
        }
        return (bool) call_user_func($this->checker, $value, $context);
    }
}
