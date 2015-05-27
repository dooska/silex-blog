<?php
/**
 * Index model
 *
 * PHP version 5
 *
 * @category Model
 * @package  Model
 * @author   Dominika Wojna
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @version  SVN: $id$
 * @link     wierzba.wzks.uj.edu.pl/~12_wojna
 */
namespace Model;

use Silex\Application;

/**
 * Class ProjectsModel
 *
 * @category Model
 * @package  Model
 * @author   Dominika Wojna
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @version  Release: <package_version>
 * @link     wierzba.wzks.uj.edu.pl/~12_wojna
 * @uses Doctrine\DBAL\DBALException
 * @uses Silex\Application
 */
class ArticlesModel
{
    /**
     * Database access object.
     *
     * @access protected
     * @var $_db Doctrine\DBAL
     */
    protected $_db;

    /**
     * Class constructor.
     *
     * @param Application $app Silex application object
     *
     * @access public
     */
    public function __construct(Application $app)
    {
        $this->_db = $app['db'];
    }

    /**
     * Gets table with all stocks
     *
     * @return mixed
     */
    /**
     * Gets all albums.
     *
     * @access public
     * @return array Result
     */
    public function getAll()
    {
        $query = 'SELECT article_id, title, content FROM articles';
        $result = $this->_db->fetchAll($query);
        return !$result ? array() : $result;
    }

    /**
     * Gets single album data.
     *
     * @access public
     * @param integer $id Record Id
     * @return array Result
     */
    public function getArticle($id)
    {
        if (($id != '') && ctype_digit((string)$id)) {
            $query = 'SELECT article_id, title, content FROM articles WHERE article_id= :id';
            $statement = $this->_db->prepare($query);
            $statement->bindValue('id', $id, \PDO::PARAM_INT);
            $statement->execute();
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
            return !$result ? array() : current($result);
        } else {
            return array();
        }
    }


}