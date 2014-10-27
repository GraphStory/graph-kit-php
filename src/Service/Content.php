<?php

namespace GraphStory\GraphKit\Service;

use Everyman\Neo4j\Cypher\Query;
use Everyman\Neo4j\Node;
use GraphStory\GraphKit\Neo4jClient;

class Content
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

    public static function delete($username, $uuid)
    {
        $queryString =  " MATCH (usr:User { username: {u} }) WITH usr MATCH (p:Content { uuid:{uuid} } )-[:NEXTPOST*0..]-(l)-[:LASTPOST]-(u) u=usr as owner ";
        $query = new Query(Neo4jClient::client(), $queryString, array('u' => $username, 'uuid' => $uuid));
        $result = $query->getResultSet();

        if (!empty($result)) {
            //delete
        }

        return self::returnMappedContent($result);
    }

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

    public static function getContentItemByUUID($username, $uuid)
    {
        $queryString =  "MATCH (usr:User { username: {u} }) WITH usr MATCH (p:Content { contentId: {uuid} } )-[:NEXTPOST*0..]-(l)-[:LASTPOST]-(u) return p, u.username as username, u=usr as owner";
        $query = new Query(Neo4jClient::client(), $queryString, array('u' => $username, 'uuid' => $uuid));
        $result = $query->getResultSet();

        return self::returnMappedContent($result);
    }

    public static function getContentItem($id)
    {
        $queryString =  " START p=node({nodeId}) ".
                        " MATCH p-[:NEXTPOST*0..]-(l)-[:LASTPOST]-(u) ".
                        " RETURN p, u.username  as username, false as owner ";
        $query = new Query(Neo4jClient::client(), $queryString, array('nodeId' => $id));
        $result = $query->getResultSet();

        return self::returnMappedContent($result);
    }

    public static function getContent($username, $s)
    {
        // we're doing LIMIT 4. At present we're only displaying 3. the extra
        // item is to ensure there's more to view, so the next skip will be 3,
        // then 6, then 12
        $queryString = "MATCH (u:User {username: {u} })-[:FOLLOWS*0..1]->f "
            . "WITH DISTINCT f, u "
            . "MATCH f-[:LASTPOST]-lp-[:NEXTPOST*0..]-p "
            . "RETURN p, f.username as username, f=u as owner "
            . "ORDER BY p.timestamp desc SKIP {s} LIMIT 4";

        $query = new Query(
            Neo4jClient::client(),
            $queryString,
            array(
                'u' => $username,
                's' => $s
            )
        );
        $result = $query->getResultSet();

        return self::returnMappedContent($result);
    }

    public static function returnMappedContent($results)
    {
        $mappedContentArray = array();

        foreach ($results as $row) {
            $mappedContentArray[] = self::fromMappedContentArray(
                $row['p'],
                $row['username'],
                $row['owner']
            );
        }

        return $mappedContentArray;
    }

    public static function fromMappedContentArray(Node $cnode, $username, $owner)
    {
        $content = new Content();
        $content->id = $cnode->getId();
        $content->title = $cnode->getProperty('title');
        $content->url = $cnode->getProperty('url');
        $content->tagstr = $cnode->getProperty('tagstr');
        $content->uuid = $cnode->getProperty('contentId');
        $content->timestamp = gmdate("F j, Y g:i a", $cnode->getProperty('timestamp'));
        $content->owner = $owner;
        $content->userNameForPost = $username;
        $content->node = $content;

        return $content;
    }

    public static function getByNodeId($id)
    {
        $node = Neo4jClient::client()->getNode($id);
        $content = new Content();
        $content->id = $node->getId();
        $content->title = $node->getProperty('title');
        $content->url = $node->getProperty('url');
        $content->url = $node->getProperty('tagstr');
        $content->uuid = $node->getProperty('uuid');
        $content->node = $node;

        return $content;
    }
}
