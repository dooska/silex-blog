<?php
/**
 * Users model.
 *
 * @author EPI <epi@uj.edu.pl>
 * @link http://epi.uj.edu.pl
 * @copyright 2015 EPI
 */

namespace Model;

use Doctrine\DBAL\DBALException;
use Silex\Application;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

/**
 * Class Users.
 *
 * @category Epi
 * @package Model
 * @use Silex\Application
 */
class UsersModel
{
    /**
     * Silex application object
     *
     * @access protected
     * @var $_app Silex\Application
     */
    protected $_app;

    /**
     * Db object.
     *
     * @access protected
     * @var Silex\Provider\DoctrineServiceProvider $db
     */
    protected $_db;

    /**
     * Object constructor.
     *
     * @access public
     * @param Silex\Application $app Silex application
     */
    public function __construct(Application $app)
    {
        $this->_app = $app;
        $this->_db = $app['db'];
    }

    /**
     * Puts one user to database.
     *
     * @param  Array $data Associative array contains all necessary information
     * @param string $password encoded password
     *
     * @access public
     * @return Void
     */
    public function register($data, $password)
    {
        $role = 2;
        $query = 'INSERT INTO `users`
                  (`login`, `email`, `password`, `role_id`)
                  VALUES (?, ?, ?, ?)';
        $this->_db->executeQuery(
            $query, array(
                $data['login'],
                $data['email'],
                $password,
                $role
            )
        );
    }

    /**
     * Loads user by login.
     *
     * @access public
     * @param string $login User login
     * @throws UsernameNotFoundException
     * @return array Result
     */
    public function loadUserByLogin($login)
    {
        $user = $this->getUserByLogin($login);

        if (!$user || !count($user)) {
            throw new UsernameNotFoundException(
                sprintf('Username "%s" does not exist.', $login)
            );
        }

        $roles = $this->getUserRoles($user['id']);

        if (!$roles || !count($roles)) {
            throw new UsernameNotFoundException(
                sprintf('Username "%s" does not exist.', $login)
            );
        }

        return array(
            'login' => $user['login'],
            'password' => $user['password'],
            'roles' => $roles
        );

    }

    /**
     * Gets user data by login.
     *
     * @access public
     * @param string $login User login
     *
     * @return array Result
     */
    public function getUserByLogin($login)
    {
        try {
            $query = '
              SELECT
                `id`, `login`, `password`, `role_id`
              FROM
                `users`
              WHERE
                `login` = :login
            ';
            $statement = $this->_db->prepare($query);
            $statement->bindValue('login', $login, \PDO::PARAM_STR);
            $statement->execute();
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
            return !$result ? array() : current($result);
        } catch (\PDOException $e) {
            return array();
        }
    }

    /**
     * Gets user roles by User ID.
     *
     * @access public
     * @param integer $userId User ID
     *
     * @return array Result
     */
    public function getUserRoles($userId)
    {
        $roles = array();
        try {
            $query = '
                SELECT
                    `roles`.`name` as `role`
                FROM
                    `users`
                INNER JOIN
                    `roles`
                ON `users`.`role_id` = `roles`.`id`
                WHERE
                    `users`.`id` = :user_id
                ';
            $statement = $this->_db->prepare($query);
            $statement->bindValue('user_id', $userId, \PDO::PARAM_INT);
            $statement->execute();
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
            if ($result && count($result)) {
                $result = current($result);
                $roles[] = $result['role'];
            }
            return $roles;
        } catch (\PDOException $e) {
            return $roles;
        }
    }

    /**
     * Get current logged user id
     *
     * @param $app
     *
     * @access public
     * @return mixed
     */
    public function getIdCurrentUser($app)
    {

        $login = $this->getCurrentUser($app);
        $iduser = $this->getUserByLogin($login);


        return $iduser['id'];
    }

    /**
     * Get information about actual logged user
     *
     * @param $app
     *
     * @access protected
     * @return mixed
     */
    protected function getCurrentUser($app)
    {
        $token = $app['security']->getToken();

        if (null !== $token) {
            $user = $token->getUser()->getUsername();
        }

        return $user;
    }

    /**
     * Check if user is logged
     *
     * @param Application $app
     *
     * @access public
     * @return bool
     */
    public function _isLoggedIn(Application $app)
    {
        if ('anon.' !== $user = $app['security']->getToken()->getUser()) {
            return true;
        } else {
            return false;
        }
    }






    /**
     * @param $app
     * @access public
     *
     * @return Array
     */
    public function getCurrentUserInfo($app)
    {
        $login = $this->getCurrentUsername($app);
        $info = $this->getUserInfo($login);

        return $info;
    }

    /**
     * This method gets currently logged user.
     *
     * @access public
     * @param application
     *
     * @return array $user
     *
     */
    protected function getCurrentUsername($app)
    {
        $token = $app['security']->getToken();

        if (null !== $token) {
            $user = $token->getUser()->getUsername();
        }

        return $user;
    }

    /**
     * Gets user by login.
     *
     * @param string $login
     *
     * @access public
     * @return Array Information about searching user.
     */
    public function getUserInfo($login)
    {
        $sql = 'SELECT id, login, email, role_id FROM users WHERE login = ?';
        return $this->_db->fetchAssoc($sql, array((string) $login));
    }



    /**
     * Changes information about user
     *
     * @param $data
     */
    public function removeUser($data)
    {
        $query = 'DELETE FROM users WHERE id = ?';
        return $this->_db->executeQuery($query, array((int)($data['id'])));
    }


}