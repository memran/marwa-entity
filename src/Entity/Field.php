<?php

declare(strict_types=1);

namespace Marwa\Entity\Entity;

use Marwa\Support\Validation\Contracts\RuleInterface;

final class Field
{
    /**
     * @param list<string>|null $enum
     * @param list<RuleInterface> $rules
     * @param list<callable(mixed): mixed> $sanitizers
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public readonly string $name,
        private Types $type = Types::String,
        private ?string $label = null,
        private ?array $enum = null,
        private array $rules = [],
        private array $sanitizers = [],
        private array $meta = [],
    ) {}

    public static function make(string $name): self
    {
        return new self($name);
    }

    public function type(Types $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function string(): self
    {
        return $this->type(Types::String);
    }

    public function integer(): self
    {
        return $this->type(Types::Integer);
    }

    public function boolean(): self
    {
        return $this->type(Types::Boolean);
    }

    public function decimal(): self
    {
        return $this->type(Types::Decimal);
    }

    public function datetime(): self
    {
        return $this->type(Types::DateTime);
    }

    public function json(): self
    {
        return $this->type(Types::Json);
    }

    /**
     * @param list<string> $values
     */
    public function enum(array $values): self
    {
        $this->type = Types::Enum;
        $this->enum = $values;

        return $this;
    }

    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function meta(string $key, mixed $value): self
    {
        $this->meta[$key] = $value;

        return $this;
    }

    public function widget(string $widget): self
    {
        return $this->meta('widget', $widget);
    }

    /** @param RuleInterface ...$rules */
    public function rule(RuleInterface ...$rules): self
    {
        array_push($this->rules, ...$rules);

        return $this;
    }

    public function sanitize(callable ...$sanitizers): self
    {
        array_push($this->sanitizers, ...$sanitizers);

        return $this;
    }

    public function getType(): Types
    {
        return $this->type;
    }
    public function getLabel(): ?string
    {
        return $this->label;
    }
    /**
     * @return list<string>|null
     */
    public function getEnum(): ?array
    {
        return $this->enum;
    }
    /**
     * @return list<RuleInterface>
     */
    public function getRules(): array
    {
        return $this->rules;
    }
    /**
     * @return list<callable(mixed): mixed>
     */
    public function getSanitizers(): array
    {
        return $this->sanitizers;
    }
    /**
     * @return array<string, mixed>
     */
    public function getMeta(): array
    {
        return $this->meta;
    }
}
