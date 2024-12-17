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

  /**
   * Fetches and stores segments from the Mautic API.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response containing the segments data.
   */
  public function fetchAndStoreSegments() {
    $logger = $this->getLogger('mautic_contacts');
    $logger->debug('Fetching and storing segments from Mautic API');
    
    try {
      $segments = $this->apiClient->getSegments();
      $logger->debug('Segments retrieved: @count', ['@count' => count($segments)]);
      
      // Store segments in shared tempstore
      $tempstore = \Drupal::service('tempstore.shared')->get('mautic_contacts');
      $tempstore->set('segments', $segments);
      
      return new JsonResponse($segments);
    }
    catch (\Exception $e) {
      $logger->error('Error fetching and storing segments: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['error' => 'Failed to fetch and store segments'], 500);
    }
  }

  /**
   * Maps Mautic segments to Drupal taxonomy terms based on the name.
   * Creates new taxonomy terms if they do not exist.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response containing the status of the operation.
   */
  public function mapSegmentsToTaxonomy() {
    $logger = $this->getLogger('mautic_contacts');
    $logger->debug('Mapping Mautic segments to Drupal taxonomy terms based on the name');
    
    try {
      $segments = $this->apiClient->getSegments();
      $logger->debug('Segments retrieved: @count', ['@count' => count($segments)]);
      
      $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
      $vocabulary = 'mautic_segments';
      $mapping = [];

      foreach ($segments as $segment_id => $segment) {
        if (!is_array($segment) || !isset($segment['name'])) {
          $logger->warning('Invalid segment data: @segment', ['@segment' => json_encode($segment)]);
          continue;
        }

        $logger->debug('Processing segment: @segment', ['@segment' => json_encode($segment)]);
        $terms = $term_storage->loadByProperties([
          'name' => $segment['name'],
          'vid' => $vocabulary,
        ]);

        if ($terms) {
          $term = reset($terms);
          $mapping[$segment['name']] = $term->id();
          $logger->debug('Mapped segment @segment_name to term @term_id', [
            '@segment_name' => $segment['name'],
            '@term_id' => $term->id(),
          ]);
        } else {
          // Create a new taxonomy term if it does not exist
          $term = $term_storage->create([
            'vid' => $vocabulary,
            'name' => $segment['name'],
          ]);
          $term->save();
          $mapping[$segment['name']] = $term->id();
          $logger->debug('Created and mapped new term for segment @segment_name with term ID @term_id', [
            '@segment_name' => $segment['name'],
            '@term_id' => $term->id(),
          ]);
        }
      }

      // Store the mapping in configuration
      $config = \Drupal::configFactory()->getEditable('mautic_contacts.settings');
      $config->set('segment_taxonomy_mapping', $mapping)->save();

      // Log the entire mapping result
      $logger->debug('Segment to taxonomy mapping: @mapping', ['@mapping' => json_encode($mapping)]);

      return new JsonResponse(['status' => 'Segments mapped to taxonomy terms successfully']);
    }
    catch (\Exception $e) {
      $logger->error('Error mapping segments to taxonomy terms: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['error' => 'Failed to map segments to taxonomy terms'], 500);
    }
  }

  /**
   * Retrieves the segment to taxonomy mapping.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response containing the mapping configuration.
   */
  public function getMapping() {
    $config = $this->config('mautic_contacts.settings');
    $mapping = $config->get('segment_taxonomy_mapping');
    return new JsonResponse($mapping);
  }

  /**
   * Fetches dynamic contents from the Mautic API.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response containing the dynamic contents data.
   */
  public function getDynamicContents() {
    $logger = $this->getLogger('mautic_contacts');
    $logger->debug('Fetching dynamic contents from Mautic API');
    
    try {
      $dynamicContents = $this->apiClient->getDynamicContents();
      $logger->debug('Dynamic contents retrieved: @count', ['@count' => count($dynamicContents)]);
      
      return new JsonResponse($dynamicContents);
    }
    catch (\Exception $e) {
      $logger->error('Error fetching dynamic contents: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['error' => 'Failed to fetch dynamic contents'], 500);
    }
  }

}
