<?php

namespace GraphStory\GraphKit\Repository;

use GraphAware\Neo4j\OGM\Query\QueryResultMapping;
use GraphAware\Neo4j\OGM\Repository\BaseRepository;
use GraphStory\GraphKit\Model\ContentItem;

class ContentRepository extends BaseRepository
{
    public function getContent($username, $skip = 0, $limit = 3)
    {
        $query = 'MATCH (u:User { username: { u }})-[:FOLLOWS*0..1]->f
        WITH DISTINCT f, u
        MATCH f-[:CURRENTPOST]-lp-[:NEXTPOST*0..]-p
        RETURN p as post, f as author, f=u as isOwner
        ORDER BY p.timestamp desc SKIP {skip} LIMIT {limit}';

        $qrm = new QueryResultMapping(ContentItem::class, QueryResultMapping::RESULT_MULTIPLE);

        return $this->nativeQuery($query, ['u' => $username, 'skip' => $skip, 'limit' => $limit], $qrm);
    }
}