<?php

namespace Marwa\Entity\Entity;

use Marwa\Entity\Contracts\RuleInterface;
use Marwa\Entity\Contracts\SanitizerInterface;

/**
 * Supported field types
 */
final class Field
{
    /**  
     * @var string 
     * */
    public function __construct(
        public readonly string $name,
        private Types $type = Types::String,
        private ?string $label = null,
        private ?array $enum = null,
        private array $rules = [],
        private array $sanitizers = [],
        private array $meta = [] // ui hints (placeholder, help, widget, options)
    ) {}
    /** Factory method */
    public static function make(string $name): self
    {
        return new self($name);
    }
    /** Set field type */
    public function type(Types $type): self
    {
        $this->type = $type;
        return $this;
    }
    /** Type helpers */
    public function string(): self
    {
        return $this->type(Types::String);
    }
    /** Type helpers */
    public function integer(): self
    {
        return $this->type(Types::Integer);
    }
    /** Type helpers */
    public function boolean(): self
    {
        return $this->type(Types::Boolean);
    }
    /** Type helpers */
    public function decimal(): self
    {
        return $this->type(Types::Decimal);
    }
    /** Type helpers */
    public function datetime(): self
    {
        return $this->type(Types::DateTime);
    }
    /** Type helpers */
    public function json(): self
    {
        return $this->type(Types::Json);
    }
    /** Type helpers */
    public function enum(array $values): self
    {
        $this->type = Types::Enum;
        $this->enum = $values;
        return $this;
    }
    /*
    * Setters for other properties
    */
    public function label(string $label): self
    {
        $this->label = $label;
        return $this;
    }
    /** Set meta key-value */
    public function meta(string $key, mixed $value): self
    {
        $this->meta[$key] = $value;
        return $this;
    }
    /** Set widget meta */
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

    /** @param callable|SanitizerInterface ...$sanitizers */
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
    public function getEnum(): ?array
    {
        return $this->enum;
    }
    public function getRules(): array
    {
        return $this->rules;
    }
    public function getSanitizers(): array
    {
        return $this->sanitizers;
    }
    public function getMeta(): array
    {
        return $this->meta;
    }
}
