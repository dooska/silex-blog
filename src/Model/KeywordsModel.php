<?php
/**
 * Index model
 *
 * PHP version 5
 *
 * @keyword Model
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
 * @keyword Model
 * @package  Model
 * @author   Dominika Wojna
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @version  Release: <package_version>
 * @link     wierzba.wzks.uj.edu.pl/~12_wojna
 * @uses Doctrine\DBAL\DBALException
 * @uses Silex\Application
 */
class KeywordsModel
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
     * Gets all keywords.
     *
     * @access public
     * @return array Result
     */
    public function getAll()
    {
        $query = 'SELECT * FROM keywords';
        $result = $this->_db->fetchAll($query);
        return !$result ? array() : $result;
    }

    /**
     * Gets keywords array.
     *
     * @access public
     * @return array Result
     */
    public function getKeywordsArray()
    {
        $keywordsArray = array();

        //tworzy tablicę asocjacyjną z tabeli kategorii
        $keywords = $this->getAll();

        foreach ($keywords as $keyword) {
            $keywordsArray[$keyword['keyword_id']] = $keyword['word'];
        }
        return $keywordsArray;
    }

    /**
     * Gets single keyword data.
     *
     * @access public
     * @param integer $id Record Id
     * @return array Result
     */
    public function getKeyword($id)
    {
        if (($id != '') && ctype_digit((string)$id)) {
            $query = 'SELECT keyword_id, `word` FROM keywords WHERE keyword_id= :id';
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
     * Gets single keyword data.
     *
     * @access public
     * @param integer $id Record Id
     * @return array Result
     */
    public function getKeywordArticles($id)
    {
        if (($id != '') && ctype_digit((string)$id)) {
            $query = 'SELECT articles.article_id, articles.title, articles.content, article_keywords.article_id, article_keywords.keyword_id
                        FROM articles
                        JOIN article_keywords
                        ON articles.article_id = article_keywords.article_id
                        WHERE article_keywords.keyword_id = ?';
            $result = $this->_db->fetchAll($query, array($id));
            return $result;
        }
    }


    /**
     * Get all keywords on page.
     *
     * @access public
     * @param integer $page Page number
     * @param integer $limit Number of records on single page
     * @return array Result
     */
    public function getKeywordsPage($page, $limit)
    {
        $query = 'SELECT keyword_id, `word` FROM keywords';
        $statement = $this->_db->prepare($query);
        $statement->bindValue('start', ($page-1)*$limit, \PDO::PARAM_INT);
        $statement->bindValue('limit', $limit, \PDO::PARAM_INT);
        $statement->execute();
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        return !$result ? array() : $result;
    }

    /**
     * Counts keyword pages.
     *
     * @access public
     * @param integer $limit Number of records on single page
     * @return integer Result
     */
    public function countKeywordsPages($limit)
    {
        $pagesCount = 0;
        $sql = 'SELECT COUNT(*) as pages_count FROM keywords';
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

    /* Save keyword.
    *
    * @access public
    * @param array $keyword Keywords data
    * @retun mixed Result
    */
    public function saveKeyword($keyword)
    {
        if (isset($keyword['id'])
            && ($keyword['id'] != '')
            && ctype_digit((string)$keyword['id'])) {
            // update record
            $id = $keyword['id'];
            unset($keyword['id']);
            return $this->_db->update('keywords', $keyword, array('keyword_id' => $id));
        } else {
            // add new record
            return $this->_db->insert('keywords', $keyword);
        }
    }

    public function removeKeyword($keyword)
    {
        if (isset($keyword['id'])
            && ($keyword['id'] != '')
            && ctype_digit((string)$keyword['id'])) {
            // update record
            $id = $keyword['id'];
            unset($keyword['id']);
            $this->removeKeywordArticles((int)$keyword['id']);
            return $this->_db->delete('keywords', array('keyword_id' => $id));
        }
    }

    protected function removeKeywordArticles($id)
    {
        $query = 'DELETE FROM `article_keywords` WHERE `keyword_id`=?';
        $this->_db->executeQuery($query, array($id));
    }

    public function checkIfKeywordForArticleExist($data)
    {
        $query = 'SELECT article_id, keyword_id
                  FROM `article_keywords`
                  WHERE(`article_id` = ? AND `keyword_id` = ?)';
        $result = $this->_db->fetchAssoc($query, array((int)$data['article_id'], (int)$data['keyword_id']));
        return $result;
    }

    public function connectKeywordWithArticle($data)
    {
        $query = 'INSERT INTO `silex-blog`.`article_keywords` (`article_id`, `keyword_id`) VALUES (?, ?)';
        $result = $this->_db->executeQuery($query, array((int)$data['article_id'], (int)$data['keyword_id']));
        return $result;
    }

    public function disconnectKeywordAndArticle($data)
    {
        $query = 'DELETE FROM `article_keywords` WHERE `id`=?';
        $this->_db->executeQuery($query, array($data['record_id']));
    }

    public function checkIfKeywordExists($data)
    {
        $query = 'SELECT keyword_id
                  FROM `keywords`
                  WHERE(`word` = ?)';
        $result = $this->_db->fetchAssoc($query, array($data['word']));
        return $result;
    }

    public function checkIfConnectionExists($record_id)
    {
        $query = 'SELECT * FROM `article_keywords` WHERE id = ?';
        $result = $this->_db->fetchAssoc($query, array($record_id));
        return $result;
    }


}