<?php
/**
 * Category controller
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
use Model\CategoriesModel;

/**
 * Class CategoryController
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
 * @uses Model\CategoryModel
 */
class CategoriesController implements ControllerProviderInterface
{
    /**
     * Category Model object.
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
        $this->_model = new CategoriesModel($app);
        $categoriesController = $app['controllers_factory'];
        $categoriesController->post('/add', array($this, 'addAction'));
        $categoriesController->match('/add', array($this, 'addAction'))
            ->bind('categories_add');
        $categoriesController->match('/add/', array($this, 'addAction'));
        $categoriesController->post('/edit/{id}', array($this, 'editAction'));
        $categoriesController->match('/edit/{id}', array($this, 'editAction'))
            ->bind('categories_edit');
        $categoriesController->match('/edit/{id}/', array($this, 'editAction'));
        $categoriesController->post('/delete/{id}', array($this, 'deleteAction'));
        $categoriesController->match('/delete/{id}', array($this, 'deleteAction'))
            ->bind('categories_delete');
        $categoriesController->match('/delete/{id}/', array($this, 'deleteAction'));
        $categoriesController->get('/view/{id}', array($this, 'viewAction'))
            ->bind('categories_view');
        $categoriesController->get('/view/{id}/', array($this, 'viewAction'));
        $categoriesController->get('/index', array($this, 'indexAction'));
        $categoriesController->get('/index/', array($this, 'indexAction'))
            ->bind('categories_index');;
//        $categoriesController->get('/{page}', array($this, 'indexAction'))
//            ->value('page', 1)->bind('categories_index');
        return $categoriesController;
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
            $pagesCount = $this->_model->countCategoriesPages($pageLimit);
            $page = $this->_model->getCurrentPageNumber($page, $pagesCount);
            $categories = $this->_model->getCategoriesPage($page, $pageLimit);
        } catch (\PDOException $e) {
            $app->abort(404, $app['translator']->trans('Categories not exists'));
        }
        $this->view['paginator']
            = array('page' => $page, 'pagesCount' => $pagesCount);
        $this->view['categories'] = $categories;
        return $app['twig']->render('categories/index.twig', $this->view);
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
            $this->view['category'] = $this->_model->getCategory($id);
            $this->view['category_articles'] = $this->_model->getCategoryArticles($id);
        } catch (\PDOException $e) {
            $app->abort(404, $app['translator']->trans('Category not found'));
        }
        return $app['twig']->render('categories/view.twig', $this->view);
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

                // default values:

                $form = $app['form.factory']->createBuilder('form')
                    ->add(
                        'category_name', 'text',
                        array(
                            'constraints' => array(
                                new Assert\NotBlank(),
                                new Assert\Length(array('min' => 3))
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
                    $categoriesModel = new CategoriesModel($app);
                    $categoriesModel->saveCategory($data);

                    $app['session']->getFlashBag()->add(
                        'message', array(
                            'type' => 'success', 'content' => $app['translator']->trans('Dodałeś nową kategorię.')
                        )
                    );
                    return $app->redirect(
                        $app['url_generator']->generate('categories_index'),
                        301
                    );
                }

                $this->view['form'] = $form->createView();
            } catch (\PDOException $e) {
                $app->abort(500, $app['translator']->trans('An error occurred, please try again later'));
            }
            return $app['twig']->render('categories/add.twig', $this->view);
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

                $id = (int) $request->get('id', 0);
                $category = $this->_model->getCategory($id);

                if (count($category)) {

                    $form = $app['form.factory']->createBuilder('form', $category)
                        ->add(
                            'id', 'hidden',
                            array(
                                'data' => $id,
                                'constraints' => array(
                                    new Assert\NotBlank(),
                                    new Assert\Type(array('type' => 'digit'))
                                ),
                            )
                        )
                        ->add(
                            'category_name', 'text',
                            array(
                                'constraints' => array(
                                    new Assert\NotBlank(),
                                    new Assert\Length(array('min' => 3))
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
                        $this->_model->saveCategory($data);

                        $app['session']->getFlashBag()->add(
                            'message', array(
                                'type' => 'success', 'content' => $app['translator']->trans('Edytowałeś wpis.')
                            )
                        );
                        return $app->redirect(
                            $app['url_generator']->generate('categories_index'),
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
                        $app['url_generator']->generate('categories_index'),
                        301
                    );
                }
            } catch (\PDOException $e) {
                $app->abort(500, $app['translator']->trans('An error occurred, please try again later'));
            }
            return $app['twig']->render('categories/edit.twig', $this->view);
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

                $categoriesModel = new CategoriesModel($app);
                $id = (int) $request->get('id', 0);
                $category = $categoriesModel->getCategory($id);

                if (count($category)) {
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

                            $this->_model->removeCategory($data);

                            $app['session']->getFlashBag()->add(
                                'message', array(
                                    'type' => 'success',
                                    'content' =>
                                        'Kategoria została usunięta wraz z artykułami'
                                )
                            );
                            return $app->redirect(
                                $app['url_generator']->generate(
                                    'categories_index'
                                ), 301
                            );

                        } else {
                            return $app->redirect(
                                $app['url_generator']->generate(
                                    'categories_index'
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
                            'categories_index'
                        ), 301
                    );
                }
            } catch (\PDOException $e) {
                $app->abort(500, $app['translator']->trans('An error occurred, please try again later'));
            }
            return $app['twig']->render('categories/delete.twig', $this->view);
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