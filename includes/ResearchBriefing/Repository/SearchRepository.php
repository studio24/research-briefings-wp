<?php


namespace ResearchBriefing\Repository;


use PDOException;
use ResearchBriefing\Exception\DbException;
use ResearchBriefing\Model\Search;
use ResearchBriefing\Services\Database\DatabaseConnection;

class SearchRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $databaseBindings = [
        'title'          => ':title',
        'content'        => ':content',
        'site'           => ':site',
        'briefing_id'    => ':briefing_id',
        'post_id'        => ':post_id',
        'thumbnail_link' => ':thumbnail_link',
        'categories'     => ':categories',
        'url'            => ':url',
        'date'           => ':date',
        'year'           => ':year',
        'types'           => ':types',
    ];


    /**
     * Database table name
     * @var string
     */
    protected $tableName = 'search';

    /**
     * @var DatabaseConnection
     */
    protected $connection;

    /**
     * @param null $data
     * @return Search
     */
    public function createModel($data = null)
    {
        return new Search($data);
    }

    /**
     * Bind PDO values
     *
     * @todo in this instance $search is an object of type ResearchBriefing\Briefing, use type hinting here
     *
     * @param $databaseBindings
     * @param $query
     * @param $search
     * @return mixed
     */
    protected function bindValues($databaseBindings, $query, $search)
    {
        if (isset($databaseBindings['title'])) {
            $title = $search->getTitle();
            $query->bindParam(':title', $title, \PDO::PARAM_STR);
        }

        if (isset($databaseBindings['content'])) {
            $content = $search->getSearchContent();
            $query->bindParam(':content', $content, \PDO::PARAM_STR);
        }

        if (isset($databaseBindings['site'])) {
            $site = $search->getSite();
            $query->bindParam(':site', $site, \PDO::PARAM_INT);
        }

        if (isset($databaseBindings['briefing_id'])) {
            if (method_exists($search, 'getBriefingId')){
                $briefingId = $search->getId();
            }

            $query->bindParam(':briefing_id', $briefingId, \PDO::PARAM_STR);
        }

        if (isset($databaseBindings['post_id'])) {
            $postId = $search->getId();

            $query->bindParam(':post_id', $postId, \PDO::PARAM_STR);
        }

        if (isset($databaseBindings['url'])) {
            $url = $search->getPermalinkSlug();
            $query->bindParam(':url', $url, \PDO::PARAM_STR);
        }

        if (isset($databaseBindings['date'])) {
            $date = $search->getDate();

            if ($date instanceof \DateTime)
            {
                $date = $date->format('Y-m-d');
            }

            $query->bindParam(':date', $date, \PDO::PARAM_STR);
        }

        if (isset($databaseBindings['year'])) {
            $year = $search->getDate()->format('Y');

            if ($year instanceof \DateTime)
            {
                $year = $year->format('Y');
            }

            $query->bindParam(':year', $year, \PDO::PARAM_STR);
        }

        if (isset($databaseBindings['thumbnail_link'])) {
            $thumbnail = $search->getThumbnail();
            $query->bindParam(':thumbnail_link', $thumbnail, \PDO::PARAM_STR);
        }


        if (isset($databaseBindings['categories'])) {
            $categories = $search->getTags();
            $query->bindParam(':categories', $categories, \PDO::PARAM_STR);
        }

        // Briefing types only apply to Research Briefing records
        if (isset($databaseBindings['types'])) {
            if (method_exists($search, 'getTypeTags')){
                $types = $search->getTypeTags();
            }

            $query->bindParam(':types', $types, \PDO::PARAM_STR);
        }

        return $query;
    }

    /**
     * @param $search
     * @return mixed
     * @throws DbException
     */
    public function create($search)
    {
        $columns = implode(", ", array_keys($this->databaseBindings));
        $fieldParams = implode(", ", array_values($this->databaseBindings));

        $sql = 'INSERT INTO ' . $this->tableName . ' (' . $columns . ') VALUES (' . $fieldParams . ')';
        $query = $this->connection->prepare($sql);

        $query = $this->bindValues($this->databaseBindings, $query, $search);

        $result = $query->execute();

        if (!$result) {
            $info = $query->errorInfo();
            throw new DbException(sprintf('Create search record failed. Error %s: %s', $info[0], $info[2]));
        }

        return $result;
    }


    /**
     * Delete a record from the search index
     *
     * @param string $searchField
     * @param string $searchValue
     * @return int Affected rows (1 if a record is deleted, 0 if no record deleted)
     * @throws DbException
     */
    public function delete($searchField, $searchValue): int
    {
        $sql = 'DELETE FROM ' . $this->tableName . ' WHERE ' . $searchField . ' = ' . $this->connection->quote($searchValue);
        return $this->connection->exec($sql);
    }

    /**
     * @param $searchField
     * @param $searchValue
     * @return mixed
     * @throws DbException
     */
    public function update($searchField, $searchValue, $search)
    {
        $originalDataBindings = $this->databaseBindings;

        // Remove the field which we're using for the update command
        if (isset($this->databaseBindings[$searchField])){
            unset($this->databaseBindings[$searchField]);
        }

        $sql = 'UPDATE ' . $this->tableName . ' SET ';

        $count = 0;

        foreach ($this->databaseBindings as $column => $field)
        {
            $sql .= '`' . $column . '` = ' . $field;
            if(count($this->databaseBindings) != ($count + 1)) {
                $sql .= ', ';
            } else {
                $sql .= ' ';
            }
            $count++;
        }

        $sql .= 'WHERE ' . $searchField . ' = :searchValue';
        $query = $this->connection->prepare($sql);
        $query->bindParam(':searchValue', $searchValue, \PDO::PARAM_STR);
        $query = $this->bindValues($this->databaseBindings, $query, $search);

        $result = $query->execute();

        if ($result === false) {
            $info = $query->errorInfo();
            throw new DbException(sprintf('Update search record failed. Error %s: %s', $info[0], $info[2]));
        }

        // Restore the array with the search field
        $this->databaseBindings = $originalDataBindings;

        return $result;

    }

    /**
     * @param $searchField
     * @param $searchValue
     * @param $column
     * @param $field
     * @return bool
     */
    public function updateWhere($searchField, $searchValue, $column, $field)
    {
        $sql = 'UPDATE ' . $this->tableName . ' SET ' .'`' . $column. '` = :field ' .' WHERE ' . $searchField  . ' = :searchValue';

        try {
            $query = $this->connection->prepare($sql);
            $query->bindParam(':field', $field, \PDO::PARAM_STR);
            $query->bindParam(':searchValue', $searchValue, \PDO::PARAM_STR);

           $result = $query->execute();

        } catch (PDOException $e) {
            trigger_error('Wrong SQL: ' . $sql . ' Error: ' . $e->getMessage(), E_USER_ERROR);
        }
        if (empty($result)) {

            return false;
        }

        return $result;

    }

    /**
     * Find all rows based on a query, with pagination
     *
     * @param $sql
     * @param string $keyword
     * @param null $site
     * @param null $year
     * @return mixed
     */
    public function findAllSearchResults($sql = null, $keyword = '', $site = null, $year = null)
    {
        try {$query = $this->connection->prepare($sql);

            if (isset($keyword)) {
                $query->bindParam(':keyword', $keyword, \PDO::PARAM_STR);
            }

            if (isset($site) && $site !== 'all'){
                $query->bindParam(':site', $site , \PDO::PARAM_INT);
            }

            if (isset($year) && $year !== 'all'){
                $query->bindParam(':year', $year , \PDO::PARAM_STR);
            }

            $query->execute();
            $results = $query->fetchAll(\PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            trigger_error('Wrong SQL: ' . $sql . ' Error: ' . $e->getMessage(), E_USER_ERROR);
        }

        if (empty($results)) {
            return false;
        }

        $modelCollection = $this->translateResultsToModels($results);

        return $modelCollection;
    }

    /**
     * Find all rows based on the keyword text search, site and year, with pagination
     *
     * This works on an exact match only to ensure relevant results are returned
     *
     * If site = all and year = all do not include these fields in the query
     * Orders by most recent first (defined by year), then by relevancy
     *
     * @param $keyword
     * @param $site
     * @param $year
     * @param $limit
     * @param $page
     * @return mixed
     */
    public function performSearch($keyword, $site, $year, $limit, $page)
    {
        $keyword = '"' . $keyword . '"';
        $sql = <<<EOD
SELECT search.*, MATCH (title, content) AGAINST (:keyword IN NATURAL LANGUAGE MODE) as score
FROM search 
WHERE MATCH (title, content) AGAINST (:keyword IN NATURAL LANGUAGE MODE)

EOD;

        if (isset($site) && $site !== 'all') {
            $sql .= ' AND site = :site';
        }

        if (isset($year) && $year !== 'all'){
            $sql .= ' AND year = :year';
        }

        $sql .= ' ORDER by score DESC';

        $sql = $this->addPaginationQuery($sql, $limit, $page);

        return $this->findAllSearchResults($sql, $keyword, $site, $year);
    }

    /**
     * Count all results of briefings based on the search keyword and site and year if they are not empty
     * If site = all and year = all do not include these fields in the query
     *
     * @param $keyword
     * @param $site
     * @param $year
     * @return mixed
     */
    public function countSearchResults($keyword, $site, $year)
    {
        $keyword = '"' . $keyword . '"';
        $sql = <<<EOD
SELECT COUNT(search.id) as count
FROM search 
WHERE MATCH (title, content) AGAINST (:keyword IN NATURAL LANGUAGE MODE)

EOD;

        if (isset($site) && $site !== 'all') {
            $sql .= ' AND site = :site';
        }

        if (isset($year) && $year !== 'all'){
            $sql .= ' AND year = :year';
        }

        $query = $this->connection->prepare($sql);
        $query->bindParam(':keyword', $keyword , \PDO::PARAM_STR);

        if (isset($site) && $site !== 'all'){
            $query->bindParam(':site', $site , \PDO::PARAM_INT);
        }

        if (isset($year) && $year !== 'all'){
            $query->bindParam(':year', $year , \PDO::PARAM_STR);
        }

        $query->execute();

        $results = $query->fetch(\PDO::FETCH_ASSOC);

        if (!isset($results['count'])) {
            return 0;
        }

        return (int) $results['count'];
    }

}
