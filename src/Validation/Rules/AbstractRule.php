<?php
namespace Marwa\Entity\Validation\Rules;

use Marwa\Entity\Contracts\RuleInterface;

abstract class AbstractRule implements RuleInterface
{
    protected string $message;
    protected array $params = [];

    public function message(): string { return $this->message; }
    public function params(): array { return $this->params; }
}
