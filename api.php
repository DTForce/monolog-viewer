<?php
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use League\Flysystem\Adapter\Local;


$api = $app['controllers_factory'];

class ApiController
{

	public function config(Silex\Application $app) {
		return $app->json(array(
			'debug' => $app['config']['debug'],
			'timezone' => $app['config']['timezone'],
			'date_format' => $app['config']['date_format'],
			'display_logger' => $app['config']['display_logger'],
			'default_limit' => $app['config']['default_limit'],
			'reverse_line_order' => $app['config']['reverse_line_order'],
		));
	}

	public function logs(Silex\Application $app, Request $request) {
		$viewer = new Syonix\LogViewer\LogViewer($app['config']['logs']);
		$clients = $viewer->getClients();
		$returnLogs = (bool) $request->query->get('logs', false);
		$return = [];
		foreach ($clients as $client) {
			$element = array(
				'name' => $client->getName(),
				'slug' => $client->getSlug(),
				'url' => BASE_URL.'/api/logs/'.$client->getSlug()
			);
			if ($returnLogs) {
				foreach ($client->getLogs() as  $log) {
					$element['logs'][] = array(
						'name' => $log->getName(),
						'slug' => $log->getSlug(),
						'url' => BASE_URL . '/api/logs/' . $client->getSlug() . '/' . $log->getSlug()
					);
				}
			}
			$return[] = $element;
		}
		$response = array(
			'clients' => $return
		);

		return $app->json($response);
	}

	public function logsClient(Silex\Application $app, $clientSlug) {
		$viewer = new Syonix\LogViewer\LogViewer($app['config']['logs']);
		$client = $viewer->getClient($clientSlug);
		if(null === $client) {
			$error = array('message' => 'The client was not found.');
			return $app->json($error, 404);
		}

		$logs = $client->getLogs();
		$return = [];
		foreach($logs as $log) {
			$return [] = array(
				'name' => $log->getName(),
				'slug' => $log->getSlug(),
				'url' => BASE_URL.'/api/logs/'.$client->getSlug().'/'.$log->getSlug()
			);
		}
		$response = array(
			'name' => $client->getName(),
			'slug' => $client->getSlug(),
			'logs' => $return
		);

		return $app->json($response);
	}

	public function log(Silex\Application $app, Request $request, $clientSlug, $logSlug) {
		$defaultLimit = (intval($app['config']['default_limit']) > 0) ? intval($app['config']['default_limit']) : 100;

		$limit = intval($request->query->get('limit', $defaultLimit));
		$offset = intval($request->query->get('offset', 0));

		list($filterLogger, $filterLevel, $filterText) = $this->parseFilterParams($request);

		$viewer = new Syonix\LogViewer\LogViewer($app['config']['logs']);
		$client = $viewer->getClient($clientSlug);
		if(null === $client) {
			$error = array('message' => 'The client was not found.');
			return $app->json($error, 404);
		}

		$logDefinition = $client->getLog($logSlug);
		if(null === $logDefinition) {
			$error = array('message' => 'The log file was not found.');
			return $app->json($error, 404);
		}

		$cache = new \Syonix\LogViewer\LogFileFactory();
		$log = $cache->createLogFile($logDefinition);
		$log->setFilter($filterLogger, $filterLevel, $filterText);
		$log->setLimit($limit);
		$log->setOffset($offset);

		if ($app['config']['reverse_line_order']) {
			$log->reverseLines();
		}

		$logUrl = BASE_URL.'/api/logs/'.$client->getSlug().'/'.$logDefinition->getSlug();
		$totalLines = $log->countLines();

		$prevPageUrl = $offset > 0 ? ($offset-$limit < 0 ? $logUrl.'?limit='.$limit.'&offset=0' : $logUrl.'?limit='.$limit.'&offset='.($offset-$limit)) : null;
		$nextPageUrl = $offset+$limit < $totalLines ? $logUrl.'?limit='.$limit.'&offset='.($offset+$limit) : null;

		$prevPageUrl = $this->addFilterParamsToUrl($filterLogger, $filterLevel, $filterText, $prevPageUrl);
		$nextPageUrl = $this->addFilterParamsToUrl($filterLogger, $filterLevel, $filterText, $nextPageUrl);

		$response = array(
			'name' => $log->getName(),
			'client' => array(
				'name' => $client->getName(),
				'slug' => $client->getSlug()
			),
			'lines' => $log->getLines(),
			'total_lines' => $totalLines,
			'offset' => $offset,
			'limit' => $limit,
			'loggers' => $log->getLoggers(),
			'prev_page_url' => $prevPageUrl,
			'next_page_url' => $nextPageUrl
		);

		return $app->json($response);
	}

    /**
     * @param Request $request
     *
     * @return array
     */
    private function parseFilterParams(Request $request)
    {
        $filterLogger = $request->query->get('logger');
        if ($filterLogger === '') {
            $filterLogger = null;
        }

        $filterLevel = intval($request->query->get('level', 0));
        if ($filterLevel <= 0) {
            $filterLevel = null;
        }

        $filterText = $request->query->get('text');
        if ($filterText === '') {
            $filterText = null;
            return [$filterLogger, $filterLevel, $filterText];
        }
        return [$filterLogger, $filterLevel, $filterText];
    }

    private function addFilterParamsToUrl($filterLogger, $filterLevel, $filterText, $url)
    {
        if($filterLogger !== null) {
            $url .= '&logger=' . $filterLogger;
        }

        if($filterLevel !== null) {
            $url .= '&level=' . $filterLevel;
        }

        if($filterText !== null) {
            $url .= '&text=' . $filterText;
        }

        return $url;
    }

}

$api->get('/config', ApiController::class . '::config');

$api->get('/logs', ApiController::class . '::logs');

$api->get('/cache/clear', ApiController::class . '::clearCache');

$api->get('/logs/{clientSlug}', ApiController::class . '::logsClient');

$api->get('/logs/{clientSlug}/{logSlug}', ApiController::class . '::log');

return $api;
