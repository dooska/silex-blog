<?php
/**
 * Article controller
 *
 * PHP version 5
 *
 * @category Controller
 * @package  Controller
 * @author   Dominika Wojna
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @version  SVN: $id$
 * @link     wierzba.wzks.uj.edu.pl/~12_wojna
 */

namespace Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Model\ArticlesModel;

/**
 * Class ArticleController
 *
 * @category Controller
 * @package  Controller
 * @author   Dominika Wojna
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @version  Release: <package_version>
 * @link     wierzba.wzks.uj.edu.pl/~12_wojna
 * @uses Silex\Application
 * @uses Silex\ControllerProviderInterface
 * @uses Symfony\Component\HttpFoundation\Request
 * @uses Symfony\Component\Validator\Constraints
 * @uses Model\ArticleModel
 */
class ArticlesController implements ControllerProviderInterface
{
    /**
     * Article Model object.
     *
     * @var $_model
     * @access protected
     */
    protected $_model;

    /**
     *
     * @param Application $app application object
     *
     * @access public
     * @return \Silex\ControllerCollection
     */
    public function connect(Application $app)
    {
        $this->_model = new ArticlesModel($app);
        $articlesController = $app['controllers_factory'];
        $articlesController->get('/', array($this, 'index'))->bind('articles_index');
        $articlesController->get('/index', array($this, 'index'));
        $articlesController->get('/index/', array($this, 'index'));
        $articlesController->get('/view/{id}', array($this, 'view'))->bind('article_view');
        $articlesController->get('/view/{id}/', array($this, 'view'));
        return $articlesController;
    }
    /**
     *
     * @param Application $app
     *
     * @access public
     * @return mixed Generates page
     */
    public function index(Application $app)
    {
        $articles = $this->_model->getAll();
        return $app['twig']->render(
            'articles/index.twig',
            array('articles' => $articles)
        );
    }
    
    public function view(Application $app, Request $request)
    {
        $id = (int)$request->get('id', null);
        $article = $this->_model->getArticle($id);
        return $app['twig']->render(
            'articles/view.twig',
            array(
                'article' => $article
            )
        );
    }

}