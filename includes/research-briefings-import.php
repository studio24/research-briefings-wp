<?php

namespace RBI;

/**
 * Research briefings importer
 *
 * Example commands:
 * wp research briefing import all
 *
 * @see https://make.wordpress.org/cli/handbook/commands-cookbook/
 *
 */

use Exception;
use ResearchBriefing\Briefing;
use ResearchBriefing\Exception\TermNotSetException;
use ResearchBriefing\Exception\WordpressException;
use ResearchBriefing\Model\Insight;
use ResearchBriefing\ResearchBriefingApi;
use ResearchBriefing\Repository\SearchRepository;
use ResearchBriefing\Services\Logger\ImportLogger;
use ResearchBriefing\Wordpress\Wordpress;
use \WP_CLI;
use WP_Query;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;

WP_CLI::add_command('research-briefing import', 'RBI\ResearchBriefings_Import');

/**
 * Class ResearchBriefings_Import
 * @package RBI
 */
class ResearchBriefings_Import
{

    /**
     * @var ResearchBriefingApi
     */
    protected $researchBriefingApi;

    /**
     * @var array
     */
    protected $importCount = ['researchBriefings' => 0];

    /**
     * @var array
     */
    protected $errorCount = ['researchBriefings' => 0];

    /**
     * @var Briefing
     */
    protected $briefing;

    /**
     * @var Wordpress
     */
    protected $wordpress;

    /**
     * @var ImportLogger
     */
    protected $logger;

    /**
     * @var SearchRepository
     */
    protected $searchRepository;

    /** @var LockFactory */
    protected $lockFactory;

    /**
     * ResearchBriefings_Import constructor.
     */
    public function __construct()
    {
        // Initialise lock
        $store = new FlockStore(sys_get_temp_dir());
        $this->lockFactory = new LockFactory($store);

        // Initialise resources
        $this->researchBriefingApi = new ResearchBriefingApi();
        $this->wordpress = new Wordpress();
        $this->searchRepository = new SearchRepository();
    }

    /**
     * Script command to import a single research briefing.
     * Usage: wp research-briefing import single SN02792
     * - Where SN02792 is the Research Briefing ID
     *
     *
     * @param $args
     */
    public function single($args)
    {
        $this->logger = new ImportLogger('single');

        // Start lock
        $lock = $this->lockFactory->createLock('research-briefings-import-single');
        if (!$lock->acquire()) {
            die('Lock file is currently in use by another process, quitting script');
        }

        if (!empty($args)) {
            $briefingId = $args[0];
        }

        if (!isset($briefingId) || empty($briefingId)) {
            $this->addError('No Research briefing ID was provided, please enter a Research briefing ID in the command.', false);
            exit;
        }

        $message = sprintf('START single import for briefing ID %s', $briefingId);
        WP_CLI::line($message);
        $this->logger->info($message);

        // Get the single research briefing data
        try {
            $researchBriefing = $this->researchBriefingApi->getSingleBriefing($briefingId);
            $this->addSuccess(sprintf('API query: %s', $this->researchBriefingApi->getFirstApiUrl()), true);

        } catch (Exception $e) {
            $this->addError(sprintf(
                'Error fetching Research briefing ID: %s from the API (%s). Error: %s',
                $briefingId,
                $this->researchBriefingApi->getApi()->getLastRequestedUri(),
                $e->getMessage()
            ), true);

            die('Process can not complete without Research briefings data');
        }

        // Make sure post is unpublished if it should not be published
        if (!$researchBriefing->isPublished()) {
            $post = $this->wordpress->getExistingBriefingById($researchBriefing->getId());
            if ($post instanceof \WP_Post) {
                $this->wordpress->unpublishBriefingPost($post);
            }
            $this->searchRepository->delete('briefing_id', $briefingId);
            $this->addSuccess('Unpublished research briefing', true);

            $this->addSuccess('END single import ', true);
            $lock->release();
            return;
        }

        if (!$researchBriefing->isValid()) {
            $this->addError('Research briefing is not valid (missing key content)', true);
            $result = $this->searchRepository->delete('briefing_id', $briefingId);
            if ($result > 0) {
                $this->addError('Research briefing removed from search index', true);
            }
            die('Cannot import invalid research briefing');
        }

        // Switch to the corresponding multisite blog we should import data to
        $this->wordpress->switchToSite($researchBriefing);

        try {
            $this->importSingleBriefing($researchBriefing);
        } catch (Exception $e) {
            $this->addError('Error importing briefing. Error: '. $e->getMessage(), false);
        }

        $this->addSuccess('END single import ', true);

        // Release lock
        $lock->release();
    }

    /**
     * Imports all Research briefings into Wordpress database
     *
     * Script command to import all briefings or briefings filtered by website
     * Usage for all briefings: wp research-briefing import all
     * Usage for briefings filtered by website: wp research-briefing import all commons
     * - Where commons is the short name to import the House of Commons Library data
     * - Similarly use lords for the House of Lords Library and post for POST
     *
     * @param $args
     * @return void
     */

    function all($args)
    {
        $limit = null;
        if (!empty($args)) {
            $website = filter_var($args[0], FILTER_SANITIZE_STRING);
            if (isset($args[1])) {
                $limit = (int) $args[1];
            }
        }

        if (!isset($website) || empty($website) ) {
            WP_CLI::line('No library was specified, quitting!');
            die();

        } else {
            // Start logger for library-specific log
            $this->logger = new ImportLogger($website);

            if ($limit !== null) {
                $message = sprintf('START import all %s most recent briefings for %s', $limit, $website);
            } else {
                $message = sprintf('START import all briefings for %s', $website);
            }
            WP_CLI::line($message);
            $this->logger->info($message);
        }

        // Start lock
        $lock = $this->lockFactory->createLock('research-briefings-import-all-' . $website);
        if (!$lock->acquire()) {
            die('Lock file is currently in use by another process, quitting script');
        }

        // Get all the research briefing data
        try {
            $researchBriefings = $this->researchBriefingApi->getSiteBriefings($website, $limit);
            $this->addSuccess(sprintf('API query: %s', $this->researchBriefingApi->getFirstApiUrl()), true);
            $this->addSuccess(count($researchBriefings) . ' Research Briefings successfully returned from API', true);
        } catch (Exception $e)
        {
            $this->addError(sprintf(
                'Error getting data from the Research briefings API (%s). Error: %s',
                $this->researchBriefingApi->getApi()->getLastRequestedUri(),
                $e->getMessage()
            ), true);
            die('Process can not complete without Research briefings data');
        }

        foreach($researchBriefings as $index => $briefing) {
                try {
                    // Switch to the corresponding multisite blog we should import data to
                    $this->wordpress->switchToSite($briefing);

                    $this->importSingleBriefing($briefing);

                } catch (Exception $e) {
                    $this->addError('Error importing briefing. Error: '. $e->getMessage(), true);
                    continue;
                }
        }

        $message = sprintf('Imported %s briefings, %s errors', $this->importCount['researchBriefings'], $this->errorCount['researchBriefings']);
        WP_CLI::line($message);
        $this->logger->info($message);

        // Importing insights
        try {
            // Get all the insights data
            $insights = $this->wordpress->getExistingInsights();
        } catch (Exception $e){
            $this->addError('Error getting Insights data from Wordpress. Error: '. $e->getMessage());
        }

        foreach ($insights as $insight) {
            $this->importSingleInsight($insight);
        }

        $message = sprintf('Imported %s insights into the Search index', count($insights));
        WP_CLI::line($message);
        $this->logger->info($message);

        $this->addSuccess('END import all ', true);

        // Release lock
        $lock->release();
    }

    /**
     * @param $briefing
     * @throws Exception
     */
    public function importSingleBriefing (Briefing $briefing)
    {
        try {
            $this->insertBriefingPostInWordpress($briefing);

        } catch (WordpressException $e) {
            $this->addError('Error saving briefing '. $briefing->getId() .' to WordPress. Error: '. $e->getMessage(), true);
        }

        try {
            // Save briefing data to the DB for the search functionality to DB (search)
            if ($briefing->isPublished()) {
                $this->searchRepository->createOrUpdate('briefing_id', $briefing->getId(), $briefing);
            } else {
                $this->searchRepository->delete('briefing_id', $briefing->getBriefingId());
            }

        } catch (\Exception $e) {
            $this->addError('Error saving briefing ' . $briefing->getId() . ' to Search index. Error: ' . $e->getMessage(), false);
        }

        $this->addSuccess(sprintf("Saved briefing %s to WordPress and Search index", $briefing->getId()), true, true);
    }


    /**
     * Determine if we need to create a new 'Research briefing' custom post in Wordpress, then (if we do) - create one.
     * Otherwise update the existing post
     *
     * @param $briefing
     * @return void
     * @throws WordpressException
     * @throws TermNotSetException
     */
    public function insertBriefingPostInWordpress(Briefing $briefing)
    {
        $existingBriefing = $this->wordpress->getExistingBriefingById($briefing->getId());

        if (empty($existingBriefing)) {
            $this->wordpress->createBriefingPost($briefing);
        } else {
            $this->wordpress->updateBriefingPost($existingBriefing, $briefing);

            if (!$briefing->isPublished()) {
                $this->addSuccess('Research briefing ' . $briefing->getId() . ' was set as draft', true);
            }

        }
    }

    /**
     * @param $message - the error message to report
     * @param bool $count
     */
    protected function addError($message, $count = false)
    {
        WP_CLI::error($message, false);
        $this->logger->error($message);

        if ($count)
        {
            $this->errorCount['researchBriefings']++;
        }

    }

    /**
     * @param $message - the success message to report
     * @param bool $count
     * @param bool $log
     */
    protected function addSuccess($message, $log = false, $count = false)
    {
        WP_CLI::success($message);

        if ($log) {
            $this->logger->info($message);
        }

        if ($count) {
            $this->importCount['researchBriefings']++;
        }

    }

    public function importSingleInsight(Insight $insight)
    {
        try {
            // Save insight data to the DB for the search functionality
            $this->searchRepository->createOrUpdate('post_id', $insight->getId(), $insight);
        } catch (\Exception $e) {
            $this->addError('Error saving insight search content. Insight post id ' . $insight->getId() . ' not saved. Error: ' . $e->getMessage(), false);
        }

    }


}
