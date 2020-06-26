<?php

namespace ResearchBriefing;


use Exception;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Base
 * @package App
 */
abstract class Base
{
    /**
     * @var Api
     */
    protected $api;

    /**
     * API base URI
     *
     * @var string
     */
    protected $baseUri;


    /**
     * @var
     */
    protected $errors = [];


    /**
     * Base constructor.
     * @throws Exception
     */
    public function __construct()
    {
        $baseUri = 'https://lda.data.parliament.uk/researchbriefings/';

        if (!$baseUri)
        {
            throw new Exception('API BASE URL Not set');
        }


        $this->baseUri = $baseUri;

        $this->initialiseApi();
    }

    /**
     *Create a new instance of the API class
     *
     */
    protected function initialiseApi()
    {
        $this->api = new Api($this->baseUri);
    }

    /**
     * @return Api
     */
    public function getApi ()
    {
        return $this->api;
    }

    /**
     * Check if the api request is successful
     *
     * @param ResponseInterface $response
     * @return bool
     */
    public function isSuccessful(ResponseInterface $response) {

        if ($response->getStatusCode() !== 200) {
            return false;

        }
        return true;

    }

    /**
     * @return bool
     */
    public function hasErrors()
    {
        if(!empty($this->errors) && isset($this->errors))
        {
            return true;
        }
        return false;
    }
    /**
     * @param $key
     * @param $errorMessage
     */
    protected function addError($key, $errorMessage)
    {
        $this->errors[$key] = $errorMessage;
    }

    /**
     * Get the errors from the validation
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

}
