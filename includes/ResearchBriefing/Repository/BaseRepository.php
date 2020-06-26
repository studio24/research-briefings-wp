<?php


namespace ResearchBriefing\Repository;


use ResearchBriefing\Exception\DbException;
use ResearchBriefing\Services\Database\DatabaseConnection;

abstract class BaseRepository implements RepositoryInterface
{
    /**
     * @var array
     */
    protected $databaseBindings = [];

    /**
     * Database table name
     * @var string
     */
    protected $tableName = '';

    /**
     * @var DatabaseConnection
     */
    protected $connection;

    /**
     * Search constructor.
     */
    public function __construct()
    {
        $this->connection = ( new DatabaseConnection() )->connection;

    }

    /**
     * Create the current data object in the database or update it if it already exists
     *
     * @param $searchField
     * @param $searchValue
     * @param $object
     * @return mixed
     * @throws DbException
     */
    public function createOrUpdate($searchField, $searchValue, $object)
    {
        if ($this->idExists($searchValue, $searchField))
        {
            return $this->update($searchField, $searchValue, $object);
        }

        return $this->create($object);

    }

    /**
     * Check if a briefing id already exists in the db
     *
     * @param $id
     * @param string $fieldName
     * @return bool
     */
    public function idExists($id, $fieldName = 'briefing_id' )
    {
        $sql = 'SELECT * from ' . $this->tableName . ' WHERE ' . $fieldName .' = :'.$fieldName;
        $query = $this->connection->prepare($sql);

        $query->bindParam(':'.$fieldName, $id, \PDO::PARAM_STR);
        $query->execute();

        $results = $query->fetchAll();

        if (empty($results)){
            return false;
        }

        return true;
    }

    /**
     * Translates DB row results to an array of appropriate models
     *
     * @param array $results
     * @return array
     */
    protected function translateResultsToModels(array $results)
    {
        $modelCollection = [];

        foreach ($results as $result)
        {
            $modelCollection[] = $this->translateSingleResultToModel($result);
        }

        return $modelCollection;
    }

    /**
     * Translates a single result to a model
     *
     * @param array $result
     * @return
     */
    protected function translateSingleResultToModel(array $result)
    {
        return $this->createModel($result);
    }

    /**
     * Adds the required SQL to make pagination work
     *
     * @param $sql
     * @param $limit
     * @param $page
     * @return string
     */
    protected function addPaginationQuery($sql, $limit, $page)
    {
        if ($page >= 2)
        {
            $page = $page-1;
        } else {
            $page = 0;
        }

        $sql .= ' LIMIT ' . $limit . ' OFFSET ' . $page * $limit;

        return $sql;
    }

}
