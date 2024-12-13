<?php

namespace Drupal\mautic_contacts\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Service for interacting with the Mautic Contacts API.
 */
class MauticContactsApiClient {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a MauticContactsApiClient object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(
    ClientInterface $http_client,
    LoggerChannelFactoryInterface $logger_factory,
    ConfigFactoryInterface $config_factory
  ) {
    $this->httpClient = $http_client;
    $this->logger = $logger_factory->get('mautic_contacts');
    $this->configFactory = $config_factory;
  }

  /**
   * Gets all contacts from Mautic API.
   *
   * @return array
   *   An array of contacts or empty array if request fails.
   */
  public function getContacts(): array {
    try {
      $config = $this->configFactory->get('mautic.settings');
      
      $url = $config->get('url') . '/api/contacts';
      $this->logger->debug('Attempting to fetch Mautic contacts from: @url', ['@url' => $url]);
      
      $auth = [
        $config->get('username'),
        $config->get('password'),
      ];
      
      $this->logger->debug('Using configured auth credentials');

      $response = $this->httpClient->request('GET', $url, [
        'auth' => $auth,
        'headers' => [
          'Accept' => 'application/json',
        ],
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);
      $this->logger->debug('API Response status: @status', ['@status' => $response->getStatusCode()]);

      return $data;
    }
    catch (GuzzleException $e) {
      $this->logger->error('Failed to fetch Mautic contacts: @error', [
        '@error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);
      return [];
    }
  }

  /**
   * Gets segments for a specific contact from Mautic API.
   *
   * @param int $contactId
   *   The Mautic contact ID.
   *
   * @return array
   *   An array of segments or empty array if request fails.
   */
  public function getContactSegments(int $contactId): array {
    try {
      $config = $this->configFactory->get('mautic.settings');
      
      $url = $config->get('url') . '/api/contacts/' . $contactId . '/segments';
      $this->logger->debug('Attempting to fetch segments for contact @id from: @url', [
        '@id' => $contactId,
        '@url' => $url,
      ]);
      
      $auth = [
        $config->get('username'),
        $config->get('password'),
      ];

      $response = $this->httpClient->request('GET', $url, [
        'auth' => $auth,
        'headers' => [
          'Accept' => 'application/json',
        ],
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);
      $this->logger->debug('API Response status: @status', ['@status' => $response->getStatusCode()]);

      return $data;
    }
    catch (GuzzleException $e) {
      $this->logger->error('Failed to fetch segments for contact @id: @error', [
        '@id' => $contactId,
        '@error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);
      return [];
    }
  }

  /**
   * Gets all segments from Mautic API.
   *
   * @return array
   *   An array of segments or empty array if request fails.
   */
  public function getSegments(): array {
    try {
      $config = $this->configFactory->get('mautic.settings');
      
      $url = $config->get('url') . '/api/segments';
      $this->logger->debug('Attempting to fetch Mautic segments from: @url', ['@url' => $url]);
      
      $auth = [
        $config->get('username'),
        $config->get('password'),
      ];
      
      $this->logger->debug('Using configured auth credentials');

      $response = $this->httpClient->request('GET', $url, [
        'auth' => $auth,
        'headers' => [
          'Accept' => 'application/json',
        ],
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);
      $this->logger->debug('API Response status: @status', ['@status' => $response->getStatusCode()]);
      $this->logger->debug('Segments data: @data', ['@data' => json_encode($data)]);

      // Extract the segments from the nested structure
      if (isset($data['lists'])) {
        $data = $data['lists'];
      }

      return $data;
    }
    catch (GuzzleException $e) {
      $this->logger->error('Failed to fetch Mautic segments: @error', [
        '@error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);
      return [];
    }
  }

}
