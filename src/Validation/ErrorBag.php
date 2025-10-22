<?php
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

    public function has(string $field): bool { return !empty($this->errors[$field] ?? []); }
    public function hasAny(): bool { return !empty($this->errors); }
    public function get(string $field): array { return $this->errors[$field] ?? []; }
    public function all(): array { return $this->errors; }
}
