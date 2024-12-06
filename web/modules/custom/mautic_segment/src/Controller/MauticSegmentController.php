<?php

namespace Drupal\mautic_segment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\mautic_segment\Entity\MauticSegment;
use Drupal\mautic_segment\Service\MauticService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Handles requests to log MTC ID.
 */
class MauticSegmentController extends ControllerBase
{
    /**
     * @var MauticService
     */
    protected $mauticService;

    /**
     * @var LoggerChannelFactoryInterface
     */
    protected $loggerFactory;

    /**
     * Constructor.
     */
    public function __construct(MauticService $mauticService, LoggerChannelFactoryInterface $loggerFactory)
    {
        $this->mauticService = $mauticService;
        $this->loggerFactory = $loggerFactory;
        $this->loggerFactory->get('mautic_segment')->debug('MauticSegmentController constructor called');
    }

    /**
     * Dependency Injection.
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('mautic_segment.mautic_service'),
            $container->get('logger.factory')
        );
    }

    /**
     * Log the MTC ID.
     */
    public function logMtcId(Request $request)
    {
        $this->loggerFactory->get('mautic_segment')->debug('logMtcId method called');
        // Parse JSON body.
        $data = json_decode($request->getContent(), true);
        $this->loggerFactory->get('mautic_segment')->info('Received request data: @data', ['@data' => json_encode($data)]);

        // Validate payload.
        if (!isset($data['data']['attributes']['mtc_id'])) {
            $this->loggerFactory->get('mautic_segment')->error('MTC ID is required');
            return new JsonResponse(['error' => 'mtc_id is required'], 400);
        }

        $mtcId = $data['data']['attributes']['mtc_id'];
        $this->loggerFactory->get('mautic_segment')->info('MTC ID: @mtc_id', ['@mtc_id' => $mtcId]);

        // Log the mtc_id using MauticService.
        if ($this->mauticService->logMtcId($mtcId)) {
            // Check if an entity with the given MTC ID already exists.
            $this->loggerFactory->get('mautic_segment')->info('Checking for existing entity with MTC ID: @mtc_id', ['@mtc_id' => $mtcId]);
            $existing_entity = \Drupal::entityTypeManager()
                ->getStorage('mautic_segment')
                ->loadByProperties(['mtc_id' => $mtcId]);

            if ($existing_entity) {
                // Update the existing entity.
                $mautic_segment = reset($existing_entity);
                $this->loggerFactory->get('mautic_segment')->info('Updating existing Mautic Segment entity with ID: @id', ['@id' => $mautic_segment->id()]);
            } else {
                // Create a new entity.
                $mautic_segment = MauticSegment::create(['mtc_id' => $mtcId]);
                $this->loggerFactory->get('mautic_segment')->info('Creating new Mautic Segment entity');
            }

            $mautic_segment->save();
            $this->loggerFactory->get('mautic_segment')->info('Entity saved with ID: @id', ['@id' => $mautic_segment->id()]);

            // Generate the URL for the created or updated entity.
            try {
                $url = $mautic_segment->toUrl('canonical', ['absolute' => true])->toString();
                $this->loggerFactory->get('mautic_segment')->info('Generated URL for Mautic Segment entity: @url', ['@url' => $url]);
                return new JsonResponse(['message' => 'MTC ID logged successfully', 'url' => $url], 200);
            } catch (\Exception $e) {
                $this->loggerFactory->get('mautic_segment')->error('Error generating URL for Mautic Segment entity: @message', ['@message' => $e->getMessage()]);
                return new JsonResponse(['error' => 'Failed to generate URL for Mautic Segment entity'], 500);
            }
        } else {
            $this->loggerFactory->get('mautic_segment')->error('Failed to log MTC ID');
            return new JsonResponse(['error' => 'Failed to log MTC ID'], 500);
        }
    }
}
