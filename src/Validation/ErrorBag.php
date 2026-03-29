<?php

declare(strict_types=1);

namespace Marwa\Entity\Validation;

final class ErrorBag
{
    /** @var array<string, string[]> */
    private array $errors = [];

    public function add(string $field, string $message): void
    {
        $this->errors[$field] ??= [];
        $this->errors[$field][] = $message;
    }

    public function has(string $field): bool
    {
        return !empty($this->errors[$field] ?? []);
    }
    public function hasAny(): bool
    {
        return !empty($this->errors);
    }
    /**
     * @return list<string>
     */
    public function get(string $field): array
    {
        return $this->errors[$field] ?? [];
    }
    /**
     * @return array<string, list<string>>
     */
    public function all(): array
    {
        return $this->errors;
    }
}
