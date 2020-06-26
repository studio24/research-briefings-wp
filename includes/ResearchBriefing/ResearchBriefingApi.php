<?php


namespace ResearchBriefing;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use ResearchBriefing\Repository\SearchRepository;
use ResearchBriefing\Services\Logger\ImportLogger;

class ResearchBriefingApi extends Base
{
    const LIBRARY_COMMONS = 'commons';
    const LIBRARY_LORDS = 'lords';
    const LIBRARY_POST = 'post';

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var string
     */
    protected $libraryString;

    /** @var string */
    protected $firstApiUrl;

    /**
     * Get research briefings for a site
     *
     * @param string $website Website code
     * @param int $limit Limit of records to retrieve, if null get all
     * @return array
     * @throws Exception
     */
    public function getSiteBriefings($website, int $limit = null): array
    {
        $this->libraryString = $this->getLibraryApiString($website);
        $searchRepository = new SearchRepository();
        $logger = new ImportLogger($website);

        // Retrieve the first page that has briefing content
        $params = $this->setQueryParams($this->libraryString);
        if ($limit !== null) {
            if ($limit > 500) {
                throw new Exception(sprintf('If you specify a limit this must be 500 or less, %s passed', $limit));
            }
            $params['_pageSize'] = $limit;
        }

        $this->response = $this->api->get('', ['query' => $params]);
        $this->firstApiUrl = $this->api->getLastRequestedUri();

        $briefings[] = $this->api->parseJsonResponse($this->response);

        // Get further pages if limit not set
        if ($limit !== null) {
            $briefingRecords = $briefings;
        } else {
            $briefingRecords = $this->retrieveNextPageBriefings($briefings, $this->libraryString);
        }

        $researchBriefings = [];

        foreach ($briefingRecords as $index => $briefings) {

            foreach ($briefings['result']['items'] as $item) {

                $researchBriefing = new Briefing();
                $researchBriefing->setMappedFields($item);

                // Get complete data for a research briefing
                $completeData = $this->getCompleteBriefingData($researchBriefing->getResourceId());

                $researchBriefing->setCompleteBriefingFields($completeData);

                // Add post to be unpublished, don't check via isValid
                if (!$researchBriefing->isPublished()) {
                    $researchBriefings[] = $researchBriefing;

                    // Remove from search
                    $searchRepository->delete('briefing_id', $researchBriefing->getBriefingId());

                    continue;
                }

                // Skip result if key content fields are empty
                if (!$researchBriefing->isValid()) {
                    $logger->info('Research briefing ' .$researchBriefing->getId().' is not valid (missing key content). Skipping briefing.');

                    // Remove from search
                    $searchRepository->delete('briefing_id', $researchBriefing->getBriefingId());
                    continue;
                }

                $researchBriefings[] = $researchBriefing;
            }

        }

        return $researchBriefings;
    }

    /**
     * @param $briefingId
     * @return Briefing
     * @throws Exception
     */
    public function getSingleBriefing($briefingId) {

        $response = $this->api->get('', ['query' => ['identifier' => $briefingId]]);
        $briefing = $this->api->parseJsonResponse($response);
        $this->firstApiUrl = $this->api->getLastRequestedUri();

        foreach ($briefing['result']['items'] as $item) {

            $researchBriefing = new Briefing();
            $researchBriefing->setMappedFields($item);

            // Get complete data for a research briefing
            $completeData = $this->getCompleteBriefingData($researchBriefing->getResourceId());

            $researchBriefing->setCompleteBriefingFields($completeData);
        }

        return $researchBriefing;
    }

    /**
     * Complete briefing data is not available from the main research briefings feed
     *
     * @param $resourceId
     * @return mixed
     * @throws \Exception
     */
    public function getCompleteBriefingData($resourceId) {

        $response = $this->api->get($resourceId);
        $briefingData = $this->api->parseJsonResponse($response);

        return $briefingData;

    }

    /**
     * @param $code
     * @return string
     * @throws Exception
     */
    public function getLibraryApiString ($code) //string
    {
        switch ($code) {
            case self::LIBRARY_COMMONS:
                return 'House of Commons Library';
            case self::LIBRARY_LORDS:
                return 'House of Lords Library';
            case self::LIBRARY_POST:
                return'POST';
            default:
                throw new Exception('No library was specified for the import');
        }
    }

    /**
     * @param $website
     * @return bool
     */
    public function isHouseOfCommons($website) {

        return ($website === self::LIBRARY_COMMONS);

    }

    /**
     * @param $website
     * @return bool
     */
    public function isHouseOfLords($website) {

        return ($website === self::LIBRARY_LORDS);

    }

    /**
     * @param $website
     * @return bool
     */
    public function isPost($website) {

        return ($website === self::LIBRARY_POST);

    }

    /**
     * @param $research_briefings
     * @param null $libraryString
     * @return array
     * @throws Exception
     */
    public function retrieveNextPageBriefings($research_briefings, $libraryString = null) {

        $page = 0;

        // Response data is paginated so we check if there is a next page
        $nextPage = $research_briefings[0]['result']['next'];

        // If there is a single page retrieve the first response data
        if (!$nextPage) {
            return $research_briefings[0];
        } else {
            // Iterate through the multiple pages, retrieve the next page data and save it in an array
            while (!empty($nextPage)) {

                $page = $page + 1;

                // Get the briefing results per each page
                $this->response = $this->api->get('', ['query' => $this->setQueryParams($libraryString, $page)]);

                $briefings = $this->api->parseJsonResponse($this->response);
                $research_briefings[] = $briefings;

                $nextPage = $briefings['result']['next'];
            }
        }
        return $research_briefings;
    }

    /**
     * @param $library
     * @param int $pageSize
     * @param int $page
     * @return mixed
     */
    public function setQueryParams($library = null, $page = 0, $pageSize = 500)
    {
        $queryParams = [
            '_pageSize' => $pageSize,
            '_page' => $page,
        ];

        $library ? $queryParams['publisher.prefLabel'] = $library : null ;

        return $queryParams;
    }

    public function getFirstApiUrl()
    {
        return $this->firstApiUrl;
    }
}
