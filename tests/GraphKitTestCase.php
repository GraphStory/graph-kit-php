<?php

namespace GraphStory\GraphKit\Test;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;
use GraphStory\GraphKit\Neo4jClient;

class GraphKitTestCase extends \PHPUnit_Framework_TestCase
{
    const SLIM_MODE = 'test';

    protected function buildRealClient()
    {
        $neo4jClient = new Client('localhost', 7474);
        $appClient = Neo4jClient::setClient($neo4jClient);

        return $appClient;
    }

    protected function sendQuery($q)
    {
        $queryString = $q;
        $query = new Query(Neo4jClient::client(), $queryString);
        $result = $query->getResultSet();

        return $result;
    }

    protected function clearDB()
    {
        return $this->sendQuery('MATCH (n) OPTIONAL MATCH (n)-[r]-() DELETE r,n');
    }

    protected function loadGraph()
    {
        $cqs = file_get_contents(__DIR__.'/GKP_IMPORT.cqs');
        $statements = explode(';', $cqs);
        $end = count($statements);
        unset($statements[$end-1]);
        foreach ($statements as $statement) {
            $this->sendQuery(trim($statement));
        }
    }
}