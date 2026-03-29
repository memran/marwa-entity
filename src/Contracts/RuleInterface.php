<?php

declare(strict_types=1);

namespace Marwa\Entity\Contracts;

interface RuleInterface
{
    /**
     * @param array<string, mixed> $context
     *
     * @return bool true on pass, false on fail
     */
    public function validate(mixed $value, array $context = []): bool;

    /** Machine name, e.g., "required", "min" */
    public function name(): string;

    /** Message template or resolved final message */
    public function message(): string;

    /**
     * Optional placeholders for templating (e.g., ['min' => 3]).
     *
     * @return array<string, mixed>
     */
    public function params(): array;
}
