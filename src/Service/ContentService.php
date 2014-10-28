<?php

namespace GraphStory\GraphKit\Service;

use Everyman\Neo4j\Cypher\Query;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\ResultSet;
use GraphStory\GraphKit\Model\Content;
use GraphStory\GraphKit\Neo4jClient;

class ContentService
{
    protected $node = null;
    public $id = null;
    public $title = '';
    public $url = '';
    public $tagstr = '';
    public $timestamp = '';
    public $uuid = '';
    public $userNameForPost = '';
    public $owner = false;

    /**
     * Adds content
     *
     * @param  string    $username User adding content
     * @param  Content   $content  Content to add
     * @return Content[]
     */
    public static function add($username, Content $content)
    {
        $queryString = "MATCH (user { username:{u}}) ".
            " OPTIONAL MATCH (user)-[r:LASTPOST]->(lastpost) ".
            " DELETE r ".
            " CREATE (user)-[:LASTPOST]->(p:Content { title:{title}, url:{url}, tagstr:{tagstr}, timestamp:{timestamp}, uuid:{uuid} }) ".
            " WITH p, collect(lastpost) as lastposts ".
            " FOREACH (x IN lastposts |  CREATE p-[:NEXTPOST]->x ) ".
            " RETURN p, {u}  as username, true as owner ";

        $query = new Query(
            Neo4jClient::client(),
            $queryString,
            array(
                'u' => $username,
                'title' => $content->title,
                'url' => $content->url,
                'tagstr' => $content->tagstr,
                'timestamp' => time(),
                'uuid' => uniqid()
            )
        );
        $result = $query->getResultSet();

        return self::returnMappedContent($result);
    }

    /**
     * WIP: Delete content
     *
     * @param string $username
     * @param string $uuid
     */
    public static function delete($username, $uuid)
    {
        $queryString = "MATCH (usr:User { username: { u }}) "
            . "WITH usr "
            . "MATCH (p:Content { uuid: { uuid }})-[:NEXTPOST*0..]-(l)-[:LASTPOST]-(u) "
            . "u = usr as owner";

        $query = new Query(
            Neo4jClient::client(),
            $queryString,
            array(
                'u' => $username,
                'uuid' => $uuid
            )
        );

        $result = $query->getResultSet();

        if (!empty($result)) {
            //delete
        }
    }

    /**
     * WIP: Edit content
     *
     * @param  Content   $content Content to edit
     * @return Content[]
     */
    public static function edit(Content $content)
    {
        /*
			// get a client
			$client = Neo4jClient::client();

			// if no node set
			if (!$content->node) {
	    		$content->node = new Node(Neo4jClient::client());
	    	}

			//get label
			$contentLabel = $client->makeLabel('Content');

	        // set properties
	        $content->node->setProperty('title', $content->title);
			$content->node->setProperty('url', $content->url);

			// if tags arent empty
			if ($content->tagstr !== '') {
				$content->node->setProperty('tagstr', $content->tagstr);
			}

			// set time
			$content->timestamp=time();
			$content->node->setProperty('timestamp', $content->timestamp);

			// set uuid
			$content->uuid=uniqid();
			$content->node->setProperty('uuid', $content->uuid);

	        // save the node with the label
	        $content->node->save()->addLabels(array($contentLabel));


	        //set the id on the content object
	        $content->id = $content->node->getId();

			return $content;
		 */

        $queryString =  " START p=node({nodeId}) ".
                        " MATCH p-[:NEXTPOST*0..]-(l)-[:LASTPOST]-(u) ".
                        " RETURN p, u.username  as username ";
        $query = new Query(Neo4jClient::client(), $queryString, array('nodeId' => $id));
        $result = $query->getResultSet();

        return self::returnMappedContent($result);
    }

    /**
     * Gets content by UUID
     *
     * @param  string    $username Username
     * @param  string    $uuid     Content UUID
     * @return Content[]
     */
    public static function getContentItemByUUID($username, $uuid)
    {
        $queryString = <<<CYPHER
MATCH (usr:User { username: { u }})
WITH usr
MATCH (p:Content { contentId: { uuid }})-[:NEXTPOST*0..]-(l)-[:LASTPOST]-(u)
RETURN p, u.username AS username, u = usr AS owner
CYPHER;

        $query = new Query(
            Neo4jClient::client(),
            $queryString,
            array(
                'u' => $username,
                'uuid' => $uuid,
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
MATCH f-[:LASTPOST]-lp-[:NEXTPOST*0..]-p
RETURN p, f.username as username, f=u as owner
ORDER BY p.timestamp desc SKIP {skip} LIMIT 4
CYPHER;

        $query = new Query(
            Neo4jClient::client(),
            $queryString,
            array(
                'u' => $username,
                'skip' => $skip,
            )
        );
        $result = $query->getResultSet();

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
    protected static function createFromNode(Node $node, $username = null, $owner = null)
    {
        $content = new Content();
        $content->id = $node->getId();
        $content->title = $node->getProperty('title');
        $content->url = $node->getProperty('url');
        $content->tagstr = $node->getProperty('tagstr');
        $content->uuid = $node->getProperty('contentId');
        $content->timestamp = gmdate("F j, Y g:i a", $node->getProperty('timestamp'));
        $content->owner = $owner;
        $content->userNameForPost = $username;
        $content->node = $content;

        return $content;
    }
}
