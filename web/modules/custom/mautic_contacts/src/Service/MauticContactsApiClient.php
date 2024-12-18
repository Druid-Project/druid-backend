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
   * Fetches data from the Mautic API.
   *
   * @param string $endpoint
   *   The API endpoint to fetch data from.
   *
   * @return array
   *   An array of data or empty array if request fails.
   */
  public function fetchData(string $endpoint): array {
    try {
      $config = $this->configFactory->get('mautic.settings');
      
      $url = $config->get('url') . $endpoint;
      $this->logger->debug('Attempting to fetch data from: @url', ['@url' => $url]);
      
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
      $this->logger->debug('Data retrieved: @data', ['@data' => json_encode($data)]);

      return $data;
    }
    catch (GuzzleException $e) {
      $this->logger->error('Failed to fetch data from @endpoint: @error', [
        '@endpoint' => $endpoint,
        '@error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);
      return [];
    }
  }

  /**
   * Gets the HTTP client.
   *
   * @return \GuzzleHttp\ClientInterface
   *   The HTTP client.
   */
  public function getHttpClient(): ClientInterface {
    return $this->httpClient;
  }

}
