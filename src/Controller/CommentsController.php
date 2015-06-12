<?php
/**
 * Comment controller
 *
 * PHP version 5
 *
 * @comment Controller
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
use Model\CommentsModel;
use Model\UsersModel;
use Model\ArticlesModel;
use Form\CommentForm;

/**
 * Class CommentController
 *
 * @comment Controller
 * @package  Controller
 * @author   Dominika Wojna
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @version  Release: <package_version>
 * @link     wierzba.wzks.uj.edu.pl/~12_wojna
 * @uses Silex\Application
 * @uses Silex\ControllerProviderInterface
 * @uses Symfony\Component\HttpFoundation\Request
 * @uses Symfony\Component\Validator\Constraints
 * @uses Model\CommentModel
 */
class CommentsController implements ControllerProviderInterface
{
    /**
     * Comment Model object.
     *
     * @var $_model
     * @access protected
     */
    protected $_model;

    /**
     * Article Model object.
     *
     * @var $_model
     * @access protected
     */
    protected $_articles;

    /**
     * User Model object.
     *
     * @var $_model
     * @access protected
     */
    protected $_user;


    /**
     *
     * @param Application $app application object
     *
     * @access public
     * @return \Silex\ControllerCollection
     */
    public function connect(Application $app)
    {
        $this->_model = new CommentsModel($app);
        $this->_articles = new ArticlesModel($app);
        $this->_user = new UsersModel($app);
        $commentsController = $app['controllers_factory'];
        $commentsController->post('/add', array($this, 'addAction'));
        $commentsController->match('/add', array($this, 'addAction'))
            ->bind('comments_add');
        $commentsController->match('/add/', array($this, 'addAction'));
        $commentsController->post('/edit/{id}', array($this, 'editAction'));
        $commentsController->match('/edit/{id}', array($this, 'editAction'))
            ->bind('comments_edit');
        $commentsController->match('/edit/{id}/', array($this, 'editAction'));
        $commentsController->post('/delete/{id}', array($this, 'deleteAction'));
        $commentsController->match('/delete/{id}', array($this, 'deleteAction'))
            ->bind('comments_delete');
        $commentsController->match('/delete/{id}/', array($this, 'deleteAction'));
        $commentsController->get('/view/{id}', array($this, 'viewAction'))
            ->bind('comments_view');
        $commentsController->get('/view/{id}/', array($this, 'viewAction'));
        $commentsController->get('/index', array($this, 'indexAction'));
        $commentsController->get('/index/', array($this, 'indexAction'))
            ->bind('comments_index');;
//        $commentsController->get('/{page}', array($this, 'indexAction'))
//            ->value('page', 1)->bind('comments_index');
        return $commentsController;
    }

    /**
     * View all comments for post
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return \Symfony\Component\HttpFoundation\RedirectResponse Redirect.
     * @return mixed Generates page.
     */
    public function indexAction(Application $app, Request $request)
    {
        $id = (int)$request->get('article_id', 0);

        $check = $this->_articles->checkArticleId($id);

        if ($check) {

            $comments = $this->_model->getCommentsList($id);

            $_isLogged = $this->_user->_isLoggedIn($app);
            if ($_isLogged) {
                $access = $this->_user->getIdCurrentUser($app);
            } else {
                $access = 0;
            }

            return $app['twig']->render(
                'comments/index.twig', array(
                    'comments' => $comments, 'article_id' => $id, 'access' => $access
                )
            );
        } else {
            $app['session']->getFlashBag()->add(
                'message', array(
                    'type' => 'danger',
                    'content' => 'Nie znaleziono komentarza'
                )
            );
            return $app->redirect(
                $app['url_generator']->generate(
                    'articles_index'
                ), 301
            );
        }
    }

    public function addAction(Application $app, Request $request)
    {
        $article_id = (int)$request->get('article_id');

        $check = $this->_articles->checkArticleId($article_id);
//        var_dump($check);
//        var_dump($article_id);
//        die();

        if ($check) {

            if ($this->_user->_isLoggedIn($app)) {
                $user_id = $this->_user->getIdCurrentUser($app);
            } else {
                $user_id = 0;
            }
            $data = array(
                'published_date' => date('Y-m-d'),
                'article_id' => $article_id,
                'user_id' => $user_id,
            );
            $form = $app['form.factory']->createBuilder(new CommentForm(), $data)
                ->getForm();

            $form->handleRequest($request);

            if ($form->isValid()) {
                $data = $form->getData();
                try {
                    $model = $this->_model->addComment($data);

                    $app['session']->getFlashBag()->add(
                        'message', array(
                            'type' => 'success',
                            'content' => 'Komentarz został dodany'
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
            }
            return $app['twig']->render(
                'comments/add.twig', array(
                    'form' => $form->createView(),
                    'article_id' => $article_id
                )
            );
        } else {
            $app['session']->getFlashBag()->add(
                'message', array(
                    'type' => 'danger',
                    'content' => 'Nie znaleziono komentarza'
                )
            );
            return $app->redirect(
                $app['url_generator']->generate(
                    'articles_index'
                ), 301
            );
        }
    }

    /**
     * Edit comment
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return \Symfony\Component\HttpFoundation\RedirectResponse Redirect.
     * @return mixed Generates page.
     */
    public function editAction(Application $app, Request $request)
    {

        $id = (int)$request->get('id', 0);

        $check = $this->_model->checkCommentId($id);

        if ($check) {

//            $idCurrentUser = $this->_user->getIdCurrentUser($app);
            $comment = $this->_model->getComment($id);

            if (count($comment)) {

                $data = array(
                    'comment_id' => $id,
                    'comment_content' => $comment['content'],
                    'published_date' => date('Y-m-d'),
                    'article_id' => $comment['article_id'],
                    'user_id' => $comment['iduser'],
//                    'current_user' => $idCurrentUser,
                );

                $form = $app['form.factory']->createBuilder('form', $data)
                    ->add(
                        'content', 'textarea', array(
                        'required' => false
                    ), array(
                            'constraints' => array(
                                new Assert\NotBlank(),
                                new Assert\Length(
                                    array(
                                        'min' => 5,
                                        'minMessage' =>
                                            'Minimalna ilość znaków to 5',
                                    )
                                ),
                                new Assert\Type(
                                    array(
                                        'type' => 'string',
                                        'message' => 'Tekst nie poprawny.',
                                    )
                                )
                            )
                        )
                    )
                    ->getForm();

                $form->handleRequest($request);

                if ($form->isValid()) {
                    $data = $form->getData();

                    try {
                        $model = $this->_model->editComment($data);

                        $app['session']->getFlashBag()->add(
                            'message', array(
                                'type' => 'success',
                                'content' => 'Komanetarz został zmieniony'
                            )
                        );
                        return $app->redirect(
                            $app['url_generator']->generate(
                                'articles_view', array('id' => $data['article_id'])
                            ), 301
                        );
                    } catch (Exception $e) {
                        $errors[] = 'Coś poszło niezgodnie z planem';
                    }
                }
                return $app['twig']->render(
                    'comments/edit.twig', array(
                        'form' => $form->createView()
                    )
                );
            } else {
                $app['session']->getFlashBag()->add(
                    'message', array(
                        'type' => 'danger',
                        'content' => 'Nie znaleziono komentarza'
                    )
                );
                return $app->redirect(
                    $app['url_generator']->generate(
                        '/comments/add'
                    ), 301
                );
            }
        } else {
            $app['session']->getFlashBag()->add(
                'message', array(
                    'type' => 'danger',
                    'content' => 'Nie znaleziono komentarza'
                )
            );
            return $app->redirect(
                $app['url_generator']->generate(
                    'articles_index'
                ), 301
            );

        }
    }

    /**
     * Delete comment
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return \Symfony\Component\HttpFoundation\RedirectResponse Redirect.
     * @return mixed Generates page.
     */
    public function deleteAction(Application $app, Request $request)
    {
        $id = (int)$request->get('id', 0);

        $check = $this->_model->checkCommentId($id);

        if ($check) {

            $comment = $this->_model->getComment($id);

            $data = array();

            if (count($comment)) {
                $form = $app['form.factory']->createBuilder('form', $data)
                    ->add(
                        'comment_id', 'hidden', array(
                            'data' => $id,
                        )
                    )
                    ->add('Yes', 'submit')
                    ->add('No', 'submit')
                    ->getForm();

                $form->handleRequest($request);

                if ($form->isValid()) {
                    if ($form->get('Yes')->isClicked()) {
                        $data = $form->getData();
                        try {
                            $model = $this->_model->deleteComment($data);

                            $app['session']->getFlashBag()->add(
                                'message', array(
                                    'type' => 'success',
                                    'content' => 'Komantarz został usunięty'
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
                return $app['twig']->render(
                    'comments/delete.twig', array(
                        'form' => $form->createView()
                    )
                );
            } else {
                $app['session']->getFlashBag()->add(
                    'message', array(
                        'type' => 'danger',
                        'content' => 'Nie znaleziono komentarza'
                    )
                );
                return $app->redirect(
                    $app['url_generator']->generate(
                        'articles_index'
                    ), 301
                );
            }
        } else {
            $app['session']->getFlashBag()->add(
                'message', array(
                    'type' => 'danger',
                    'content' => 'Nie znaleziono komentarza'
                )
            );
            return $app->redirect(
                $app['url_generator']->generate(
                    'articles_index'
                ), 301
            );

        }
    }

}