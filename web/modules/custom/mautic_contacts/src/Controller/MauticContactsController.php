<?php

namespace Drupal\mautic_contacts\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\mautic_contacts\Service\MauticContactsApiClient;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for fetching Mautic contacts.
 */
class MauticContactsController extends ControllerBase {

  /**
   * The Mautic contacts API client.
   *
   * @var \Drupal\mautic_contacts\Service\MauticContactsApiClient
   */
  protected $apiClient;

  /**
   * Constructs a MauticContactsController object.
   *
   * @param \Drupal\mautic_contacts\Service\MauticContactsApiClient $api_client
   *   The Mautic contacts API client.
   */
  public function __construct(MauticContactsApiClient $api_client) {
    $this->apiClient = $api_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('mautic_contacts.api_client')
    );
  }

  /**
   * Returns a list of Mautic contacts.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response containing Mautic contacts.
   */
  public function getContacts() {
    $this->getLogger('mautic_contacts')->debug('Accessing mautic contacts endpoint');
    
    try {
      $contacts = $this->apiClient->getContacts();
      $this->getLogger('mautic_contacts')->debug('Contacts retrieved: @count', ['@count' => count($contacts)]);
      
      return new JsonResponse($contacts);
    }
    catch (\Exception $e) {
      $this->getLogger('mautic_contacts')->error('Error getting contacts: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['error' => 'Failed to retrieve contacts'], 500);
    }
  }

  /**
   * Returns segments for a specific Mautic contact.
   *
   * @param int $mtc_id
   *   The Mautic contact ID.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response containing contact segments.
   */
  public function getContactSegmentsById($mtc_id) {
    $this->getLogger('mautic_contacts')->debug('Accessing mautic contact segments for ID: @id', ['@id' => $mtc_id]);
    
    try {
      $segments = $this->apiClient->getContactSegments($mtc_id);
      return new JsonResponse($segments);
    }
    catch (\Exception $e) {
      $this->getLogger('mautic_contacts')->error('Error getting segments: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['error' => 'Failed to retrieve segments'], 500);
    }
  }

  /**
   * Returns segments for the stored Mautic contact.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response containing contact segments.
   */
  public function getContactSegments() {
    $logger = $this->getLogger('mautic_contacts');
    $logger->debug('Accessing mautic contact segments');
    
    try {
      // Use shared tempstore instead of private
      $tempstore = \Drupal::service('tempstore.shared')->get('mautic_contacts');
      $mtc_id = $tempstore->get('mtc_id');
      
      $logger->debug('Retrieved stored MTC ID: @id', ['@id' => $mtc_id]);
      
      if (empty($mtc_id)) {
        $logger->warning('No MTC ID found in storage');
        return new JsonResponse([
          'error' => 'No contact ID found. Please POST to /api/mautic-contacts/mtc_id first',
          'code' => 'MISSING_CONTACT_ID'
        ], 404);
      }

      $segments = $this->apiClient->getContactSegments($mtc_id);
      $logger->debug('Segments retrieved for MTC ID @id: @segments', [
        '@id' => $mtc_id,
        '@segments' => json_encode($segments)
      ]);
      
      if (empty($segments)) {
        return new JsonResponse([
          'total' => 0,
          'lists' => []
        ]);
      }
      
      return new JsonResponse($segments);
    }
    catch (\Exception $e) {
      $logger->error('Error getting segments: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse([
        'error' => 'Failed to retrieve segments',
        'details' => $e->getMessage()
      ], 500);
    }
  }

}
