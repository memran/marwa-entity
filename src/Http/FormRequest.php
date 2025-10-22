<?php

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
      private ServerRequestInterface $request;
      private ?ContainerInterface $container;
      private ?array $cachedValidated = null;
      private ?ErrorBag $cachedErrors = null;

      public function __construct(ServerRequestInterface $request, ?ContainerInterface $container = null)
      {
            $this->request = $request;
            $this->container = $container;
      }

      /** Provide the Entity that defines schema + validation for this request. */
      abstract protected function entity(): Entity;

      /** Authorization hook. Override in child; return false to deny early. */
      protected function authorize(): bool
      {
            return true;
      }

      /** Called when authorize() returns false; override to customize. */
      protected function failedAuthorization(): never
      {
            throw new \RuntimeException('This action is unauthorized.', 403);
      }

      /**
       * Collect raw input. Override to customize source/merge order.
       * By default: query params + parsed body (arrays only).
       */
      protected function collectInput(): array
      {
            $query = $this->request->getQueryParams();
            $body  = $this->request->getParsedBody();

            $query = is_array($query) ? $query : [];
            $body  = is_array($body)  ? $body  : [];

            return array_merge($query, $body);
      }

      /**
       * Pre-validation hook to mutate inputs (e.g. map keys, flatten arrays).
       * Return the transformed array.
       */
      protected function prepareForValidation(array $input): array
      {
            return $input;
      }

      /**
       * Post-validation hook to adjust the validated data (e.g. computed fields).
       * Only called when validation passes.
       */
      protected function passedValidation(array $validated): array
      {
            return $validated;
      }

      /**
       * Validate lazily and cache results. Throws ValidationException on error.
       */
      public function validated(): array
      {
            if ($this->cachedValidated !== null) {
                  return $this->cachedValidated;
            }

            if (!$this->authorize()) {
                  $this->failedAuthorization(); // never returns
            }

            $input = $this->prepareForValidation($this->collectInput());

            try {
                  $context = [
                        'container' => $this->container,
                        'request'   => $this->request,
                  ];

                  $validated = $this->entity()->hydrate($input, $context);
                  $validated = $this->passedValidation($validated);

                  $this->cachedValidated = $validated;
                  $this->cachedErrors = null;

                  return $validated;
            } catch (\InvalidArgumentException $e) {
                  // Entity::hydrate throws with JSON-encoded errors
                  $decoded = json_decode($e->getMessage(), true) ?: [];
                  $bag = new ErrorBag();
                  foreach ($decoded as $field => $messages) {
                        foreach ((array)$messages as $m) {
                              $bag->add($field, (string)$m);
                        }
                  }
                  $this->cachedErrors = $bag;
                  throw new ValidationException($bag);
            }
      }

      /** True if validated() has failed and captured errors. */
      public function hasErrors(): bool
      {
            return $this->cachedErrors?->hasAny() === true;
      }

      /** Access the captured ErrorBag after catching ValidationException. */
      public function errors(): ?ErrorBag
      {
            return $this->cachedErrors;
      }

      /** Raw input accessor (unvalidated). */
      public function all(): array
      {
            return $this->collectInput();
      }

      /** Convenience getters */
      public function input(string $key, mixed $default = null): mixed
      {
            $data = $this->collectInput();
            return $data[$key] ?? $default;
      }

      public function query(string $key, mixed $default = null): mixed
      {
            $q = $this->request->getQueryParams();
            return is_array($q) ? ($q[$key] ?? $default) : $default;
      }

      public function body(string $key, mixed $default = null): mixed
      {
            $b = $this->request->getParsedBody();
            return is_array($b) ? ($b[$key] ?? $default) : $default;
      }

      /** Uploaded files (PSR-7) */
      public function file(string $key): ?\Psr\Http\Message\UploadedFileInterface
      {
            $files = $this->request->getUploadedFiles();
            return $files[$key] ?? null;
      }

      /** Headers helper */
      public function header(string $name, mixed $default = null): mixed
      {
            $values = $this->request->getHeader($name);
            return $values[0] ?? $default;
      }

      /** The underlying PSR-7 request */
      public function request(): ServerRequestInterface
      {
            return $this->request;
      }

      /** Optional container accessor */
      public function container(): ?ContainerInterface
      {
            return $this->container;
      }
}
