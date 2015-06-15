<?php
use Symfony\Component\HttpFoundation\Response;

require_once dirname(dirname(__FILE__)) . '/vendor/autoload.php';

$app = new Silex\Application();

/* Twig */
$app->register(
    new Silex\Provider\TwigServiceProvider(), array(
        'twig.path' => __DIR__.'/../src/Views',
    )
);

/* Doctrine */
$app->register(
    new Silex\Provider\DoctrineServiceProvider(), array(
        'db.options' => array(
            'driver'    => 'pdo_mysql',
            'host'      => 'localhost',
            'dbname'    => '12_wojna',
            'user'      => '12_wojna',
            'password'  => 'X8z6b9c5w5',
            'charset'   => 'utf8',
        ),
    )
);

/* Validation */
$app->register(new Silex\Provider\ValidatorServiceProvider());

/* Forms */
$app->register(new Silex\Provider\FormServiceProvider());

/* Translation */
$app->register(
    new Silex\Provider\TranslationServiceProvider(), array(
        'translator.domains' => array(),
    )
);

/* Auth */
$app->register(
    new Silex\Provider\SecurityServiceProvider(),
    array(
        'security.firewalls' => array(
            'admin' => array(
                'pattern' => '^.*$',
                'form' => array(
                    'login_path' => 'auth_login',
                    'check_path' => 'auth_login_check',
                    'default_target_path'=> '/articles/index',
                    'username_parameter' => 'loginForm[login]',
                    'password_parameter' => 'loginForm[password]',
                ),
                'anonymous' => true,
                'logout' => array(
                    'logout_path' => 'auth_logout',
                    'target_url' => '/articles/index'
                ),
                'users' => $app->share(
                    function() use ($app)
                    {
                        return new Provider\UserProvider($app);
                    }
                ),
            ),
        ),
        'security.access_rules' => array(
            array('^/article.+$', 'IS_AUTHENTICATED_ANONYMOUSLY'),
            array('^/categories.+$', 'IS_AUTHENTICATED_ANONYMOUSLY'),
            array('^/register.+$', 'IS_AUTHENTICATED_ANONYMOUSLY'),
            array('^/auth.+$', 'IS_AUTHENTICATED_ANONYMOUSLY'),
            array('^/comments.+$', 'IS_AUTHENTICATED_ANONYMOUSLY'),
            array('^/.+$', 'ROLE_ADMIN')
        ),
        'security.role_hierarchy' => array(
            'ROLE_ADMIN' => array('ROLE_USER'),
        ),
    )
);

// Errors
$app->error(
    function (\Exception $e, $code) use ($app) {
        if ($code == 404) {
            return new Response(
                $app['twig']->render('404.twig'), 404
            );
        }
    }
);

/* Session */
$app->register(new Silex\Provider\SessionServiceProvider());

/* URLs */
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

/* Routing */
$app->mount('/', new Controller\IndexController());
$app->mount('/register', new Controller\RegistrationController());
$app->mount('/articles', new Controller\ArticlesController());
$app->mount('/categories', new Controller\CategoriesController());
$app->mount('/auth/', new Controller\AuthController());
$app->mount('/comments/', new Controller\CommentsController());


$app['debug'] = true;
$app->run();