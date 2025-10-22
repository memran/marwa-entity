<?php
namespace Marwa\Entity\Entity;

enum Types: string
{
    case String = 'string';
    case Integer = 'integer';
    case Boolean = 'boolean';
    case Decimal = 'decimal';
    case DateTime = 'datetime';
    case Enum = 'enum';
    case Json = 'json';
}
