<?php

namespace GraphStory\GraphKit\Service;

use Everyman\Neo4j\Cypher\Query;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\ResultSet;
use GraphStory\GraphKit\Model\User;
use GraphStory\GraphKit\Neo4jClient;

class UserService
{
    /**
     * Gets User instance by username
     *
     * @param  string $username Username of User to find
     * @return User
     */
    public static function getByUsername($username)
    {
        $userlabel = Neo4jClient::client()->makeLabel('User');
        $nodes = $userlabel->getNodes('username', $username);

        if (empty($nodes) || count($nodes)==0) {
            return null;
        }

        return self::fromNode($nodes[0]);
    }

    /**
     * Finds a User Node by username
     *
     * @param  string $username Username of User to find
     * @return Node   User's Node
     */
    public static function getNodeByUsername($username)
    {
        $userlabel = Neo4jClient::client()->makeLabel('User');
        $nodes = $userlabel->getNodes('username', $username);

        if (empty($nodes) || count($nodes) == 0) {
            return null;
        }

        return $nodes[0];
    }

    /**
     * Find friends the provided user is not already following
     *
     * @param  string $username Username to whom we're providing friend recommendations
     * @return User[] Array of users
     */
    public static function friendSuggestions($username)
    {
        $queryString = <<<CYPHER
MATCH (u:User), (user:User { username:{username}})
WHERE u <> user
AND (NOT (user)-[:FOLLOWS]->(u))
OPTIONAL MATCH (user)-[:FOLLOWS]->(u2)<-[:FOLLOWS]-(u)
WHERE u2 <> u
RETURN u, count(u2) as common
ORDER BY common DESC
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

    /**
     * WIP: Suggest friends using collaborative recommendation
     *
     * @param  string $username Username to whom we're providing friend recommendations
     * @return User[] Array of users
     */
    public static function collaborativeFriendSuggestions($username)
    {
        $queryString = <<<CYPHER
MATCH (u:User { username: { username }})-[:FOLLOWS]->(friend),
(friend)-[:FOLLOWS]->(FoF)
WHERE NOT (u = FoF)
AND NOT (u--FoF)
RETURN COUNT(*) AS weight, FoF.username as recommendation
ORDER BY weight DESC, recommendation DESC
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

    /**
     * Performs wildcard search on username
     *
     * @param  string $username        Username or partial username to search for
     * @param  string $currentusername Username to exclude from results
     * @return User[] Array of users
     */
    public static function searchByUsername($username, $currentusername)
    {
        $username = $username.'.*';
        $queryString = "MATCH (n:User), (user { username:{c}}) WHERE (n.username =~ {u} AND n <> user) AND (NOT (user)-[:FOLLOWS]->(n)) RETURN n";
        $query = new Query(Neo4jClient::client(), $queryString, array('u' => $username,'c' => $currentusername));
        $result = $query->getResultSet();

        return self::returnAsUsers($result);
    }

    /**
     * Follow a user
     *
     * @param  string       $username     User taking the follow action
     * @param  string       $userToFollow User to follow
     * @return Relationship New follows relationship
     */
    public static function followUser($username, $userToFollow)
    {
        $currentNode = self::getNodeByUsername($username);
        $userNodeToBeFollowed = self::getNodeByUsername($userToFollow);

        return $currentNode->relateTo($userNodeToBeFollowed, 'FOLLOWS')->save();
    }

    /**
     * Unfollow a user
     *
     * @param  string    $username       User taking the unfollow action
     * @param  string    $userToUnfollow User to unfollow
     * @return ResultSet
     */
    public static function unfollowUser($username, $userToUnfollow)
    {
        $queryString = "MATCH (n1 { username: {u} })-[r:FOLLOWS]-(n2 { username: {f} }) DELETE  r";
        $query = new Query(
            Neo4jClient::client(),
            $queryString,
            array(
                'u' => $username,
                'f' => $userToUnfollow,
            )
        );

        return $query->getResultSet();
    }

    /**
     * Find users the named user is following
     *
     * @param  string $username
     * @return User[] Users followed by $username
     */
    public static function following($username)
    {
        $queryString = "MATCH (user:User { username:{u}}) WITH user MATCH (user)-[:FOLLOWS]->(users:User) RETURN users ORDER BY users.username";
        $query = new Query(Neo4jClient::client(), $queryString, array('u' => $username));
        $result = $query->getResultSet();

        return self::returnAsUsers($result);
    }

    /**
     * Saves a User
     *
     * @param  User $user User to save
     * @return User Saved user
     */
    public static function save(User $user)
    {
        if (!$user->node) {
            $user->node = new Node(Neo4jClient::client());
        }

        $userLabel = Neo4jClient::client()->makeLabel('User');
        // set properties
        $user->node->setProperty('username', $user->username);
        $user->node->setProperty('firstname', $user->firstname);
        $user->node->setProperty('lastname', $user->lastname);
        // save the node
        $user->node->save()->addLabels(array($userLabel));

        //set the id on the user object
        $user->id = $user->node->getId();

        return $user;
    }

    /**
     * Gets a User by node id
     *
     * @param  int|string $id Node id
     * @return User
     */
    public static function getByNodeId($id)
    {
        return self::fromNode(Neo4jClient::client()->getNode($id));
    }

    /**
     * Creates an array of users from a ResultSet
     *
     * @param  ResultSet $results Query results
     * @return User[]    Array of users
     */
    protected static function returnAsUsers(ResultSet $results)
    {
        $userArray = array();
        foreach ($results as $row) {
            $user = self::fromNode($row['x']);
            if (isset($row['common'])){
                $user->commonFriends = $row['common'];
            }
            $userArray[] = $user;
        }

        return $userArray;
    }

    /**
     * Create User object from Node
     *
     * @param  Node $node User node
     * @return User
     */
    protected static function fromNode(Node $node)
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
