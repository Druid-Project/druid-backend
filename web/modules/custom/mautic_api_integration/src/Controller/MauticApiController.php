<?php

namespace Drupal\mautic_api_integration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\mautic_api_integration\MauticApiClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for handling Mautic API integration.
 */
class MauticApiController extends ControllerBase
{

    /**
     * The Mautic API client service.
     *
     * @var \Drupal\mautic_api_integration\MauticApiClient
     */
    protected MauticApiClient $mauticApiClient;

    /**
     * The logger service.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * Constructs a new MauticApiController object.
     *
     * @param \Drupal\mautic_api_integration\MauticApiClient $mautic_api_client
     *   The Mautic API client.
     * @param \Psr\Log\LoggerInterface $logger
     *   The logger service.
     */
    public function __construct(MauticApiClient $mautic_api_client, LoggerInterface $logger)
    {
        $this->mauticApiClient = $mautic_api_client;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): self
    {
        return new static(
            $container->get('mautic_api_integration.client'),
            $container->get('logger.factory')->get('mautic_api_integration')
        );
    }

    /**
     * Provides a JSON response with Mautic segment data.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   The JSON response containing the Mautic segment data.
     */
    public function getSegments(): JsonResponse
    {
        // Log the start of the segment fetch process.
        $this->logger->debug('Fetching Mautic segments from cache or live.');

        // Fetch segments from cache or live if not cached.
        $segments = $this->mauticApiClient->getCachedSegments();
        if (empty($segments)) {
            $segments = $this->mauticApiClient->fetchSegments();
        }

        // If no segments are found, log an error and return a failure response.
        if (!$segments) {
            $this->logger->error('Failed to fetch Mautic segments.');
            return new JsonResponse(['error' => 'Failed to fetch Mautic segments.'], 500);
        }

        // Log the fetched segment data for debugging.
        $this->logger->debug('Returning segments data: @segments', ['@segments' => print_r($segments, true)]);

        // Optionally, log to PHP error log.
        error_log('Returning segments data: ' . print_r($segments, true));

        // Return the fetched segments in a JSON response.
        return new JsonResponse($segments);
    }

}
