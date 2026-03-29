<?php

declare(strict_types=1);

namespace Marwa\Entity\Tests\Validation;

use Marwa\Entity\Entity\EntitySchema;
use Marwa\Entity\Validation\Rules\Confirmed;
use Marwa\Entity\Validation\Rules\Min;
use Marwa\Entity\Validation\Rules\Nullable;
use Marwa\Entity\Validation\Rules\Required;
use Marwa\Entity\Validation\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function testNullableSkipsSubsequentRulesForEmptyValues(): void
    {
        $schema = EntitySchema::make('users');
        $schema->string('nickname')->rule(new Nullable(), new Min(3));

        $errors = (new Validator())->validate($schema, ['nickname' => '']);

        self::assertFalse($errors->hasAny());
    }

    public function testConfirmedUsesRawInputAndFieldContext(): void
    {
        $schema = EntitySchema::make('users');
        $schema->string('password')->rule(new Required(), new Confirmed());

        $validator = new Validator();
        $errors = $validator->validate($schema, ['password' => 'secret'], [
            'input' => [
                'password' => 'secret',
                'password_confirmation' => 'secret',
            ],
        ]);

        self::assertFalse($errors->hasAny());
    }
}
