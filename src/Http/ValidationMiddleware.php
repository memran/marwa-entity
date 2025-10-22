<?php

namespace Marwa\Entity\Http;

use Marwa\Entity\Entity\Entity;
use Marwa\Entity\Validation\ErrorBag;
use Marwa\Entity\Contracts\ValidationErrorResponderInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

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
        private readonly string $attribute = 'validated'
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Merge query + parsed body (typical web forms; customize upstream if needed)
        $input = array_merge(
            $request->getQueryParams(),
            (array)($request->getParsedBody() ?? [])
        );

        try {
            $ctx = ['container' => $this->container, 'request' => $request];
            $data = $this->entity->hydrate($input, $ctx);
            $request = $request->withAttribute($this->attribute, $data);

            return $handler->handle($request);
        } catch (\InvalidArgumentException $e) {
            // hydrate() throws with JSON-encoded errors; rehydrate into ErrorBag shape
            $payload = json_decode($e->getMessage(), true) ?: [];
            $bag = new ErrorBag();
            foreach ($payload as $field => $messages) {
                foreach ((array)$messages as $m) {
                    $bag->add($field, (string)$m);
                }
            }
            return $this->responder->respond($bag, $request);
        }
    }
}
