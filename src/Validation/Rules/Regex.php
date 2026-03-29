<?php

declare(strict_types=1);

namespace Marwa\Entity\Validation\Rules;

final class Regex extends AbstractRule
{
    public function __construct(
        private readonly string $pattern,
        string $message = 'The :field format is invalid.',
    ) {
        $this->message = $message;
        $this->params = ['pattern' => $pattern];
    }

    public function name(): string
    {
        return 'regex';
    }

    /** @param array<string, mixed> $context */
    public function validate(mixed $value, array $context = []): bool
    {
        if ($value === null) {
            return true;
        }
        if (!is_scalar($value)) {
            return false;
        }
        return preg_match($this->pattern, (string) $value) === 1;
    }
}
