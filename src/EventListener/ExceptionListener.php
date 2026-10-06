<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelInterface;

final class ExceptionListener
{
    public function __construct(private KernelInterface $kernel)
    {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $e = $event->getThrowable();

        $data = [
            'error' => $e->getMessage(),
        ];

        if ($this->kernel->isDebug()) {
            $data['debug'] = [
                'class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => explode("\n", $e->getTraceAsString()),
            ];
        }

        $statusCode = $e instanceof HttpExceptionInterface
        ? $e->getStatusCode()
        : 500;

        $headers = $e instanceof HttpExceptionInterface
        ? $e->getHeaders()
        : [];

        $event->setResponse(new JsonResponse($data, $statusCode, $headers));
    }
}
