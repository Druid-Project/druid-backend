<?php

namespace Drupal\mautic_api_integration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\mautic_api_integration\Service\MauticApiClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for handling Mautic API integration.
 */
class MauticApiController extends ControllerBase
{

    /**
     * The Mautic API client service.
     *
     * @var \Drupal\mautic_api_integration\Service\MauticApiClient
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
     * @param \Drupal\mautic_api_integration\Service\MauticApiClient $mautic_api_client
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

    /**
     * Handles the POST request to log mtc_id.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The incoming request.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   The JSON response.
     */
    public function logMtcId(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (isset($data['data']['attributes']['mtc_id'])) {
            $mtcId = $data['data']['attributes']['mtc_id'];
            $this->logger->info('Received mtc_id: @mtc_id', ['@mtc_id' => $mtcId]);

            // Store the mtc_id in the key-value store.
            $this->mauticApiClient->storeMtcId($mtcId);

            return new JsonResponse(['message' => 'mtc_id logged successfully'], 200);
        } else {
            $this->logger->error('mtc_id not provided in the request.');
            return new JsonResponse(['error' => 'mtc_id not provided'], 400);
        }
    }

    /**
     * Provides a JSON response with the stored mtc_id.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   The JSON response containing the stored mtc_id.
     */
    public function getMtcId(): JsonResponse
    {
        $mtcId = $this->mauticApiClient->getStoredMtcId();
        if ($mtcId) {
            return new JsonResponse(['mtc_id' => $mtcId], 200);
        } else {
            return new JsonResponse(['error' => 'No mtc_id found'], 404);
        }
    }
}
