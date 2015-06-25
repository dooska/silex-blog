<?php
/**
 * Index controller
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
use Model\IndexModel;

/**
 * Class IndexController
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
 * @uses Model\IndexModel
 */
class IndexController implements ControllerProviderInterface
{
    /**
     * UsersModel object.
     *
     * @var $_model
     * @access protected
     */
    protected $model;

    /**
     *
     * @param Application $app application object
     *
     * @access public
     * @return \Silex\ControllerCollection
     */
    public function connect(Application $app)
    {
        $this->model = new IndexModel($app);
        $indexController = $app['controllers_factory'];
        $indexController->get('/', array($this, 'index'))->bind('/index');
        return $indexController;
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
        return $app['twig']->render(
            'index/index.twig'
        );
    }
}
