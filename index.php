<?php
use Symfony\Component\Debug\ErrorHandler;


require_once('bootstrap.php');

define('APP_ROOT', __DIR__);
define('APP_PATH', __DIR__ . '/app');
define('CONFIG_FILE', APP_PATH . '/config/config.yml');
define('PASSWD_DIR', APP_PATH . '/config/secure');
define('PASSWD_FILE', PASSWD_DIR . '/passwd');
define('VENDOR_PATH', __DIR__ . '/vendor');
define('IS_HTTPS', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'));

define('BASE_URL', (IS_HTTPS ? 'https' : 'http') .
    '://' . $_SERVER['SERVER_NAME'] . (!IS_HTTPS && $_SERVER['SERVER_PORT'] !== 80 ? ':' .$_SERVER['SERVER_PORT'] : '') .
    str_replace('/index.php', '', $_SERVER['SCRIPT_NAME'])
);
define('WEB_URL', BASE_URL . '/web');

$app = new Silex\Application();
$app['template_url'] = WEB_URL;

if(is_readable(CONFIG_FILE)) {
    $app->register(new DerAlex\Silex\YamlConfigServiceProvider(CONFIG_FILE));
    $app['debug'] = ($app['config']['debug']);
    Symfony\Component\Debug\ExceptionHandler::register(!$app['debug']);
    if(in_array($app['config']['timezone'], DateTimeZone::listIdentifiers())) date_default_timezone_set($app['config']['timezone']);
}
$app->register(new Silex\Provider\TwigServiceProvider(), array(
        'twig.path' => APP_ROOT.'/views',
        'twig.options' => array('debug' => $app['debug'])
    ));
$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

$app->get('/', function() use($app) {
        if(!is_readable(CONFIG_FILE)) {
            throw new \Syonix\LogViewer\Exceptions\ConfigFileMissingException();
        }
        return $app->redirect($app['url_generator']->generate('logs'));
    })
    ->bind("home");

$app->get('/login', function(\Symfony\Component\HttpFoundation\Request $request) use($app) {
        return $app['twig']->render('login.html.twig', array(
                'create_success' => false,
                'error'         => $app['security.last_error']($request),
            ));
    })
    ->bind("login");

$app->mount('/api', include 'api.php');

$app->get('/logs{path}', function() use($app) { return $app['twig']->render('log.html.twig', array(
    'reverse_order' => $app['config']['reverse_line_order']
)); })
    ->bind("logs")
    ->value('path', FALSE)
    ->assert("path", ".*");

ErrorHandler::register();

$app->error(function (\Syonix\LogViewer\Exceptions\ConfigFileMissingException $e, $code) use($app) {
    return $app['twig']->render('error/config_file_missing.html.twig');
});

$app->error(function (\Syonix\LogViewer\Exceptions\NoLogsConfiguredException $e, $code) use($app) {
    return $app['twig']->render('error/no_log_files.html.twig');
});

$app->error(function (\Symfony\Component\HttpKernel\Exception\HttpException $e) use($app) {
    if ($app['debug']) {
        return;
    }

    switch($e->getStatusCode()) {
        case 404:
            $viewer = new Syonix\LogViewer\LogViewer($app['config']['logs']);

            return $app['twig']->render('error/log_file_not_found.html.twig', array(
                'clients' => $viewer->getClients(),
                'current_client_slug' => null,
                'current_log_slug' => null,
                'error' => $e
            ));
        default:
            try {
                $viewer = new Syonix\LogViewer\LogViewer($app['config']['logs']);
                $clients = $viewer->getClients();
            } catch(\Exception $e) {
                $clients = array();
            }
            return $app['twig']->render('error/error.html.twig', array(
                'clients' => $clients,
                'clientSlug' => null,
                'logSlug' => null,
                'message' => 'Something went wrong!',
                'icon' => 'bug',
                'error' => $e
            ));
    }
});

$app->error(function (\Exception $e, $code) use($app) {
    if ($app['debug']) {
        return;
    }

    switch($code) {
        default:
            try {
                $viewer = new Syonix\LogViewer\LogViewer($app['config']['logs']);
                $clients = $viewer->getClients();
            } catch(\Exception $e) {
                $clients = array();
            }
            return $app['twig']->render('error/error.html.twig', array(
                'clients' => $clients,
                'clientSlug' => null,
                'logSlug' => null,
                'message' => 'Something went wrong!',
                'icon' => 'bug',
                'error' => $e
            ));
    }
});

$app->run();
