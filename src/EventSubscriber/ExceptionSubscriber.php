<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class ExceptionSubscriber implements EventSubscriberInterface
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        
        // Only handle API requests
        if (str_starts_with($request->getPathInfo(), '/api/')) {
            $exception = $event->getThrowable();
            
            $statusCode = $exception instanceof HttpExceptionInterface
                ? $exception->getStatusCode()
                : 500;
            
            $response = new JsonResponse([
                'status' => 'error',
                'message' => $exception->getMessage(),
                'code' => $statusCode,
            ], $statusCode);
            
            $event->setResponse($response);
            
            // Log the error for troubleshooting
            error_log('API Error: ' . $exception->getMessage());
            error_log('Stack trace: ' . $exception->getTraceAsString());
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }
}
