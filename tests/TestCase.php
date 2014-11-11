<?php

namespace GraphStory\GraphKit\Test;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;
use GraphStory\GraphKit\Neo4jClient;

class TestCase extends \PHPUnit_Framework_TestCase
{

    protected function setUp()
    {
        $neo4jClient = new Client('localhost', 7474);
        Neo4jClient::setClient($neo4jClient);
    }

    protected function sendQuery($q)
    {
        $query = new Query(Neo4jClient::client(), $q);
        $result = $query->getResultSet();

        return $result;
    }

    protected function loadDB()
    {
        $queries = $this->getDBImportQueries();
        foreach ($queries as $query) {
            $this->sendQuery(trim($query));
        }
    }

    protected function clearDB()
    {
        return $this->sendQuery('MATCH (n) OPTIONAL MATCH (n)-[r]-() DELETE r,n');
    }

    protected function tearDown()
    {
        // should add code to remove the database
    }

    private function getDBImportQueries()
    {
        if ($path = realpath(__DIR__ . '/GKP_IMPORT.cqs')) {
            $contents = file_get_contents($path);
            $queries = explode(';', $contents);
            array_pop($queries);
            return $queries;
        }
        throw new \Exception('Could not load the import queries.');
    }
}
