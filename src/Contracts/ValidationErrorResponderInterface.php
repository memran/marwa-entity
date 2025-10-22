<?php

namespace Marwa\Entity\Contracts;

use Marwa\Entity\Validation\ErrorBag;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface ValidationErrorResponderInterface
{
      public function respond(ErrorBag $errors, ServerRequestInterface $request): ResponseInterface;
}
