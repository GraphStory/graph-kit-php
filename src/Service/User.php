<?php

namespace GraphStory\GraphKit\Service;

use Everyman\Neo4j\Cypher\Query;
use Everyman\Neo4j\Node;
use GraphStory\GraphKit\Neo4jClient;

class User
{
    protected $node = null;
    public $id = null;
    public $username = '';
    public $firstname = '';
    public $lastname = '';

    public static function getByUsername($username)
    {
        $userlabel = Neo4jClient::client()->makeLabel('User');
        $nodes= $userlabel->getNodes('username', $username);

        if (empty($nodes) || count($nodes)==0) {
            return null;
        } else {
            return self::fromArray($nodes[0]);
        }
    }

    public static function friendSuggestions($username)
    {
        $queryString = <<<CYPHER
MATCH (u:User), (user:User { username: {username} })
WHERE u <> user
AND (NOT (user)-[:FOLLOWS]->(u))
RETURN u
LIMIT 5
CYPHER;

        $query = new Query(
            Neo4jClient::client(),
            $queryString,
            array('username' => $username)
        );

        $result = $query->getResultSet();

        return self::returnAsUsers($result);
    }

    public static function getNodeByUsername($username)
    {
        $userlabel = Neo4jClient::client()->makeLabel('User');
        $nodes = $userlabel->getNodes('username', $username);

        if (empty($nodes) || count($nodes) == 0) {
            return null;
        } else {
            return $nodes[0];
        }
    }

    public static function searchByUsername($username, $currentusername)
    {
        //wild card search on $username - which is just a string passed in from the request, e.g. the letter 'a'
        $username=$username.'.*';
        $queryString = "MATCH (n:User), (user { username:{c}}) WHERE (n.username =~ {u} AND n <> user) AND (NOT (user)-[:FOLLOWS]->(n)) RETURN n";
        $query = new Query(Neo4jClient::client(), $queryString, array('u' => $username,'c' => $currentusername));
        $result = $query->getResultSet();

        return self::returnAsUsers($result);
    }

    public static function followUser($username, $userTofollow)
    {
        $currentNode = self::getNodeByUsername($username);
        $userNodeToBeFollowed = self::getNodeByUsername($userTofollow);

        return $currentNode->relateTo($userNodeToBeFollowed, 'FOLLOWS')->save();
    }

    public static function unfollowUser($username, $userToUnfollow,$app)
    {
        $queryString = "MATCH (n1 { username: {u} })-[r:FOLLOWS]-(n2 { username: {f} }) DELETE  r";
        $query = new Query(Neo4jClient::client(), $queryString, array('u' => $username,'f' => $userToUnfollow));
        $rel = $query->getResultSet();

        return $rel;
    }

    public static function following($username)
    {
        $queryString = "MATCH (user { username:{u}})-[:FOLLOWS]->(users) RETURN users ORDER BY users.username";
        $query = new Query(Neo4jClient::client(), $queryString, array('u' => $username));
        $result = $query->getResultSet();

        return self::returnAsUsers($result);
    }

    private static function returnAsUsers($results)
    {
        $userArray = array();
        foreach ($results as $row) {
            $userArray[] = self::fromArray($row['x']);
        }

        return $userArray;
    }

    public static function save(User $user)
    {
        if (!$user->node) {
            $user->node = new Node(Neo4jClient::client());
        }
        $userlabel = Neo4jClient::client()->makeLabel('User');
        // set properties
        $user->node->setProperty('username', $user->username);
        $user->node->setProperty('firstname', $user->firstname);
        $user->node->setProperty('lastname', $user->lastname);
        // save the node
        $user->node->save()->addLabels(array($userlabel));
        //set the id on the user object
        $user->id = $user->node->getId();

    }

    public static function getByNodeId($id)
    {
        return self::fromArray( Neo4jClient::client()->getNode($id));
    }

    public static function getUsers($id)
    {
        $node = Neo4jClient::client()->getNode($id);

        return array(self::fromArray($node));
    }

    public static function fromArray(Node $node)
    {
        $user = new User();
        $user->id = $node->getId();
        $user->username = $node->getProperty('username');
        $user->firstname = $node->getProperty('firstname');
        $user->lastname = $node->getProperty('lastname');
        $user->node = $node;

        return $user;
    }
}
