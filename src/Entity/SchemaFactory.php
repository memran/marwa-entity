<?php

namespace Marwa\Entity\Entity;

final class SchemaFactory
{
      /**
       * Build schema from a normalized array.
       * Shape:
       * [
       *   'name' => 'users',
       *   'fields' => [
       *     'email' => [
       *       'type' => 'string',     // string|integer|boolean|decimal|datetime|json|enum
       *       'label' => 'Email',
       *       'enum' => null,         // or ['A', 'B']
       *       'meta' => ['unique' => true, 'widget' => 'email'],
       *       'rules' => [
       *          ['name' => 'required', 'params' => []],
       *          ['name' => 'min', 'params' => ['min' => 3]],
       *          // your app maps names → rule instances
       *       ],
       *       'sanitize' => ['trim','lower'] // your app maps names → closures
       *     ],
       *     ...
       *   ]
       * ]
       */
      public static function fromArray(array $def, callable $ruleResolver, callable $sanitizerResolver): EntitySchema
      {
            $schema = EntitySchema::make($def['name'] ?? null);

            foreach ($def['fields'] as $name => $cfg) {
                  $type = match (strtolower((string)($cfg['type'] ?? 'string'))) {
                        'string' => Types::String,
                        'integer' => Types::Integer,
                        'boolean' => Types::Boolean,
                        'decimal' => Types::Decimal,
                        'datetime' => Types::DateTime,
                        'json' => Types::Json,
                        'enum' => Types::Enum,
                        default => Types::String,
                  };

                  $f = Field::make($name)->type($type);

                  if (!empty($cfg['label'])) $f->label((string)$cfg['label']);
                  if (!empty($cfg['enum']) && is_array($cfg['enum'])) $f->enum($cfg['enum']);
                  if (!empty($cfg['meta']) && is_array($cfg['meta'])) {
                        foreach ($cfg['meta'] as $k => $v) $f->meta((string)$k, $v);
                  }

                  // rules
                  if (!empty($cfg['rules']) && is_array($cfg['rules'])) {
                        $rules = [];
                        foreach ($cfg['rules'] as $r) {
                              $rules[] = $ruleResolver($r['name'], $r['params'] ?? []);
                        }
                        $f->rule(...$rules);
                  }

                  // sanitizers
                  if (!empty($cfg['sanitize']) && is_array($cfg['sanitize'])) {
                        $san = [];
                        foreach ($cfg['sanitize'] as $s) {
                              $san[] = $sanitizerResolver($s);
                        }
                        $f->sanitize(...$san);
                  }

                  $schema->field($f);
            }

            return $schema;
      }

      /** No YAML dependency here; your app passes decoded array. */
      public static function fromYaml(string $yaml, callable $yamlDecoder, callable $ruleResolver, callable $sanitizerResolver): EntitySchema
      {
            $array = $yamlDecoder($yaml); // e.g., fn($y)=>Symfony\Component\Yaml\Yaml::parse($y)
            return self::fromArray($array, $ruleResolver, $sanitizerResolver);
      }

      public static function fromJson(string $json, callable $ruleResolver, callable $sanitizerResolver): EntitySchema
      {
            $array = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
            return self::fromArray($array, $ruleResolver, $sanitizerResolver);
      }
}
