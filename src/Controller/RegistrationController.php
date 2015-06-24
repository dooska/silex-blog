<?php
/**
 * Registration controller.
 *
 * @author EPI <epi@uj.edu.pl>
 * @link http://epi.uj.edu.pl
 * @copyright 2015 EPI
 */

namespace Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form;
use Model\UsersModel;

/**
 * Class RegistrationController.
 *
 * @package Controller
 * @implements ControllerProviderInterface
 */
class RegistrationController implements ControllerProviderInterface
{
    /**
     * Data for view.
     *
     * @access protected
     * @var array $view
     */
    protected $view = array();

    protected $_model;

    /**
     * Connection
     *
     * @param Application $app application object
     *
     * @access public
     * @return \Silex\ControllerCollection
     */
    public function connect(Application $app)
    {
        $this->_model = new UsersModel($app);
        $registrationController = $app['controllers_factory'];
        $registrationController->match('/', array($this, 'register'))
            ->bind('register');
        $registrationController->match('/success', array($this, 'success'))
            ->bind('/register/success');
        return $registrationController;
    }

    /**
     * Function adds new user to database
     *
     * @param Application $app
     * @param Request     $request
     *
     * @access public
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function register(Application $app, Request $request)
    {
        try
        {
            $data = array();
            $form = $app['form.factory']->createBuilder('form', $data)
                ->add(
                    'login', 'text', array(
                        'constraints' => array(
                            new Assert\NotBlank()
                        ),
                        'attr' => array(
                            'class' => 'form-control'
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
                                    'message' => 'Email nie jest poprawny'
                                )
                            )
                        ),
                        'attr' => array(
                            'class' => 'form-control'
                        )
                    )
                )
                ->add(
                    'password', 'password', array(
                        'label' => $app['translator']->trans('password'),
                        'constraints' => array(
                            new Assert\NotBlank()
                        ),
                        'attr' => array(
                            'class' => 'form-control'
                        )
                    )
                )
                ->add(
                    'confirm_password', 'password', array(
                        'label' => $app['translator']->trans('password_confirmation'),
                        'constraints' => array(
                            new Assert\NotBlank()
                        ),
                        'attr' => array(
                            'class' => 'form-control'
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
                        ->encodePassword($data['password'], '');

                    $checkLogin = $this->_model->getUserByLogin(
                        $data['login']
                    );

                    if (!$checkLogin) {

                        $this->_model->register(
                            $data,
                            $password
                        );
                        return $app->redirect(
                            $app['url_generator']
                                ->generate(
                                    '/register/success'
                                ), 301
                        );

                    } else {
                        $app['session']->getFlashBag()->add(
                            'message', array(
                                'type' => 'warning',
                                'content' => $app['translator']->trans('login_not_available')
                            )
                        );
                        return $app['twig']->render(
                            'users/register.twig', array(
                                'form' => $form->createView()
                            )
                        );
                    }
                } else {
                    $app['session']->getFlashBag()->add(
                        'message', array(
                            'type' => 'warning',
                            'content' => $app['translator']->trans('passwords_differ')
                        )
                    );
                    return $app['twig']->render(
                        'users/register.twig', array(
                            'form' => $form->createView()
                        )
                    );
                }
            }

            return $app['twig']->render(
                'users/register.twig', array(
                    'form' => $form->createView(),
                    'data' => $data
                )
            );
        }
        catch (\PDOException $e)
        {
            $app->abort(500, $app['translator']
                ->trans('error_occured'));
        }
    }

    /**
     * Generates page with information about successful registration
     *
     * @param Application $app
     * @access public
     * @return mixed
     */
    public function success(Application $app)
    {
        $link = $app['url_generator']->generate(
            'auth_login'
        );
        return $app['twig']->render(
            'users/successfulRegistration.twig', array(
                'login_link' => $link
            )
        );
    }
}