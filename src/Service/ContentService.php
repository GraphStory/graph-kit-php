<?php

namespace GraphStory\GraphKit\Service;

use GraphStory\GraphKit\Model\Content;
use GraphStory\GraphKit\Neo4jClient;

class ContentService
{
    /**
     * Adds content
     *
     * @param  string    $username User adding content
     * @param  Content   $content  Content to add
     * @return Content[]
     */
    public static function add($username, Content $content)
    {
        $queryString = <<<CYPHER
MATCH (user { username: {u}})
OPTIONAL MATCH (user)-[r:CURRENTPOST]->(currentpost)
DELETE r
CREATE (user)-[:CURRENTPOST]->(p:Content { title:{title}, url:{url}, tagstr:{tagstr}, timestamp:{timestamp}, contentId:{contentId} })
WITH p, collect(currentpost) as currentposts
FOREACH (x IN currentposts | CREATE p-[:NEXTPOST]->x)
RETURN p, {u} as username, true as owner
CYPHER;

        $query = new Query(
            Neo4jClient::client(),
            $queryString,
            array(
                'u' => $username,
                'title' => $content->title,
                'url' => $content->url,
                'tagstr' => $content->tagstr,
                'timestamp' => time(),
                'contentId' => uniqid(),
            )
        );
        $result = $query->getResultSet();

        return self::returnMappedContent($result);
    }

    /**
     * Edit content
     *
     * @param  Content $content Content to edit
     * @return Content Edited content
     */
    public static function edit(Content $content)
    {
        $updatedAt = time();

        $node = $content->node;
        $node->setProperty('title', $content->title);
        $node->setProperty('url', $content->url);
        $node->setProperty('tagstr', $content->tagstr);
        $node->setProperty('updated', $updatedAt);
        $node->save();

        $content->updated = $updatedAt;

        return $content;
    }

    /**
     * Delete content and create relationships between remaining content as appropriate
     *
     * @param string $username  Username of content owner
     * @param string $contentId Content id
     */
    public static function delete($username, $contentId)
    {
        $queryString = self::getDeleteQueryString($username, $contentId);

        $params = array(
            'username' => $username,
            'contentId' => $contentId,
        );

        $query = new Query(Neo4jClient::client(), $queryString, $params);
        $query->getResultSet();
    }

    /**
     * Returns true if Content is the most recent, or current, Content item
     *
     * @param  string  $username  Username of content owner
     * @param  string  $contentId Content id
     * @return boolean True if Content is most recent, false otherwise
     */
    public static function isCurrentPost($username, $contentId)
    {
        $queryString = <<<CYPHER
MATCH (u:User { username: { username }})-[:CURRENTPOST]->(c:Content { contentId: { contentId }}) RETURN c
CYPHER;

        $query = new Query(
            Neo4jClient::client(),
            $queryString,
            array(
                'username' => $username,
                'contentId' => $contentId,
            )
        );

        $result = $query->getResultSet();

        return count($result) !== 0;
    }

    /**
     * Returns true if Content is the final, and oldest, Content item in the list
     *
     * @param  string  $username  Username of content owner
     * @param  string  $contentId Content id
     * @return boolean True if Content is last, false otherwise
     */
    public static function isLeafPost($username, $contentId)
    {
        $queryString = <<<CYPHER
MATCH (u:User { username: { username }})-[:CURRENTPOST|NEXTPOST*0..]->(c:Content { contentId: { contentId }})
WHERE NOT (c)-[:NEXTPOST]->()
RETURN c
CYPHER;

        $query = new Query(
            Neo4jClient::client(),
            $queryString,
            array(
                'username' => $username,
                'contentId' => $contentId,
            )
        );

        $result = $query->getResultSet();

        return count($result) !== 0;
    }

    /**
     * Gets the appropriate DELETE query based on where in the list the Content appears
     *
     * @param  string $username  Username of content owner
     * @param  string $contentId Content id
     * @return string Cypher query to delete Content
     */
    protected static function getDeleteQueryString($username, $contentId)
    {
        if (self::isLeafPost($username, $contentId)) {
            return <<<CYPHER
MATCH (u:User { username: { username }})-[:CURRENTPOST|NEXTPOST*0..]->(c:Content { contentId: { contentId }})
WITH c
MATCH (c)-[r]-()
DELETE c, r
CYPHER;
        }

        if (self::isCurrentPost($username, $contentId)) {
            return <<<CYPHER
MATCH (u:User { username: { username }})-[lp:CURRENTPOST]->(del:Content { contentId: { contentId }})-[np:NEXTPOST]->(nextPost)
CREATE UNIQUE (u)-[:CURRENTPOST]->(nextPost)
DELETE lp, del, np
CYPHER;
        }

        return <<<CYPHER
MATCH (u:User { username: { username }})-[:CURRENTPOST|NEXTPOST*0..]->(before),
    (before)-[delBefore]->(del:Content { contentId: { contentId }})-[delAfter]->(after)
CREATE UNIQUE (before)-[:NEXTPOST]->(after)
DELETE del, delBefore, delAfter
CYPHER;
    }

    /**
     * Gets content by contentId
     *
     * @param  string    $username  Username
     * @param  string    $contentId Content id
     * @return Content[]
     */
    public static function getContentById($username, $contentId)
    {
        $queryString = <<<CYPHER
MATCH (usr:User { username: { u }})
WITH usr
MATCH (p:Content { contentId: { contentId }})-[:NEXTPOST*0..]-(l)-[:CURRENTPOST]-(u)
RETURN p, u.username AS username, u = usr AS owner
CYPHER;

        $query = new Query(
            Neo4jClient::client(),
            $queryString,
            array(
                'u' => $username,
                'contentId' => $contentId,
            )
        );

        $result = $query->getResultSet();

        return self::returnMappedContent($result);
    }

    /**
     * Gets content from user's friends
     *
     * We're doing LIMIT 4. At present we're only displaying 3. the extra
     * item is to ensure there's more to view, so the next skip will be 3,
     * then 6, then 12
     *
     * @param  string    $username
     * @param  int       $skip     Records to skip
     * @return Content[]
     */
    public static function getContent($username, $skip)
    {
        $queryString = <<<CYPHER
MATCH (u:User { username: { u }})-[:FOLLOWS*0..1]->f
WITH DISTINCT f, u
MATCH f-[:CURRENTPOST]-lp-[:NEXTPOST*0..]-p
RETURN p, f.username as username, f=u as owner
ORDER BY p.timestamp desc SKIP {skip} LIMIT 4
CYPHER;
        $p = array(
            'u' => (string) $username,
            'skip' => (int) $skip
        );

        $result = Neo4jClient::client()->sendCypherQuery($queryString, $p)->getResult();

        return self::returnMappedContent($result);
    }

    /**
     * Get content by node id
     *
     * @param  int     $id Node id
     * @return Content
     */
    public static function getByNodeId($id)
    {
        $node = Neo4jClient::client()->getNode($id);

        return self::createFromNode($node);
    }

    /**
     * Creates array of Content from ResultSet
     *
     * @param  ResultSet $results
     * @return Content[]
     */
    protected static function returnMappedContent(ResultSet $results)
    {
        $mappedContentArray = array();

        foreach ($results as $row) {
            $mappedContentArray[] = self::createFromNode(
                $row['p'],
                $row['username'],
                $row['owner']
            );
        }

        return $mappedContentArray;
    }

    /**
     * Creates Content instance from a content node
     *
     * @param  Node    $node     Content node
     * @param  string  $username Username for post
     * @param  string  $owner    Content owner
     * @return Content
     */
    protected static function createFromNode(Node $node, $username = null, $owner = false)
    {
        $content = new Content();
        $content->node = $node;
        $content->nodeId = $node->getId();
        $content->contentId = $node->getProperty('contentId');
        $content->title = $node->getProperty('title');
        $content->url = $node->getProperty('url');
        $content->tagstr = $node->getProperty('tagstr');
        $content->timestamp = gmdate("F j, Y g:i a", $node->getProperty('timestamp'));
        $content->owner = $owner;
        $content->userNameForPost = $username;

        return $content;
    }
}
