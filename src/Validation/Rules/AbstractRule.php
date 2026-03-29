<?php

declare(strict_types=1);

namespace Marwa\Entity\Validation\Rules;

use Marwa\Entity\Contracts\RuleInterface;

abstract class AbstractRule implements RuleInterface
{
    protected string $message;
    /** @var array<string, mixed> */
    protected array $params = [];

    public function message(): string
    {
        return $this->message;
    }
    /**
     * @return array<string, mixed>
     */
    public function params(): array
    {
        return $this->params;
    }
}
