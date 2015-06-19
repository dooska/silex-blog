<?php
/**
 * Index model
 *
 * PHP version 5
 *
 * @comment Model
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
 * @comment Model
 * @package  Model
 * @author   Dominika Wojna
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @version  Release: <package_version>
 * @link     wierzba.wzks.uj.edu.pl/~12_wojna
 * @uses Doctrine\DBAL\DBALException
 * @uses Silex\Application
 */
class CommentsModel
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
     * Gets one comment.
     *
     * @param Integer $idcomment
     *
     * @access public
     * @return array Associative array with comments
     */
    public function getComment($idcomment)
    {
        $sql = 'SELECT * FROM comments WHERE comment_id = ? LIMIT 1';
        return $this->_db->fetchAssoc($sql, array($idcomment));
    }

    /**
     * Get all comments for one post
     *
     * @param $id post id
     *
     * @access public
     * @internal param int $idpost
     * @return Array Comment
     */
    public function getCommentsList($id)
    {
        $sql = 'SELECT * FROM comments WHERE article_id = ?';
        return $this->_db->fetchAll($sql, array($id));
    }

    /**
     * Add one comment.
     *
     * @param  Array $data date about addcomment.
     *
     * @access public
     * @return Void
     */
    public function addComment($data)
    {
        $sql = 'INSERT INTO comments
            (comment_content, published_date, article_id, user_id)
            VALUES (?,?,?,?)';
        $this->_db
            ->executeQuery(
                $sql,
                array(
                    $data['comment_content'],
                    $data['published_date'],
                    $data['article_id'],
                    $data['user_id']
                )
            );
    }

    /**
     * Updates one comment.
     *
     * @param Array $data date about update comment.
     *
     * @access public
     * @return Void
     */
    public function editComment($data)
    {


        if (isset($data['comment_id'])
            && ctype_digit((string)$data['idcomment'])) {
            $sql = 'UPDATE comments
                SET comment_content = ?, published_date = ?
            WHERE comment_id = ?';
            $this->_db->executeQuery(
                $sql, array(
                    $data['content'],
                    $data['published_date'],
                    $data['comment_id']
                )
            );
        }
    }

    /**
     * Delete one comment.
     *
     * @param Array $data date about delete comment.
     *
     * @access public
     * @return Void
     */
    public function deleteComment($data)
    {
        $sql = 'DELETE FROM `comments` WHERE `comment_id`= ?';
        $this->_db->executeQuery($sql, array($data['comment_id']));
    }


    /**
     * Check if comment id exists
     *
     * @param $idcomment id comment
     *
     * @access public
     * @return bool true if exists.
     */
    public function checkCommentId($idcomment)
    {
        $sql = 'SELECT * FROM comments WHERE comment_id=?';
        $result = $this->_db->fetchAll($sql, array($idcomment));

        if ($result) {
            return true;
        } else {
            return false;
        }
    }
}