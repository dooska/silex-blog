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
use Model\CategoriesModel;
use Model\CommentsModel;
use Model\UsersModel;

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
 * @uses Model\CategoriesModel
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
    protected $_category_model;
    protected $_comments_model;
    protected $_keywords_model;
    protected $_users_model;

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
        $this->_category_model = new CategoriesModel($app);
        $this->_comments_model = new CommentsModel($app);
        $this->_users_model = new UsersModel($app);
        $articlesController = $app['controllers_factory'];
        $articlesController->post('/add', array($this, 'addAction'));
        $articlesController->match('/add', array($this, 'addAction'))
            ->bind('articles_add');
        $articlesController->match('/add/', array($this, 'addAction'));
        $articlesController->post('/edit/{id}', array($this, 'editAction'));
        $articlesController->match('/edit/{id}', array($this, 'editAction'))
            ->bind('articles_edit');
        $articlesController->match('/edit/{id}/', array($this, 'editAction'));
        $articlesController->post('/delete/{id}', array($this, 'deleteAction'));
        $articlesController->match('/delete/{id}', array($this, 'deleteAction'))
            ->bind('articles_delete');
        $articlesController->match('/delete/{id}/', array($this, 'deleteAction'));
        $articlesController->get('/view/{id}', array($this, 'viewAction'))
            ->bind('articles_view');
        $articlesController->get('/view/{id}/', array($this, 'viewAction'));
        $articlesController->get('/index', array($this, 'indexAction'));
        $articlesController->get('/index/', array($this, 'indexAction'))
            ->bind('articles_index');
//        $articlesController->get('/{page}', array($this, 'indexAction'))
//            ->value('page', 1)->bind('articles_index');
        return $articlesController;
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
    {
        try {

            $pageLimit = 10;
            $page = (int) $request->get('page', 1);
            $pagesCount = $this->_model->countArticlesPages($pageLimit);
            $page = $this->_model->getCurrentPageNumber($page, $pagesCount);
            $articles = $this->_model->getArticlesPage($page, $pageLimit);
            $this->view['paginator']
                = array('page' => $page, 'pagesCount' => $pagesCount);
            $this->view['articles'] = $articles;
        } catch (\PDOException $e){
            $app->abort(404, $app['translator']->trans('Articles not found'));
        }
        return $app['twig']->render('articles/index.twig', $this->view);
    }

    /**
     * View action.
     *
     * @access public
     * @param Silex\Application $app Silex application
     * @param Symfony\Component\HttpFoundation\Request $request Request object
     * @return string Output
     */
    public function viewAction(Application $app, Request $request)
    {
        try {
            $id = (int)$request->get('id', null);
            $this->view['article'] = $this->_model->getArticle($id);
            $this->view['comments'] = $this->_comments_model->getCommentsList($id);
            $this->view['keywords'] = $this->_model->getArticleKeywords($id);
            $checkUser = $this->_users_model->_isLoggedIn($app);
        } catch (\PDOException $e){
            $app->abort(404, $app['translator']->trans('Album not found'));
        }
        if($checkUser) {
            $this->view['user'] = $this->_users_model->getCurrentUserInfo($app);
        }
        return $app['twig']->render('articles/view.twig', $this->view);
    }


    /**
     * Add action.
     *
     * @access public
     * @param Silex\Application $app Silex application
     * @param Symfony\Component\HttpFoundation\Request $request Request object
     * @return string Output
     */
    public function addAction(Application $app, Request $request)
    {
        if ($app['security']->isGranted('ROLE_ADMIN')) {
            try {

                $data = array(
                    'title' => 'Title',
                    'content' => 'Content',
                );

                $categories = $this->_category_model->getCategoriesToForm();

                $form = $app['form.factory']->createBuilder('form', $data)
                    ->add(
                        'title', 'text',
                        array(
                            'constraints' => array(
                                new Assert\NotBlank(),
                                new Assert\Length(array('min' => 5))
                            ),
                            'attr' => array(
                                'class' => 'form-control'
                            )
                        )
                    )
                    ->add(
                        'content', 'textarea',
                        array(
                            'constraints' => array(
                                new Assert\NotBlank(),
                                new Assert\Length(array('min' => 5))
                            ),
                            'attr' => array(
                                'class' => 'form-control'
                            )
                        )
                    )
                    ->add(
                        'category_id', 'choice',
                        array(
                            'choices' => $categories,
                            'constraints' => array(
                                new Assert\NotBlank(),
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
                    $this->_model->saveArticle($data);


                    $app['session']->getFlashBag()->add(
                        'message', array(
                            'type' => 'success', 'content' => $app['translator']->trans('Dodałeś nowy wpis.')
                        )
                    );

                    return $app->redirect(
                        $app['url_generator']->generate('articles_index'),
                        301
                    );

                }
            } catch (\PDOException $e) {
                $app->abort(500, $app['translator']->trans('An error occurred, please try again later'));
            }
            $this->view['form'] = $form->createView();


            return $app['twig']->render('articles/add.twig', $this->view);
        } else {
            $app['session']->getFlashBag()->add(
                'message', array(
                    'type' => 'danger', 'content' => $app['translator']->trans('Nie masz odpowiednich uprawnień do tej czynności!')
                )
            );
            return $app->redirect(
                $app['url_generator']->generate('articles_index'),
                301
            );
        }
    }

    /**
     * Edit action.
     *
     * @access public
     * @param Silex\Application $app Silex application
     * @param Symfony\Component\HttpFoundation\Request $request Request object
     * @return string Output
     */
    public function editAction(Application $app, Request $request)
    {
        if ($app['security']->isGranted('ROLE_ADMIN')) {
            try {

                $articlesModel = new ArticlesModel($app);
                $id = (int) $request->get('id', 0);
                $article = $articlesModel->getArticle($id);
                $categories = $this->_category_model->getCategoriesToForm();

                if (count($article)) {

                    $form = $app['form.factory']->createBuilder('form', $article)
                        ->add(
                            'id', 'hidden',
                            array(
                                'data' => $id,
                                'constraints' => array(
                                    new Assert\NotBlank(),
                                    new Assert\Type(array('type' => 'digit'))
                                ),
                                'attr' => array(
                                    'class' => 'form-control'
                                )
                            )
                        )
                        ->add(
                            'title', 'text',
                            array(
                                'constraints' => array(
                                    new Assert\NotBlank(),
                                    new Assert\Length(array('min' => 5))
                                ),
                                'attr' => array(
                                    'class' => 'form-control'
                                )
                            )
                        )
                        ->add(
                            'content', 'text',
                            array(
                                'constraints' => array(
                                    new Assert\NotBlank(),
                                    new Assert\Length(array('min' => 5))
                                ),
                                'attr' => array(
                                    'class' => 'form-control'
                                )
                            )
                        )
                        ->add(
                            'category_id', 'choice',
                            array(
                                'choices' => $categories,
                                'constraints' => array(
                                    new Assert\NotBlank(),
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
                        $articlesModel = new ArticlesModel($app);
                        $articlesModel->saveArticle($data);

                        $app['session']->getFlashBag()->add(
                            'message', array(
                                'type' => 'success', 'content' => $app['translator']->trans('Edytowałeś wpis.')
                            )
                        );
                        return $app->redirect(
                            $app['url_generator']->generate('articles_index'),
                            301
                        );
                    }

                    $this->view['id'] = $id;
                    $this->view['form'] = $form->createView();

                } else {
                    $app['session']->getFlashBag()->add(
                        'message', array(
                            'type' => 'warning', 'content' => $app['translator']->trans('Wpis nie istnieje.')
                        )
                    );
                    return $app->redirect(
                        $app['url_generator']->generate('articles_index'),
                        301
                    );
                }
            } catch (\PDOException $e) {
                $app->abort(500, $app['translator']->trans('An error occurred, please try again later'));
            }

            return $app['twig']->render('articles/edit.twig', $this->view);
        } else {
            $app['session']->getFlashBag()->add(
                'message', array(
                    'type' => 'danger', 'content' => $app['translator']->trans('Nie masz odpowiednich uprawnień do tej czynności!')
                )
            );
            return $app->redirect(
                $app['url_generator']->generate('articles_index'),
                301
            );
        }
    }

    public function deleteAction(Application $app, Request $request)
    {
        if ($app['security']->isGranted('ROLE_ADMIN')) {
            try {

                $articlesModel = new ArticlesModel($app);
                $id = (int) $request->get('id', 0);
                $article = $articlesModel->getArticle($id);

                if (count($article)) {
                    $data = array();
                    $form = $app['form.factory']->createBuilder('form', $data)
                        ->add(
                            'id', 'hidden', array(
                                'data' => $id,
                            )
                        )
                        ->add('Tak', 'submit')
                        ->add('Nie', 'submit')
                        ->getForm();

                    $form->handleRequest($request);

                    if ($form->isValid()) {
                        if ($form->get('Tak')->isClicked()) {
                            $data = $form->getData();

                            $this->_model->removeArticle($data);

                            $app['session']->getFlashBag()->add(
                                'message', array(
                                    'type' => 'success',
                                    'content' =>
                                        'Artykuł został usunięty'
                                )
                            );
                            return $app->redirect(
                                $app['url_generator']->generate(
                                    'articles_index'
                                ), 301
                            );

                        } else {
                            return $app->redirect(
                                $app['url_generator']->generate(
                                    'articles_index'
                                ), 301
                            );
                        }
                    }

                    $this->view['id'] = $id;
                    $this->view['form'] = $form->createView();

                } else {
                    $app['session']->getFlashBag()->add(
                        'message', array(
                            'type' => 'danger',
                            'content' => 'Nie znaleziono postu'
                        )
                    );
                    return $app->redirect(
                        $app['url_generator']->generate(
                            'articles_index'
                        ), 301
                    );
                }
            } catch (\Exception $e) {
                $errors[] = 'Coś poszło niezgodnie z planem';
            }
            return $app['twig']->render('articles/delete.twig', $this->view);
        } else {
            $app['session']->getFlashBag()->add(
                'message', array(
                    'type' => 'danger', 'content' => $app['translator']->trans('Nie masz odpowiednich uprawnień do tej czynności!')
                )
            );
            return $app->redirect(
                $app['url_generator']->generate('articles_index'),
                301
            );
        }
    }


}