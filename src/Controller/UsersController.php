<?php
/**
 * User controller
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
use Model\UsersModel;

/**
 * Class UserController
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
 * @uses Model\UserModel
 * @uses Model\CategoriesModel
 */
class UsersController implements ControllerProviderInterface
{
    /**
     * User Model object.
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
        $this->_model = new UsersModel($app);
        $usersController = $app['controllers_factory'];
        $usersController->post('/add', array($this, 'addAction'));
        $usersController->match('/add', array($this, 'addAction'))
            ->bind('users_add');
        $usersController->match('/add/', array($this, 'addAction'));
        $usersController->post('/edit', array($this, 'editAction'));
        $usersController->match('/edit', array($this, 'editAction'))
            ->bind('users_edit');
        $usersController->match('/edit', array($this, 'editAction'));
        $usersController->post('/delete/{id}', array($this, 'deleteAction'));
        $usersController->match('/delete/{id}', array($this, 'deleteAction'))
            ->bind('users_delete');
        $usersController->match('/delete/{id}/', array($this, 'deleteAction'));
        $usersController->get('/view', array($this, 'viewAction'))
            ->bind('users_view');
        $usersController->get('/view', array($this, 'viewAction'));
        $usersController->get('/index', array($this, 'indexAction'));
        $usersController->get('/index/', array($this, 'indexAction'))
            ->bind('users_index');
//        $usersController->get('/{page}', array($this, 'indexAction'))
//            ->value('page', 1)->bind('users_index');
        return $usersController;
    }

    /**
     * Index action.
     *
     * @access public
     * @param Silex\Application $app Silex application
     * @param Symfony\Component\HttpFoundation\Request $request Request object
     * @return string Output
     */
    public function indexAction(Application $app, Request $request)
    {}

    public function viewAction(Application $app, Request $request)
    {
        $currentUser = $this->_model->getCurrentUserInfo($app);

        if (count($currentUser)) {
            return $app['twig']->render(
                'users/view.twig', array(
                    'user' => $currentUser,
                    'userinfo' => $currentUser
                )
            );
        } else {
            $app['session']->getFlashBag()->add(
                'message', array(
                    'type' => 'danger',
                    'content' => 'Nie znaleziono użytkownika'
                )
            );
            return $app->redirect(
                $app['url_generator']->generate(
                    'articles_index'
                ), 301
            );
        }
    }

    public function editAction(Application $app, Request $request)
    {
        $currentUser = $this->_model->getCurrentUserInfo($app);


        $data = array(
            'login' => $currentUser['login'],
            'email' => $currentUser['email'],
            'password' => '',
            'confirm_password' => ''
        );
        $form = $app['form.factory']->createBuilder('form', $data)
            ->add(
                'login', 'text', array(
                    'label' => 'Login',
                    'constraints' => array(
                        new Assert\NotBlank()
                    )
                )
            )
            ->add(
                'email', 'text', array(
                    'label' => 'Email',
                    'constraints' => array(
                        new Assert\NotBlank(),
                        new Assert\Email(
                            array(
                                'message' => 'Wrong email'
                            )
                        )
                    )
                )
            )
            ->add(
                'password', 'password', array(
                    'label' => 'Nowe hasło',
                    'constraints' => array(
                        new Assert\NotBlank()
                    )
                )
            )
            ->add(
                'confirm_password', 'password', array(
                    'label' => 'Potwierdź hasło',
                    'constraints' => array(
                        new Assert\NotBlank()
                    )
                )
            )
            ->getForm();


        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();

            $data['login'] = $app
                ->escape($data['login']);
            $data['email'] = $app
                ->escape($data['email']);
            $data['password'] = $app
                ->escape($data['password']);
            $data['confirm_password'] = $app
                ->escape($data['confirm_password']);

            if ($data['password'] === $data['confirm_password']) {
                $password = $app['security.encoder.digest']
                    ->encodePassword(
                        $data['password'], ''
                    );


                $checkLogin = $this->_model
                    ->getUserByLogin(
                        $data['login']
                    );

                if ($data['login'] === $checkLogin ||
                    !$checkLogin ||
                    (int)$currentUser['id'] ===(int)$checkLogin['id']) {
                    try
                    {

                        $model = $this->_model->updateUser(
                            (int)$currentUser['id'],
                            $form->getData(),
                            $password
                        );
                        if($model) {

                        $app['session']->getFlashBag()->add(
                            'message', array(
                                'type' => 'success',
                                'content' => 'Edycja konta udała się,
                                    możesz się teraz ponownie zalogować'
                            )
                        );
                        return $app->redirect(
                            $app['url_generator']
                                ->generate(
                                    'users_view'
                                ), 301
                        );
                    } else {
                            $app['session']->getFlashBag()->add(
                                'message', array(
                                    'type' => 'success',
                                    'content' => 'Edycja konta nie udała się.'
                                )
                            );
                            return $app->redirect(
                                $app['url_generator']
                                    ->generate(
                                        'auth_logout'
                                    ), 301
                            );
                        }
                    }
                    catch (\Exception $e)
                    {
                        $errors[] = 'Edycja konta nie powiodła się';
                    }

                } else {
                    $app['session']->getFlashBag()->add(
                        'message', array(
                            'type' => 'warning',
                            'content' => 'Login zajęty'
                        )
                    );
                    return $app['twig']->render(
                        'users/edit.twig', array(
                            'form' => $form->createView(),
                            'login' => $currentUser
                        )
                    );
                }
            } else {
                $app['session']->getFlashBag()->add(
                    'message', array(
                        'type' => 'warning',
                        'content' => 'Hasła różnią się'
                    )
                );
                return $app['twig']->render(
                    'users/edit.twig', array(
                        'form' => $form->createView(),
                        'login' => $currentUser
                    )
                );

            }
        }
        return $app['twig']->render(
            'users/edit.twig', array(
                'form' => $form->createView(),
                'login' => $currentUser
            )
        );
    }
}