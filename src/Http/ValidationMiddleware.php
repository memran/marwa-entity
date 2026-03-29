<?php

declare(strict_types=1);

namespace Marwa\Entity\Http;

use Marwa\Entity\Contracts\ValidationErrorResponderInterface;
use Marwa\Entity\Entity\Entity;
use Marwa\Entity\Validation\ErrorBag;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Validates and sanitizes request input using the provided Entity.
 *
 * Attaches validated data to request attribute "validated".
 * On failure, delegates to a pluggable responder (JSON/HTML/etc).
 */
final class ValidationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly Entity $entity,
        private readonly ValidationErrorResponderInterface $responder,
        private readonly ?ContainerInterface $container = null,
        private readonly string $attribute = 'validated',
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $input = array_merge(
            $this->normalizeInput($request->getQueryParams()),
            $this->normalizeInput($request->getParsedBody()),
        );

        try {
            $ctx = ['container' => $this->container, 'request' => $request];
            $data = $this->entity->hydrate($input, $ctx);
            $request = $request->withAttribute($this->attribute, $data);

            return $handler->handle($request);
        } catch (\InvalidArgumentException $e) {
            return $this->responder->respond($this->decodeErrors($e), $request);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeInput(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

    private function decodeErrors(\InvalidArgumentException $exception): ErrorBag
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
