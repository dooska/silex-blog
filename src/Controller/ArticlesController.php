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
        $articlesController->get('/index/', array($this, 'indexAction'));
        $articlesController->get('/{page}', array($this, 'indexAction'))
            ->value('page', 1)->bind('articles_index');
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
        $pageLimit = 3;
        $page = (int) $request->get('page', 1);
        $articlesModel = new ArticlesModel($app);
        $pagesCount = $articlesModel->countArticlesPages($pageLimit);
        $page = $articlesModel->getCurrentPageNumber($page, $pagesCount);
        $articles = $articlesModel->getArticlesPage($page, $pageLimit);
        $this->view['paginator']
            = array('page' => $page, 'pagesCount' => $pagesCount);
        $this->view['articles'] = $articles;
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
        $id = (int)$request->get('id', null);
        $articlesModel = new ArticlesModel($app);
        $this->view['article'] = $articlesModel->getArticle($id);
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
        // default values:
        $data = array(
            'title' => 'Title',
            'content' => 'Content',
        );

        $form = $app['form.factory']->createBuilder('form', $data)
            ->add(
                'title', 'text',
                array(
                    'constraints' => array(
                        new Assert\NotBlank(),
                        new Assert\Length(array('min' => 5))
                    )
                )
            )
            ->add(
                'content', 'text',
                array(
                    'constraints' => array(
                        new Assert\NotBlank(),
                        new Assert\Length(array('min' => 5))
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
                    'type' => 'success', 'content' => $app['translator']->trans('Dodałeś nowy wpis.')
                )
            );
            return $app->redirect(
                $app['url_generator']->generate('articles_index'),
                301
            );
        }

        $this->view['form'] = $form->createView();

        return $app['twig']->render('articles/add.twig', $this->view);
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

        $articlesModel = new ArticlesModel($app);
        $id = (int) $request->get('id', 0);
        $article = $articlesModel->getArticle($id);

        if (count($article)) {

            $form = $app['form.factory']->createBuilder('form', $article)
                ->add(
                    'id', 'hidden',
                    array(
                        'data' => $id,
                        'constraints' => array(
                            new Assert\NotBlank(),
                            new Assert\Type(array('type' => 'digit'))
                        )
                    )
                )
                ->add(
                    'title', 'text',
                    array(
                        'constraints' => array(
                            new Assert\NotBlank(),
                            new Assert\Length(array('min' => 5))
                        )
                    )
                )
                ->add(
                    'content', 'text',
                    array(
                        'constraints' => array(
                            new Assert\NotBlank(),
                            new Assert\Length(array('min' => 5))
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

        return $app['twig']->render('articles/edit.twig', $this->view);
    }

    public function deleteAction(Application $app, Request $request)
    {
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

                    try {
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
                    } catch (\Exception $e) {
                        $errors[] = 'Coś poszło niezgodnie z planem';
                    }
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
        return $app['twig']->render('articles/delete.twig', $this->view);

    }
}