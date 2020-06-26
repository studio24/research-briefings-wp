<?php

namespace ResearchBriefing;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use http\QueryString;
use Psr\Http\Message\ResponseInterface;

class Api
{

    const API_FEED = 'http://lda.data.parliament.uk/researchbriefings.json';

    const USER_AGENT = 'ResearchBriefingsWebsiteBot/2.0 (+https://researchbriefings.parliament.uk/)';

    /**
     * Guzzle client
     *
     * @var Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $baseUri;

    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var
     */
    protected $response;

    /** @var string */
    protected $lastRequestedUri;

    /** @var int */
    protected $lastRequestedStatus;

    /**
     * Api constructor.
     * @param string $baseUri
     */
    public function __construct($baseUri)
    {
        $this->client = new Client([
            'base_uri' => $baseUri,
            'headers' => [
                'User-Agent' => self::USER_AGENT,
                'Accept' => 'application/json',
                'Content-type' => 'application/json'
            ]
        ]);

        $this->baseUri = $baseUri;
    }

    /**
     * Make a GET request to the API
     *
     * @param string $uri URI relative to base URI
     * @param array $options
     * @return mixed|ResponseInterface
     * @throws Exception
     */
    public function get($uri, array $options = [])
    {
        return $this->request('GET', $this->getUri($uri), $options);
    }

    /**
     *
     * @see http://docs.guzzlephp.org/en/stable/request-options.html
     * @param $uri
     * @param array $options Request options
     * @return mixed|ResponseInterface
     * @throws Exception
     * @throws GuzzleException
     */
    public function post($uri, array $options = [])
    {
        return $this->request('POST', $this->getUri($uri), $options);
    }

    /**
     * @param $method
     * @param $uri
     * @param array $options
     * @return mixed|ResponseInterface
     * @throws Exception
     */
    public function request($method, $uri, array $options)
    {
        $this->lastRequestedUri = $uri;
        if (isset($options['query'])) {
            $this->lastRequestedUri .= '?' . http_build_query($options['query']);
        }

        $response = $this->client->request($method, $uri, $options);
        $this->lastRequestedStatus = (int) $response->getStatusCode();

        if ($response->getStatusCode() !== 200) {
            $message = sprintf(
                'Expected response code error. Status: %s, Error: %s',
                $response->getStatusCode(),
                $response->getReasonPhrase());
            throw new Exception($message);
        }

        $this->response = $response;

        return $this->response;
    }

    /**
     * @return mixed
     */
    public function getContents()
    {
        return json_decode($this->response->getBody()->getContents());
    }

    /**
     * Parse return JSON data and return in decoded format
     *
     * @param ResponseInterface $response
     * @return array Array of response data
     * @throws Exception
     */
    public function parseJsonResponse(ResponseInterface $response)
    {
        $data = json_decode($response->getBody()->getContents(), true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $data;
        }

        throw new Exception('Error parsing JSON response body: ' . json_last_error_msg());
    }

    /**
     * @param $uri
     * @return string
     */
    public function getUri($uri = null)
    {
        if(!$uri) {
            return self::API_FEED ;
        }

       return $this->baseUri . $uri .'.json';
    }

    public function getLastRequestedUri(): ?string
    {
        return $this->lastRequestedUri;
    }

    public function getLastRequestedStatus(): ?int
    {
        return $this->lastRequestedStatus;
    }

}
