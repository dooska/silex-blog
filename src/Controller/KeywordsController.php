<?php
/**
 * Keyword controller
 *
 * PHP version 5
 *
 * @keyword Controller
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
use Model\KeywordsModel;
use Model\ArticlesModel;
use Form\KeywordAddForm;
use Form\ArticleKeywordForm;

/**
 * Class KeywordsController
 *
 * @keyword Controller
 *
 * @package  Controller
 * @author   Dominika Wojna
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @version  Release: <package_version>
 * @link     wierzba.wzks.uj.edu.pl/~12_wojna
 * @uses Silex\Application
 * @uses Silex\ControllerProviderInterface
 * @uses Symfony\Component\HttpFoundation\Request
 * @uses Symfony\Component\Validator\Constraints
 * @uses Model\KeywordModel
 */
class KeywordsController implements ControllerProviderInterface
{
    /**
     * Keyword Model object.
     *
     * @var $_model
     * @access protected
     */
    protected $_model;
    protected $_article;

    /**
     *
     * @param Application $app application object
     *
     * @access public
     * @return \Silex\ControllerCollection
     */
    public function connect(Application $app)
    {
        $this->_model = new KeywordsModel($app);
        $this->_article = new ArticlesModel($app);
        $keywordsController = $app['controllers_factory'];
        $keywordsController->post('/add', array($this, 'addAction'));
        $keywordsController->match('/add', array($this, 'addAction'))
            ->bind('keywords_add');
        $keywordsController->match('/add/', array($this, 'addAction'));
        $keywordsController->post('/edit/{id}', array($this, 'editAction'));
        $keywordsController->match('/edit/{id}', array($this, 'editAction'))
            ->bind('keywords_edit');
        $keywordsController->match('/edit/{id}/', array($this, 'editAction'));
        $keywordsController->post('/delete/{id}', array($this, 'deleteAction'));
        $keywordsController->match('/delete/{id}', array($this, 'deleteAction'))
            ->bind('keywords_delete');
        $keywordsController->match('/delete/{id}/', array($this, 'deleteAction'));
        $keywordsController->get('/view/{id}', array($this, 'viewAction'))
            ->bind('keywords_view');
        $keywordsController->get('/view/{id}/', array($this, 'viewAction'));
        $keywordsController->get('/index', array($this, 'indexAction'));
        $keywordsController->get('/index/', array($this, 'indexAction'))
            ->bind('keywords_index');
        $keywordsController->match('/connect/{id}', array($this, 'connectAction'))
            ->bind('connect_keyword');
//        $keywordsController->get('/{page}', array($this, 'indexAction'))
//            ->value('page', 1)->bind('keywords_index');
        return $keywordsController;
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
        $page = (int)$request->get('page', 1);
        $pagesCount = $this->_model->countKeywordsPages($pageLimit);
        $page = $this->_model->getCurrentPageNumber($page, $pagesCount);
        $keywords = $this->_model->getKeywordsPage($page, $pageLimit);
        $this->view['paginator']
            = array('page' => $page, 'pagesCount' => $pagesCount);
        $this->view['keywords'] = $keywords;
        return $app['twig']->render('keywords/index.twig', $this->view);
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
        $this->view['keyword'] = $this->_model->getKeyword($id);
        $this->view['keyword_articles'] = $this->_model->getKeywordArticles($id);
        return $app['twig']->render('keywords/view.twig', $this->view);
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

            // default values:

            $form = $app['form.factory']->createBuilder(
                new KeywordAddForm(), array())
                ->getForm();

            $form->handleRequest($request);

            if ($form->isValid()) {
                $data = $form->getData();
                try {
                    $check = $this->_model->checkIfKeywordExists($data);
                    if(!$check) {
                        $this->_model->saveKeyword($data);
                        $app['session']->getFlashBag()->add(
                            'message', array(
                                'type' => 'success', 'content' => $app['translator']->trans('Dodałeś nowe słowo kluczowe.')
                            )
                        );
                        return $app->redirect(
                            $app['url_generator']->generate('keywords_index'),
                            301
                        );
                    } else {
                        $app['session']->getFlashBag()->add(
                            'message', array(
                                'type' => 'danger',
                                'content' => $app['translator']->trans('Słowo kluczowe istnieje.')
                            )
                        );
                        return $app->redirect(
                            $app['url_generator']->generate('keywords_index'),
                            301
                        );
                    }
                } catch (\Exception $e) {
                    $errors[] = 'Coś poszło niezgodnie z planem';
                }
            }

            $this->view['form'] = $form->createView();

            return $app['twig']->render('keywords/add.twig', $this->view);
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



            $keywordsModel = new KeywordsModel($app);
            $id = (int)$request->get('id', 0);
            $keyword = $keywordsModel->getKeyword($id);

            if (count($keyword)) {

                $form = $app['form.factory']->createBuilder('form', $keyword)
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
                        'word', 'text',
                        array(
                            'constraints' => array(
                                new Assert\NotBlank(),
                                new Assert\Length(array('min' => 3))
                            )
                        )
                    )
                    ->getForm();
                $form->handleRequest($request);

                if ($form->isValid()) {
                    $data = $form->getData();
                    $keywordsModel = new KeywordsModel($app);
                    $keywordsModel->saveKeyword($data);
                    $app['session']->getFlashBag()->add(
                        'message', array(
                            'type' => 'success', 'content' => $app['translator']->trans('Edytowałeś wpis.')
                        )
                    );
                    return $app->redirect(
                        $app['url_generator']->generate('keywords_index'),
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
                    $app['url_generator']->generate('keywords_index'),
                    301
                );
            }

            return $app['twig']->render('keywords/edit.twig', $this->view);
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


            $keywordsModel = new KeywordsModel($app);
            $id = (int)$request->get('id', 0);
            $keyword = $keywordsModel->getKeyword($id);

            if (count($keyword)) {
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
                            $this->_model->removeKeyword($data);

                            $app['session']->getFlashBag()->add(
                                'message', array(
                                    'type' => 'success',
                                    'content' =>
                                        'Słowo kluczowe zostało usunięte'
                                )
                            );
                            return $app->redirect(
                                $app['url_generator']->generate(
                                    'keywords_index'
                                ), 301
                            );
                        } catch (\Exception $e) {
                            $errors[] = 'Coś poszło niezgodnie z planem';
                        }
                    } else {
                        return $app->redirect(
                            $app['url_generator']->generate(
                                'keywords_index'
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
                        'keywords_index'
                    ), 301
                );
            }
            return $app['twig']->render('keywords/delete.twig', $this->view);
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

    public function connectAction(Application $app, Request $request)
    {
        if ($app['security']->isGranted('ROLE_ADMIN')) {


            $article_id = (int)$request->get('id', 0);
            $checkArticle = $this->_article->checkArticleId($article_id);

            if ($checkArticle) {
                $keywords = $this->_model->getKeywordsArray();
                $form = $app['form.factory']->createBuilder(
                    new ArticleKeywordForm(), array(
                    'keywords' => $keywords,
                    'article_id' => $article_id))
                    ->getForm();

                $form->handleRequest($request);

                if ($form->isValid()) {
                    $data = $form->getData();

                    $checkTag = $this->_model->checkIfKeywordForArticleExist($data);
                    if (!$checkTag) {
                        $this->_model->connectKeywordWithArticle($data);

                        $app['session']->getFlashBag()->add(
                            'message', array(
                                'type' => 'success',
                                'content' => 'Słowo kluczowe zostało dodane'
                            )
                        );
                        return $app->redirect(
                            $app['url_generator']->generate(
                                'articles_view', array('id' => $article_id)
                            ), 301
                        );
                    } else {
                        $app['session']->getFlashBag()->add(
                            'message', array(
                                'type' => 'danger',
                                'content' => 'Słowo kluczowe jest już przypisane'
                            )
                        );
                        return $app->redirect(
                            $app['url_generator']->generate(
                                'articles_index'
                            ), 301
                        );
                    }
                }
                return $app['twig']->render('keywords/connect.twig', array(
                        'form' => $form->createView()
                    )
                );

            }  else {
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


















