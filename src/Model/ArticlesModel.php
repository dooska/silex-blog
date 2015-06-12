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
     * Gets all articles.
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
     * Gets single article data.
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


    /**
     * Get all articles on page.
     *
     * @access public
     * @param integer $page Page number
     * @param integer $limit Number of records on single page
     * @return array Result
     */
    public function getArticlesPage($page, $limit)
    {
        $query = 'SELECT article_id, title, content, category_id FROM articles';
        $statement = $this->_db->prepare($query);
        $statement->bindValue('start', ($page-1)*$limit, \PDO::PARAM_INT);
        $statement->bindValue('limit', $limit, \PDO::PARAM_INT);
        $statement->execute();
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        return !$result ? array() : $result;
    }

    /**
     * Counts article pages.
     *
     * @access public
     * @param integer $limit Number of records on single page
     * @return integer Result
     */
    public function countArticlesPages($limit)
    {
        $pagesCount = 0;
        $sql = 'SELECT COUNT(*) as pages_count FROM articles';
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

    /* Save article.
    *
    * @access public
    * @param array $article Articles data
    * @retun mixed Result
    */
    public function saveArticle($article)
    {
        if (isset($article['id'])
            && ($article['id'] != '')
            && ctype_digit((string)$article['id'])) {
            // update record
            $id = $article['id'];
            unset($article['id']);
            return $this->_db->update('articles', $article, array('article_id' => $id));
        } else {
            // add new record
            return $this->_db->insert('articles', $article);
        }
    }

    public function removeArticle($article)
    {
        if (isset($article['id'])
            && ($article['id'] != '')
            && ctype_digit((string)$article['id'])) {
            // update record
            $id = $article['id'];
            unset($article['id']);
            return $this->_db->delete('articles', array('article_id' => $id));
        }
    }
    public function checkArticleId($article_id)
    {
        $sql = 'SELECT * FROM articles WHERE article_id=?';
        $result = $this->_db->fetchAll($sql, array($article_id));

        if ($result) {
            return true;
        } else {
            return false;
        }
    }



}