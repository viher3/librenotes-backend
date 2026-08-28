<?php

namespace App\Core\Infrastructure\Framework\Symfony\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiResponseSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse'],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        // Apply only for json responses
        if ($event->getResponse()->headers->get('Content-Type') !== "application/json") {
            return;
        }

        $response = json_decode($event->getResponse()->getContent(), true);
        $params = $event->getRequest()->query->all();
        $response = $this->normalizeResult($response, $params);

        $response = new JsonResponse($response, $event->getResponse()->getStatusCode());
        $event->setResponse($response);
    }

    private function normalizeResult(
        array $response,
        array $params
    ): array
    {
        if (!empty($response['total'])) {
            $total = (int) $response['total'];
            $response['size'] = count($response['data']);
            $response['page'] = $this->calculatePage($params);
            $response['last'] = $this->calculateLast($params, $total);
        }

        return $response;
    }

    /**
     * calculate current page
     *
     * @param array $params
     * @return int
     */
    private function calculatePage(array $params): int
    {
        if (isset($params['page']) && (int)$params['page'] > 0) {
            $page = (int)$params['page'];
        } else {
            $page = 1;
        }

        return $page;
    }

    /**
     * Calculates last page
     *
     * @param array $params
     * @param int $total
     * @return int
     */
    private function calculateLast(array $params, int $total): int
    {
        if (isset($params['size']) && (int)$params['size'] > 0) {
            $last = (int)ceil($total / (int)$params['size']);
        } else {
            $last = 1;
        };

        return $last;
    }
}