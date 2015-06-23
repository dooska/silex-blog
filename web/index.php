<?php
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\Loader\YamlFileLoader;

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
            array('^/articles.+$', 'IS_AUTHENTICATED_ANONYMOUSLY'),
            array('^/categories.+$', 'IS_AUTHENTICATED_ANONYMOUSLY'),
            array('^/register.+$', 'IS_AUTHENTICATED_ANONYMOUSLY'),
            array('^/auth.+$', 'IS_AUTHENTICATED_ANONYMOUSLY'),
            array('^/comments.+$', 'IS_AUTHENTICATED_ANONYMOUSLY'),
            array('^/keywords.+$', 'IS_AUTHENTICATED_ANONYMOUSLY'),
            array('^/users.+$', 'ROLE_USER'),
            array('^/.+$', 'ROLE_ADMIN')
        ),

        'security.role_hierarchy' => array(
            'ROLE_ADMIN' => array('ROLE_USER'),
        ),
    )
);

// Errors
$app->error(
    function (
        \Exception $e, $code
    ) use ($app) {

        if ($e instanceof Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            $code = (string)$e->getStatusCode();
        }

        if ($app['debug']) {
            return;
        }

        // 404.html, or 40x.html, or 4xx.html, or error.html
        $templates = array(
            'errors/'.$code.'.twig',
            'errors/'.substr($code, 0, 2).'x.twig',
            'errors/'.substr($code, 0, 1).'xx.twig',
            'errors/default.twig',
        );

        return new Response(
            $app['twig']->resolveTemplate($templates)->render(
                array('code' => $code)
            ),
            $code
        );

    }
);

/* Session */
$app->register(new Silex\Provider\SessionServiceProvider());

/* URLs */
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

/* Translation */
$app->register(
    new Silex\Provider\TranslationServiceProvider(), array(
        'locale' => 'pl',
        'locale_fallbacks' => array('pl'),
    )
);

$app['translator'] = $app->share($app->extend('translator', function($translator, $app) {
    $translator->addLoader('yaml', new YamlFileLoader());
    $translator->addResource('yaml', dirname(dirname(__FILE__)) . '/config/locales/pl.yml', 'pl');
    return $translator;
}));

/* Routing */
$app->mount('/', new Controller\IndexController());
$app->mount('/register', new Controller\RegistrationController());
$app->mount('/articles', new Controller\ArticlesController());
$app->mount('/categories', new Controller\CategoriesController());
$app->mount('/auth/', new Controller\AuthController());
$app->mount('/comments/', new Controller\CommentsController());
$app->mount('/keywords/', new Controller\KeywordsController());
$app->mount('/users/', new Controller\UsersController());


$app['debug'] = true;
$app->run();