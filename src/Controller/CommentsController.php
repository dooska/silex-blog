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
     * @var $model
     * @access protected
     */
    protected $model;

    /**
     * Article Model object.
     *
     * @var $model
     * @access protected
     */
    protected $articles;

    /**
     * User Model object.
     *
     * @var $model
     * @access protected
     */
    protected $user;


    /**
     *
     * @param Application $app application object
     *
     * @access public
     * @return \Silex\ControllerCollection
     */
    public function connect(Application $app)
    {
        $this->model = new CommentsModel($app);
        $this->articles = new ArticlesModel($app);
        $this->user = new UsersModel($app);
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
        $commentsController
            ->match('/delete/{id}/', array($this, 'deleteAction'));
        $commentsController->get('/view/{id}', array($this, 'viewAction'))
            ->bind('comments_view');
        $commentsController->get('/view/{id}/', array($this, 'viewAction'));
        return $commentsController;
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
        if ($app['security']->isGranted('ROLE_USER')) {
            try {
                $article_id = (int)$request->get('article_id');

                $check = $this->articles->checkArticleId($article_id);

                if ($check) {
                    if ($this->user->_isLoggedIn($app)) {
                        $user_id = $this->user->getIdCurrentUser($app);
                    } else {
                        $user_id = 0;
                    }
                    $data = array(
                        'published_date' => date('Y-m-d H:m'),
                        'article_id' => $article_id,
                        'user_id' => (int)$user_id,
                    );
                    $form = $app['form.factory']
                        ->createBuilder(new CommentForm(), $data)
                        ->getForm();

                    $form->handleRequest($request);

                    if ($form->isValid()) {
                        $data = $form->getData();
                        $model = $this->model->addComment($data);

                        $app['session']->getFlashBag()
                            ->add(
                                'message',
                                array(
                                    'type' => 'success',
                                    'content' =>
                                        $app['translator']->trans(
                                            'comment_added'
                                        )
                                )
                            );
                        return $app->redirect(
                            $app['url_generator']
                                ->generate(
                                    'articles_view',
                                    array(
                                        'id' => $data['article_id']
                                    )
                                ),
                            301
                        );

                    }
                    return $app['twig']->render(
                        'comments/add.twig',
                        array(
                            'form' => $form
                                ->createView(),
                            'article_id' => $article_id
                        )
                    );
                } else {
                    $app['session']->getFlashBag()
                        ->add(
                            'message',
                            array(
                                'type' => 'danger',
                                'content' => $app['translator']
                                    ->trans('comment_not_found')
                            )
                        );
                    return $app->redirect(
                        $app['url_generator']->generate(
                            'articles_index'
                        ),
                        301
                    );
                }
            } catch (\PDOException $e) {
                $app->abort(500, $app['translator']
                    ->trans('An error occurred, please try again later'));
            }
        } else {
            $app['session']->getFlashBag()
                ->add(
                    'message',
                    array(
                        'type' => 'danger',
                        'content' => $app['translator']
                            ->trans('log_in')
                    )
                );
            return $app->redirect(
                $app['url_generator']
                    ->generate('articles_index'),
                301
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
        if ($app['security']->isGranted('ROLE_USER')) {
            try {
                $id = (int)$request->get('id', 0);

                $check = $this->model->checkCommentId($id);

                if ($check) {
                    $comment = $this->model->getComment($id);
                    $comment['published_date'] = date('Y-m-d H:m:s');

                    if (count($comment)) {
                        $form = $app['form.factory']
                            ->createBuilder(new CommentForm(), $comment)
                            ->getForm();

                        $form->handleRequest($request);

                        if ($form->isValid()) {
                            $data = $form->getData();

                            $model = $this->model->editComment($data);

                            $app['session']
                                ->getFlashBag()
                                ->add(
                                    'message',
                                    array(
                                        'type' => 'success',
                                        'content' => 'comment_edited'
                                    )
                                );
                            return $app->redirect(
                                $app['url_generator']->generate(
                                    'articles_view',
                                    array('id' => $comment['article_id'])
                                ),
                                301
                            );

                        }
                        return $app['twig']->render(
                            'comments/edit.twig',
                            array(
                                'form' => $form->createView()
                            )
                        );
                    } else {
                        $app['session']->getFlashBag()
                            ->add(
                                'message',
                                array(
                                    'type' => 'danger',
                                    'content' => 'comment_not_found'
                                )
                            );
                        return $app->redirect(
                            $app['url_generator']
                                ->generate(
                                    '/comments/add'
                                ),
                            301
                        );
                    }
                } else {
                    $app['session']->getFlashBag()
                        ->add(
                            'message',
                            array(
                                'type' => 'danger',
                                'content' => 'comment_not_found'
                            )
                        );
                    return $app->redirect(
                        $app['url_generator']->generate(
                            'articles_index'
                        ),
                        301
                    );

                }
            } catch (\PDOException $e) {
                $app->abort(500, $app['translator']->trans('error_occured'));
            }
        } else {
            $app['session']->getFlashBag()->add(
                'message',
                array(
                    'type' => 'danger',
                    'content' => $app['translator']->trans('no_rights')
                )
            );
            return $app->redirect(
                $app['url_generator']->generate('articles_index'),
                301
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
        if ($app['security']->isGranted('ROLE_USER')) {
            try {
                $id = (int)$request->get('id', 0);

                $check = $this->model->checkCommentId($id);

                if ($check) {
                    $comment = $this->model->getComment($id);

                    $data = array();

                    if (count($comment)) {
                        $form = $app['form.factory']
                            ->createBuilder(
                                'form',
                                $data
                            )
                            ->add(
                                'comment_id',
                                'hidden',
                                array(
                                    'data' => $id,
                                )
                            )
                            ->add('Tak', 'submit')
                            ->add('Nie', 'submit')
                            ->getForm();

                        $form->handleRequest($request);

                        if ($form->isValid()) {
                            if ($form->get('Tak')
                                ->isClicked()) {
                                $data = $form->getData();
                                $model = $this->model
                                    ->deleteComment($data);

                                $app['session']->getFlashBag()->add(
                                    'message',
                                    array(
                                        'type' => 'success',
                                        'content' => $app['translator']
                                            ->trans('comment_deleted')
                                    )
                                );
                                return $app->redirect(
                                    $app['url_generator']->generate(
                                        'articles_view',
                                        array('id' => $comment['article_id'])
                                    ),
                                    301
                                );

                            } else {
                                return $app->redirect(
                                    $app['url_generator']->generate(
                                        'articles_index'
                                    ),
                                    301
                                );
                            }
                        }
                        return $app['twig']->render(
                            'comments/delete.twig',
                            array(
                                'form' => $form->createView()
                            )
                        );
                    } else {
                        $app['session']->getFlashBag()->add(
                            'message',
                            array(
                                'type' => 'danger',
                                'content' => $app['translator']
                                    ->trans('comment_not_found')
                            )
                        );
                        return $app->redirect(
                            $app['url_generator']
                                ->generate(
                                    'articles_index'
                                ),
                            301
                        );
                    }
                } else {
                    $app['session']->getFlashBag()->add(
                        'message',
                        array(
                            'type' => 'danger',
                            'content' => $app['translator']
                                ->trans('comment_not_found')
                        )
                    );
                    return $app->redirect(
                        $app['url_generator']->generate(
                            'articles_index'
                        ),
                        301
                    );
                }
            } catch (\PDOException $e) {
                $app->abort(500, $app['translator']->trans('error_occured'));
            }
        } else {
            $app['session']->getFlashBag()
                ->add(
                    'message',
                    array(
                        'type' => 'danger', 'content' => $app['translator']
                        ->trans('no_rights')
                    )
                );
            return $app->redirect(
                $app['url_generator']->generate('articles_index'),
                301
            );
        }
    }
}
