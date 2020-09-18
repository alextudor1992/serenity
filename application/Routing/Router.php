<?php

namespace Serenity\Routing;

use JsonException;
use Serenity\Modules\Module;
use function \
{
	count,
	strpos,
	explode,
	headers_sent,
	http_response_code,
};


/**
 * Class Router
 * @package Serenity\Routing
 */
class Router
{
	/** @var Request */
    protected Request $request;

    protected array $staticResolvers = [];

	/** @var array */
    protected array $dynamicResolvers = [];

    /**
     * Router constructor.
     * @throws JsonException
     */
	public function __construct()
	{
		$this->request = new Request($_SERVER['REQUEST_URI']);
	}

    /**
     * @return Request
     */
	public function getRequest(): Request
	{
		return $this->request;
	}

    /**
     * Adds a request resolver for a given pattern.
     *
     * @param Module $module
     * @param Resolver $resolver
     * @return $this
     */
	public function addResolver(Module $module, Resolver $resolver)
	{
		$service = $this->request->getService();
        $pattern = $resolver->getFullPath();
        $moduleId = $module->getModuleId();
        $method = $resolver->getMethod();

		if (!$service || $service === $moduleId)
		{
		    $listener = ['module' => $module, 'resolver' => $resolver];

			/**
			 * Token "$"	- The identifier for a dynamic parameter
			 * Token "..."	- The identifier for the variadic parameter
			 */
			if (strpos($pattern, '$') !== false || strpos($pattern, '...') !== false) {
				$this->dynamicResolvers[$method][$pattern] = $listener;
			}
			else {
                $this->staticResolvers[$method][$pattern] = $listener;
            }
		}
		return $this;
	}

	/**
	 * The routing system maps each segment of the URI path
	 * to a predefined variable name, if the route is dynamic,
	 * otherwise the segment name must match the segment value.
	 * Static routes are handled first and are extremely fast.
	 *
	 * @example Static route:		/api/customers/
	 *          Endpoint request:	/api/customers/
	 *          Will result in:		['api' => 'api', 'customers' => 'customers']
	 *
	 * @example Dynamic route:		/api/customers/$id
	 *          Endpoint request:	/api/customers/5
	 *          Will result in:		['api' => 'api', 'customers' => 'customers', 'id' => 5]
	 *
	 * @param Request|null $request If null, the main request will be processed.
	 */
	public function resolve(Request $request=null): void
	{
		if (!$request) {
            $request = $this->request;
        }

		$this->resolveStaticRoutes($request);
		$this->resolveDynamicRoutes($request);
		$this->dispatchResponse($request, new Response(null,404));
	}

    /**
     * @param Request $request
     */
	protected function resolveStaticRoutes(Request $request): void
    {
        $initialPath = $request->getRequestPath();
        $staticResolvers = $this->staticResolvers;
        $requestMethod = $request->getMethod();

        if (isset($staticResolvers[$requestMethod][$initialPath]))
        {
            $request->setPath([]);
            $moduleResolver = $staticResolvers[$requestMethod][$initialPath];

            /** @var Module $module */
            $module = $moduleResolver['module'];

            if ($module->isActive())
            {
                /** @var Resolver $resolver */
                $resolver = $moduleResolver['resolver'];
                $this->tryResolver($request, $resolver);
            }
        }
    }

    /**
     * @param Request $request
     */
    protected function resolveDynamicRoutes(Request $request): void
    {
        $initialPath = $request->getRequestPath();
        $pathParameters	= $this->parseURI($initialPath);
        $pathDepth = count($pathParameters);
        $requestMethod = $request->getMethod();
        $resolvers = $this->dynamicResolvers;

        if (!empty($resolvers[$requestMethod]))
        {
            /**
             * @var string $pattern
             * @var array $pluginResolver
             */
            foreach ($resolvers[$requestMethod] as $pattern => $pluginResolver)
            {
                $template = $this->parseURI($pattern);
                $patternDepth = $count = count($template);

                if (strpos($pattern, '...') !== false)
                {
                    /**
                     * The resolver for pattern "/books/psychology/..." won't be called for request URI: "/books"
                     */
                    if ($patternDepth < $pathDepth)
                    {
                        $count = $pathDepth;
                    }
                    else {
                        continue;
                    }
                }

                $mapping = [];

                for ($i = 0; $i < $count; $i++) {
                    $key = $template[$i] ?? $i;
                    $value = $pathParameters[$i] ?? null;

                    if ($key[0] !== '$') {
                        if ((int)$key !== $key && $key !== $value) {
                            continue 2;
                        }
                    }
                    else {
                        $key = ltrim($key, '$');
                    }
                    $mapping[$key] = $value;
                }

                $request->setPath($mapping);

                /** @var Module $module */
                $module = $pluginResolver['module'];

                if ($module->isActive())
                {
                    /** @var Resolver $resolver */
                    $resolver = $pluginResolver['resolver'];
                    $this->tryResolver($request, $resolver);
                }
            }
        }
    }

	/**
	 * Splits a pattern in segments, ignoring the slashes
	 * from the beginning and ending of the pattern.
	 *
	 * @param string $uri
	 * @return array
	 */
	protected function parseURI(string $uri) : array
	{
		$params = [];

		foreach (explode('/', $uri) as $pathParam)
		{
			if (!empty($pathParam))
            {
                $params[] = $pathParam;
            }
		}
		return $params;
	}

	/**
	 * Transmits the response to the client
	 * @param Request $request
	 * @param Response $response
	 */
	protected function dispatchResponse(Request $request, Response $response): void
	{
		$data = $response->getBody();

		if (!headers_sent())
		{
			$code = $response->getHTTPCode();

			if ($code === 200 && !(bool)$data)
			{
				$code = 204;
			}
			$this->dispatchHeaders($response->getHeaders(), $code ?: 200);
		}
		exit($data ?: '');
	}

    /**
     * @param Request $request
     * @param Redirect $redirect
     */
	protected function redirect(Request $request, Redirect $redirect): void
	{
		if (!headers_sent())
		{
			$this->dispatchHeaders($redirect->getHeaders(), $redirect->getRedirectType());
		}
		exit();
	}

    /**
     * @param array $headers
     * @param int $statusCode
     */
	protected function dispatchHeaders(array $headers, int $statusCode): void
    {
        foreach ($headers as $key => $value)
        {
            header("{$key}: {$value}");
        }
        http_response_code($statusCode);
    }

    /**
     * Iterates through all resolvers to handle a given request.
     * @param Request $request
     * @param Resolver $resolver
     */
	protected function tryResolver(Request $request, Resolver $resolver): void
	{
        $result = $resolver->resolve($request);

        if (!$result) {
            return;
        }
        if ($result instanceof Response) {
		    $this->dispatchResponse($request, $result);
		}
        if ($result instanceof Request) {
            if ($result !== $request) {
                $this->resolve($result);
            }
        }
        elseif ($result instanceof Redirect) {
            $this->redirect($request, $result);
		}
	}
}
