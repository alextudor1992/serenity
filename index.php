<?php

namespace Serenity;

use Serenity\Modules\Core\ServiceAPI;

require __DIR__ . '/vendor/autoload.php';

const MODULES =
[
    'service_api' => ServiceAPI::class
];

(static function()
{
	if (!apcu_fetch('init'))
	{
		apcu_clear_cache();
		clearstatcache(true);

		$appName = getenv('APP_NAME');
		$appRuntime	= getenv('APP_RUNTIME');
		$serviceProviderUri	= getenv('SERVICE_PROVIDER_URL');

		App::config()->setMultiple(
		[
			'init'                  => true,
			'instanceId'            => hash('sha256', random_bytes(32)),
			'appName'			    => $appName,
			'appRuntime'		    => $appRuntime,
			'serviceProviderUrl'    => $serviceProviderUri, // Default value: http://127.0.0.1:8500/v1
            'mysql'                 => [],
            'redis'                 => [],
            'object_storage'        => [],

		], true);
	}

	App::init(MODULES);

})();


