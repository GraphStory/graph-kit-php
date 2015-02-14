<?php

namespace GraphStory\GraphKit\Service;

use GraphStory\GraphKit\Model\User;
use GraphStory\GraphKit\Neo4jClient;
use Neoxygen\NeoClient\Formatter\Node;

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
        $query = 'MATCH (user:User) WHERE user.username = {username} RETURN user';
        $params = array(
            'username' => (string) $username
        );
        $result = Neo4jClient::client()->sendCypherQuery($query, $params)->getResult();
        $user = $result->get('user');

        if (null !== $user) {

            return self::fromNode($user);
        }

        return null;
    }

    /**
     * Finds a User Node by username
     *
     * @param  string $username Username of User to find
     * @return Node   User's Node
     */
    public static function getNodeByUsername($username)
    {
        $query = 'MATCH (user:User) WHERE u.username = {username} RETURN user';
        $params = array(
            'username' => (string) $username
        );
        $result = Neo4jClient::client()->sendCypherQuery($query, $params)->getResult();
        $user = $result->getSingleNode('User');

        if (empty($nodes) || count($nodes) == 0) {
            return;
        }

        return $user;
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
MATCH (u:User { username: { username }})
WITH u
MATCH (u)-[:FOLLOWS]->(friend),
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
        $username = $username . '.*';
        $queryString = "MATCH (n:User), (user:User { username:{c}}) WHERE (n.username =~ {u} AND n <> user) AND (NOT (user)-[:FOLLOWS]->(n)) RETURN n";
        $query = new Query(Neo4jClient::client(), $queryString, array('u' => $username, 'c' => $currentusername));
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
        $queryString = "MATCH (n1:User { username: {u} }) WITH n1 MATCH (n1)-[r:FOLLOWS]-(n2 { username: {f} }) DELETE  r";
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
        $query = 'MERGE (user:User {username: {username}})
        ON CREATE SET user.firstname = {firstname}, user.lastname = {lastname}
        RETURN user';
        $params = array(
            'username' => $user->getUsername(),
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname()
        );

        $result = Neo4jClient::client()->sendCypherQuery($query, $params)->getResult();

        return self::fromNode($result->get('user'));
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
            if (isset($row['common'])) {
                $user->commonFriends = $row['common'];
            }
            $userArray[] = $user;
        }

        return $userArray;
    }

    /**
     * Create User object from Node
     *
     * @param  Node $node Neo4j Node Object
     * @return User
     */
    public static function fromNode(Node $node)
    {
        $user = new User();
        $user->setId($node->getId());
        $user->setUsername($node->getProperty('username'));
        $user->setFirstname($node->getProperty('firstname'));
        $user->getLastname($node->getProperty('lastname'));

        return $user;
    }
}
