<?php

namespace Drupal\mautic_contacts\Controller;

use Drupal\Core\Controller\ControllerBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Controller for handling Mautic contact ID operations.
 *
 * @package Drupal\mautic_contacts\Controller
 */
class MtcIdController extends ControllerBase {

  /**
   * The logger channel.
   */
  const LOGGER_CHANNEL = 'mautic_contacts';

  /**
   * Required attribute key for Mautic ID.
   */
  const MTC_ID_ATTRIBUTE = 'mtc_id';

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new MtcIdController object.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   */
  public function __construct(LoggerInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.factory')->get(static::LOGGER_CHANNEL)
    );
  }

  /**
   * Handles the incoming request to log the Mautic contact ID.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The incoming request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response following JSON:API specification.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   Thrown when the request data is invalid.
   */
  public function logMtcId(Request $request): JsonResponse {
    $content = $request->getContent();
    
    if (empty($content)) {
      throw new BadRequestHttpException('Empty request body.');
    }

    $data = json_decode($content, TRUE);
    if (json_last_error() !== JSON_ERROR_NONE) {
      throw new BadRequestHttpException('Invalid JSON format.');
    }

    if (empty($data['data']['attributes'][static::MTC_ID_ATTRIBUTE])) {
      throw new BadRequestHttpException(sprintf('Missing required attribute: %s', static::MTC_ID_ATTRIBUTE));
    }

    $mtc_id = $data['data']['attributes'][static::MTC_ID_ATTRIBUTE];

    try {
      // Use shared tempstore instead of private
      $tempstore = \Drupal::service('tempstore.shared')->get('mautic_contacts');
      $tempstore->set('mtc_id', $mtc_id);
      
      // Verify storage
      $stored_id = $tempstore->get('mtc_id');
      $this->logger->debug('Stored MTC ID: @id', ['@id' => $stored_id]);

      if (!$stored_id) {
        throw new \Exception('Failed to store MTC ID');
      }

      // Log the Mautic contact ID with context.
      $this->logger->info('Mautic contact ID received: @mtc_id', [
        '@mtc_id' => $mtc_id,
        'request_uri' => $request->getRequestUri(),
        'method' => $request->getMethod(),
      ]);

      return new JsonResponse([
        'data' => [
          'type' => 'mautic_contact_log',
          'attributes' => [
            static::MTC_ID_ATTRIBUTE => $mtc_id,
            'status' => 'success',
            'stored' => true
          ],
        ],
      ], Response::HTTP_OK);
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to store MTC ID: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse([
        'error' => 'Failed to store contact ID',
        'details' => $e->getMessage()
      ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

}
