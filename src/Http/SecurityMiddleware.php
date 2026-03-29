<?php

declare(strict_types=1);

namespace Marwa\Entity\Http;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Minimal request/response hardening:
 *  - Max body size guard
 *  - Optional content-type enforcement
 *  - Sensible security headers on responses
 */
final class SecurityMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly int $maxBodyBytes = 1_000_000,
        private readonly bool $enforceJson = false,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $lenHeader = $request->getHeaderLine('Content-Length');
        $len = ctype_digit($lenHeader) ? (int) $lenHeader : 0;

        if ($len > $this->maxBodyBytes) {
            return $this->deny(413, 'Payload too large');
        }

        if ($this->enforceJson) {
            $ct = strtolower(trim(strtok($request->getHeaderLine('Content-Type'), ';') ?: ''));
            if ($ct !== '' && $ct !== 'application/json') {
                return $this->deny(415, 'Unsupported Media Type');
            }
        }

        $response = $handler->handle($request);

        // Apply standard hardening headers
        return $response
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('X-Frame-Options', 'SAMEORIGIN')
            ->withHeader('Referrer-Policy', 'no-referrer')
            ->withHeader('X-XSS-Protection', '0');
    }

    private function deny(int $status, string $message): ResponseInterface
    {
        $body = $this->streamFactory->createStream(
            json_encode(
                ['error' => $message],
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            ),
        );

        return $this->responseFactory->createResponse($status)
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Cache-Control', 'no-store, max-age=0')
            ->withBody($body);
    }
}
