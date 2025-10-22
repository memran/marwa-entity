<?php
namespace Marwa\Entity\Migration;

use Marwa\Entity\Entity\EntitySchema;

final class SchemaMigration
{
    /** Returns a portable array you can feed to your own migrator/DDL generator. */
    public static function toArray(EntitySchema $schema): array
    {
        return [
            'entity' => $schema->entityName(),
            'columns' => $schema->migrationSpec(),
        ];
    }
}
