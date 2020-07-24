<?php

namespace RBI;

/**
 * Author tags importer
 *
 * Example commands:
 * wp author-tags import all
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
use S24CollectionsPortal\Helpers\DataCleanup;
use \WP_CLI;
use WP_Query;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;

WP_CLI::add_command('author-tags import', 'RBI\Author_Tags_Import');

/**
 * Class Author_Tags_Import
 * @package RBI
 */
class Author_Tags_Import
{

    /**
     * The location of the CSV files required to load
     *
     * @var array
     */
    const CSV_FILE_LOCATION = __DIR__.'/Storage/author-tag-ids/';

    /**
     * Holds Raw CSV file data as an array
     *
     * @var
     */
    protected $rawData = [];

    /**
     * @var array
     */
    protected $importCount = ['authors' => 0];

    /**
     * @var array
     */
    protected $errorCount = ['authors' => 0];

    /**
     * @var ImportLogger
     */
    protected $logger;


    /**
     * Author_Tags_Import constructor.
     */
    public function __construct()
    {
        // Initialise resources
    }

    /**
     * Imports all post_tag authors into the rb_authors taxonomy
     *
     * Script command to import all or author tags filtered by website
     * Usage for author tags filtered by website: wp author-tags import all commons
     * - Where commons is the short name to import the House of Commons Library data
     * - Similarly use lords for the House of Lords Library and post for POST
     *
     * @param $args
     * @return void
     */

    function all($args)
    {
        if (!empty($args)) {
            $website = filter_var($args[0], FILTER_SANITIZE_STRING);
        }

        if (!isset($website) || empty($website) ) {
            WP_CLI::line('No library was specified, quitting!');
            die();

        } else {
            // Start logger for authors import
            $this->logger = new ImportLogger('authors');

            $message = sprintf('START import all author tags for %s', $website);
            WP_CLI::line($message);
            $this->logger->info($message);
        }

        // Check file is in the location we expect.
        if (!file_exists(self::CSV_FILE_LOCATION. $website .'.csv')) {
            $this->addError('Process cannot complete without the CSV file. Exiting command');
            die();
        }

        // Get the authors tag IDs
        try {
            $authorTagIds = $this->loadCsvFileIntoMemory(self::CSV_FILE_LOCATION. $website .'.csv');

            $this->addSuccess(count($authorTagIds) . ' Author IDs successfully returned from the CSV file', true);
        } catch (Exception $e)
        {
            $this->addError(sprintf(
                'Error getting data from the CSV file (%s). Error: %s',
                self::CSV_FILE_LOCATION. $website .'.csv',
                $e->getMessage()
            ), true);
            die('Process can not complete without Author Tag IDs');
        }

        // Switch to the corresponding multisite blog we should import data to
        $this->switchToSite($website);

        foreach($authorTagIds as $index => $authorId) {
                try {
                    $this->importAuthorInWordpress($authorId);

                } catch (Exception $e) {
                    $this->addError('Error importing author tag ID. Error: '. $e->getMessage(), true);
                    continue;
                }
        }

        $message = sprintf('Imported %s author tag IDs, %s errors', $this->importCount['authors'], $this->errorCount['authors']);
        WP_CLI::line($message);
        $this->logger->info($message);

        $this->addSuccess('END import all ', true);

    }

    /**
     * Imports the post_tag author terms ID as a rb_authors taxonomy
     *
     * @param $authorTermId
     * @return void
     * @throws Exception
     */
    protected function importAuthorInWordpress($authorTermId)
    {
        global $wpdb;

        $update = $wpdb->update(
            $wpdb->prefix . 'term_taxonomy',
                    [ 'taxonomy' => 'rb_authors' ],
                    [ 'term_taxonomy_id' => $authorTermId ],
                    [ '%s' ],
                    [ '%d' ]
            );

        if (!$update) {
            throw new \Exception('Could not update taxonomy of author term ID ' .$authorTermId. ' in Wordpress');
        } else {
            $this->addSuccess(sprintf('Updated the taxonomy of the author term ID %s in WordPress', $authorTermId), true, true);
        }

    }


    /**
     * Loads the CSV file into memory and returns the raw data as an array
     *
     * @param $fileName
     * @return mixed
     */
    protected function loadCsvFileIntoMemory($fileName)
    {
        ini_set('auto_detect_line_endings', true);
        $csv = [];

        $file = fopen($fileName, "r");

        if ($file){
            while (!feof($file)) {
                $csv[] = (fgetcsv($file));
            }
        }

        fclose($file);

        $rawData = [];

        foreach ($csv as $index => $row)
        {
            // Let's check we don't have an empty row
            if (!empty($row)) {
                $rawData[$index] = $this->cleanAlphaNumericString($row[0]);
            }
        }

        return  $rawData;
    }

    /**
     * Removes special characters from the string
     * @param $string
     * @return null|string|string[]
     */
    protected function cleanAlphaNumericString($string) {

        return preg_replace('/[^A-Za-z0-9\-]/', '', $string);
    }


    /**
     * Method used for the multisite setup to determine what site tables to use for the import
     * Prefix wp_* is used for House of Commons data; wp_2_* is for House of Lords and wp_3_* is for POST
     * @param $website
     */
    protected function switchToSite($website) {

        switch ($website) {
            case 'commons':
                switch_to_blog(1);
                break;
            case 'lords':
                switch_to_blog(2);
                break;
            case 'post':
                switch_to_blog(3);
                break;
            default:
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
            $this->errorCount['authors']++;
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
            $this->importCount['authors']++;
        }

    }
}
