<?php

namespace Drupal\mautic_api_integration;

use Drupal\Component\Serialization\Json;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Class to interact with the Mautic API.
 */
class MauticApiClient
{

    /**
     * The HTTP client for making API requests.
     *
     * @var \GuzzleHttp\ClientInterface
     */
    protected ClientInterface $httpClient;

    /**
     * The logger service.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * The key-value store service.
     *
     * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
     */
    protected $store;

    /**
     * Constructs a MauticApiClient object.
     *
     * @param \GuzzleHttp\ClientInterface $http_client
     *   The HTTP client.
     * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
     *   The logger channel factory.
     * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value
     *   The key-value store factory.
     */
    public function __construct(ClientInterface $http_client, LoggerChannelFactoryInterface $logger_factory, KeyValueFactoryInterface $key_value)
    {
        $this->httpClient = $http_client;
        $this->logger = $logger_factory->get('mautic_api_integration');
        $this->store = $key_value->get('mautic_api_integration');
    }

    /**
     * Fetches Mautic segments from the API.
     *
     * @return array|null
     *   The fetched segments or null in case of failure.
     */
    public function fetchSegments(): ?array
    {
        $url = getenv('MAUTIC_BASE_URL') . '/api/segments';
        $username = getenv('MAUTIC_USERNAME');
        $password = getenv('MAUTIC_PASSWORD');

        try {
            // Log the URL from which segments are being fetched.
            $this->logger->debug('Fetching segments from URL: @url', ['@url' => $url]);

            // Make the API request.
            $response = $this->httpClient->request('GET', $url, [
                'auth' => [$username, $password],
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);

            // Decode the response body.
            $data = Json::decode($response->getBody()->getContents());

            // Log and store the fetched data.
            $this->logger->debug('Fetched segments data: @data', ['@data' => print_r($data, true)]);
            $this->store->set('segments', $data);

            return $data;
        } catch (\Exception $e) {
            // Log the error if the request fails.
            $this->logger->error('Failed to fetch Mautic segments: @message', ['@message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Retrieves cached Mautic segments.
     *
     * @return array
     *   The cached segments or an empty array if none found.
     */
    public function getCachedSegments(): array
    {
        // Retrieve cached segments.
        $segments = $this->store->get('segments', []);

        // Log the cached data.
        $this->logger->debug('Retrieved cached segments: @segments', ['@segments' => print_r($segments, true)]);

        return $segments;
    }

}
