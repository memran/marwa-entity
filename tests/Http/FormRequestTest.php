<?php

declare(strict_types=1);

namespace Marwa\Entity\Tests\Http;

use Marwa\Entity\Entity\Entity;
use Marwa\Entity\Entity\EntitySchema;
use Marwa\Entity\Http\FormRequest;
use Marwa\Entity\Http\ValidationException;
use Marwa\Entity\Support\Sanitizers;
use Marwa\Entity\Validation\Rules\Min;
use Marwa\Entity\Validation\Rules\Required;
use Marwa\Entity\Validation\Validator;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

final class FormRequestTest extends TestCase
{
    public function testValidatedUsesMergedInputAndCachesTransformedPayload(): void
    {
        $schema = EntitySchema::make('users');
        $schema->string('name')
            ->rule(new Required(), new Min(3))
            ->sanitize(Sanitizers::trim());

        $entity = new Entity($schema, new Validator());
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn(['source' => 'query']);
        $request->method('getParsedBody')->willReturn(['name' => '  Alice  ']);
        $request->method('getUploadedFiles')->willReturn([]);
        $request->method('getHeader')->willReturn([]);

        $formRequest = new class ($request, $entity) extends FormRequest {
            public function __construct(ServerRequestInterface $request, private readonly Entity $entity)
            {
                parent::__construct($request);
            }

            protected function entity(): Entity
            {
                return $this->entity;
            }

            protected function passedValidation(array $validated): array
            {
                $validated['source'] = $this->input('source');

                return $validated;
            }
        };

        $validated = $formRequest->validated();

        self::assertSame(['name' => 'Alice', 'source' => 'query'], $validated);
        self::assertSame($validated, $formRequest->validated());
        self::assertFalse($formRequest->hasErrors());
    }

    public function testValidatedTurnsEntityErrorsIntoValidationException(): void
    {
        $schema = EntitySchema::make('users');
        $schema->string('name')->rule(new Required(), new Min(3));

        $entity = new Entity($schema, new Validator());
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn([]);
        $request->method('getParsedBody')->willReturn(['name' => 'ab']);
        $request->method('getUploadedFiles')->willReturn([]);
        $request->method('getHeader')->willReturn([]);

        $formRequest = new class ($request, $entity) extends FormRequest {
            public function __construct(ServerRequestInterface $request, private readonly Entity $entity)
            {
                parent::__construct($request);
            }

            protected function entity(): Entity
            {
                return $this->entity;
            }
        };

        try {
            $formRequest->validated();
            self::fail('Expected validation to fail.');
        } catch (ValidationException $e) {
            self::assertTrue($formRequest->hasErrors());
            self::assertSame(['The name must be at least 3.'], $e->errors()->get('name'));
        }
    }
}
