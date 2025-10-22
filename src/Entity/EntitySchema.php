<?php

namespace Marwa\Entity\Entity;

final class EntitySchema
{
    /** @var array<string, Field> */
    private array $fields = [];
    private ?string $name = null;
    /**
     * @param string|null $name Optional name of the entity (e.g., table name)
     * 
     */
    public function __construct(?string $name = null)
    {
        $this->name = $name;
    }
    /** Factory method */
    public static function make(?string $name = null): self
    {
        return new self($name);
    }
    /** Set entity name */
    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }
    /** Register a field */
    public function field(Field $field): self
    {
        $this->fields[$field->name] = $field;
        return $this;
    }
    /** Field type helpers */
    public function string(string $name): Field
    {
        $f = Field::make($name)->string();
        return $this->tap($f);
    }
    /** Field type helpers */
    public function integer(string $name): Field
    {
        $f = Field::make($name)->integer();
        return $this->tap($f);
    }
    /** Field type helpers */
    public function boolean(string $name): Field
    {
        $f = Field::make($name)->boolean();
        return $this->tap($f);
    }
    /** Field type helpers */
    public function enum(string $name, array $values): Field
    {
        $f = Field::make($name)->enum($values);
        return $this->tap($f);
    }

    /** finalize field registration and return the field for chaining */
    private function tap(Field $field): Field
    {
        $this->field($field);
        return $field;
    }

    /** @return array<string, Field> */
    public function fields(): array
    {
        return $this->fields;
    }
    public function get(string $name): ?Field
    {
        return $this->fields[$name] ?? null;
    }
    public function entityName(): ?string
    {
        return $this->name;
    }

    /** UI helpers for Twig */
    public function uiSpec(): array
    {
        $spec = [];
        foreach ($this->fields as $name => $f) {
            $spec[$name] = [
                'name'  => $name,
                'label' => $f->getLabel() ?? ucfirst($name),
                'type'  => $f->getType()->value,
                'enum'  => $f->getEnum(),
                'meta'  => $f->getMeta(),
            ];
        }
        return $spec;
    }

    /** Migration metadata (portable, not SQL) */
    public function migrationSpec(): array
    {
        $spec = [];
        foreach ($this->fields as $name => $f) {
            $spec[$name] = [
                'type' => $f->getType()->value,
                'enum' => $f->getEnum(),
                'nullable' => ! $this->hasRequired($f),
                'index' => $f->getMeta()['index'] ?? false,
                'unique' => $f->getMeta()['unique'] ?? false,
                'default' => $f->getMeta()['default'] ?? null,
                'precision' => $f->getMeta()['precision'] ?? null,
                'scale' => $f->getMeta()['scale'] ?? null,
            ];
        }
        return $spec;
    }

    private function hasRequired(Field $f): bool
    {
        foreach ($f->getRules() as $r) {
            if ($r->name() === 'required') return true;
        }
        return false;
    }
}
