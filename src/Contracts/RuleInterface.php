<?php
namespace Marwa\Entity\Contracts;

interface RuleInterface
{
    /** @return bool true on pass, false on fail */
    public function validate(mixed $value, array $context = []): bool;

    /** Machine name, e.g., "required", "min" */
    public function name(): string;

    /** Message template or resolved final message */
    public function message(): string;

    /** Optional placeholders for templating (e.g., ['min' => 3]) */
    public function params(): array;
}
