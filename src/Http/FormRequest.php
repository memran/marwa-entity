<?php

declare(strict_types=1);

namespace Marwa\Entity\Http;

use Marwa\Entity\Entity\Entity;
use Marwa\Entity\Validation\ErrorBag;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Base class to:
 *  - bind a PSR-7 ServerRequest
 *  - gather input (query + parsed body by default)
 *  - validate/sanitize via Marwa\Entity\Entity
 *  - provide convenient accessors
 *
 * Usage:
 *   final class UserStoreRequest extends FormRequest {
 *       protected function entity(): Entity { return $this->userEntity; }
 *       public function __construct(ServerRequestInterface $r, Entity $userEntity, ?ContainerInterface $c = null) {
 *           parent::__construct($r, $c);
 *           $this->userEntity = $userEntity;
 *       }
 *   }
 *
 * In your handler:
 *   $req = new UserStoreRequest($request, $entity, $container);
 *   $data = $req->validated(); // sanitized + typed
 */
abstract class FormRequest
{
    /** @var array<string, mixed>|null */
    private ?array $cachedInput = null;

    /** @var array<string, mixed>|null */
    private ?array $cachedValidated = null;
    private ?ErrorBag $cachedErrors = null;

    public function __construct(
        private readonly ServerRequestInterface $request,
        private readonly ?ContainerInterface $container = null,
    ) {}

    abstract protected function entity(): Entity;

    protected function authorize(): bool
    {
        return true;
    }

    protected function failedAuthorization(): never
    {
        throw new \RuntimeException('This action is unauthorized.', 403);
    }

    /**
     * Collect raw input. Override to customize source/merge order.
     *
     * @return array<string, mixed>
     */
    protected function collectInput(): array
    {
        return array_merge($this->normalizeInput($this->request->getQueryParams()), $this->parsedBody());
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     */
    protected function prepareForValidation(array $input): array
    {
        return $input;
    }

    /**
     * @param array<string, mixed> $validated
     *
     * @return array<string, mixed>
     */
    protected function passedValidation(array $validated): array
    {
        return $validated;
    }

    /**
     * @return array<string, mixed>
     */
    public function validated(): array
    {
        if ($this->cachedValidated !== null) {
            return $this->cachedValidated;
        }

        if (! $this->authorize()) {
            $this->failedAuthorization();
        }

        $input = $this->prepareForValidation($this->all());

        try {
            $validated = $this->entity()->hydrate($input, [
                'container' => $this->container,
                'request' => $this->request,
            ]);

            $this->cachedValidated = $this->passedValidation($validated);
            $this->cachedErrors = null;

            return $this->cachedValidated;
        } catch (\InvalidArgumentException $e) {
            $this->cachedErrors = self::decodeErrors($e);

            throw new ValidationException($this->cachedErrors, previous: $e);
        }
    }

    public function hasErrors(): bool
    {
        return $this->cachedErrors?->hasAny() === true;
    }

    public function errors(): ?ErrorBag
    {
        return $this->cachedErrors;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->cachedInput ??= $this->collectInput();
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->normalizeInput($this->request->getQueryParams())[$key] ?? $default;
    }

    public function body(string $key, mixed $default = null): mixed
    {
        return $this->parsedBody()[$key] ?? $default;
    }

    public function file(string $key): ?\Psr\Http\Message\UploadedFileInterface
    {
        $file = $this->request->getUploadedFiles()[$key] ?? null;

        return $file instanceof \Psr\Http\Message\UploadedFileInterface ? $file : null;
    }

    public function header(string $name, mixed $default = null): mixed
    {
        $values = $this->request->getHeader($name);

        return $values[0] ?? $default;
    }

    public function request(): ServerRequestInterface
    {
        return $this->request;
    }

    public function container(): ?ContainerInterface
    {
        return $this->container;
    }

    /**
     * @param mixed $value
     *
     * @return array<string, mixed>
     */
    private function normalizeInput(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function parsedBody(): array
    {
        return $this->normalizeInput($this->request->getParsedBody());
    }

    private static function decodeErrors(\InvalidArgumentException $exception): ErrorBag
    {
        $decoded = json_decode($exception->getMessage(), true);
        $bag = new ErrorBag();

        if (! is_array($decoded)) {
            $bag->add('general', $exception->getMessage());

            return $bag;
        }

        foreach ($decoded as $field => $messages) {
            foreach ((array) $messages as $message) {
                $bag->add((string) $field, (string) $message);
            }
        }

        return $bag;
    }
}
