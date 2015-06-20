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
class CategoriesModel
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
     * Gets all categories.
     *
     * @access public
     * @return array Result
     */
    public function getAll()
    {
        $query = 'SELECT category_id, `category_name` FROM categories';
        $result = $this->_db->fetchAll($query);
        return !$result ? array() : $result;
    }


    /**
     * Gets single category data.
     *
     * @access public
     * @param integer $id Record Id
     * @return array Result
     */
    public function getCategory($id)
    {
        if (($id != '') && ctype_digit((string)$id)) {
            $query = 'SELECT category_id, `category_name` FROM categories WHERE category_id= :id';
            $statement = $this->_db->prepare($query);
            $statement->bindValue('id', $id, \PDO::PARAM_INT);
            $statement->execute();
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
            return !$result ? array() : current($result);
        } else {
            return array();
        }
    }

    /**
     * Gets single category data.
     *
     * @access public
     * @param integer $id Record Id
     * @return array Result
     */
    public function getCategoryArticles($id)
    {
        if (($id != '') && ctype_digit((string)$id)) {
            $query = 'SELECT articles.article_id, articles.title,articles.content, articles.category_id
                      FROM articles
                    WHERE articles.category_id = ?';
            $result = $this->_db->fetchAll($query, array($id));
            return $result;
        }
    }


    /**
     * Get all categories on page.
     *
     * @access public
     * @param integer $page Page number
     * @param integer $limit Number of records on single page
     * @return array Result
     */
    public function getCategoriesPage($page, $limit)
    {
        $query = 'SELECT category_id, `category_name` FROM categories';
        $statement = $this->_db->prepare($query);
        $statement->bindValue('start', ($page-1)*$limit, \PDO::PARAM_INT);
        $statement->bindValue('limit', $limit, \PDO::PARAM_INT);
        $statement->execute();
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        return !$result ? array() : $result;
    }

    /**
     * Counts category pages.
     *
     * @access public
     * @param integer $limit Number of records on single page
     * @return integer Result
     */
    public function countCategoriesPages($limit)
    {
        $pagesCount = 0;
        $sql = 'SELECT COUNT(*) as pages_count FROM categories';
        $result = $this->_db->fetchAssoc($sql);
        if ($result) {
            $pagesCount =  ceil($result['pages_count']/$limit);
        }
        return $pagesCount;
    }

    /**
     * Returns current page number.
     *
     * @access public
     * @param integer $page Page number
     * @param integer $pagesCount Number of all pages
     * @return integer Page number
     */
    public function getCurrentPageNumber($page, $pagesCount)
    {
        return (($page < 1) || ($page > $pagesCount)) ? 1 : $page;
    }

    /* Save category.
    *
    * @access public
    * @param array $category Categories data
    * @retun mixed Result
    */
    public function saveCategory($category)
    {
        if (isset($category['id'])
            && ($category['id'] != '')
            && ctype_digit((string)$category['id'])) {
            // update record
            $id = $category['id'];
            unset($category['id']);
            return $this->_db->update('categories', $category, array('category_id' => $id));
        } else {
            // add new record
            return $this->_db->insert('categories', $category);
        }
    }

    public function removeCategory($category)
    {
        if (isset($category['id'])
            && ($category['id'] != '')
            && ctype_digit((string)$category['id'])) {
            // update record
            $id = $category['id'];
            unset($category['id']);
            return $this->_db->delete('categories', array('category_id' => $id));
        }
    }


}