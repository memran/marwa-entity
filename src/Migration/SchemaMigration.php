<?php

declare(strict_types=1);

namespace Marwa\Entity\Migration;

use Marwa\Entity\Entity\EntitySchema;

final class SchemaMigration
{
    /**
     * Returns a portable array you can feed to your own migrator/DDL generator.
     *
     * @return array{entity: string|null, columns: array<string, array<string, mixed>>}
     */
    public static function toArray(EntitySchema $schema): array
    {
        return [
            'entity' => $schema->entityName(),
            'columns' => $schema->migrationSpec(),
        ];
    }
}
