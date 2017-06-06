<?php namespace F2m2\Apidocs\Commands;

use F2m2\Apidocs\Generators\LaravelGenerator;
use F2m2\Apidocs\Generators\AbstractGenerator;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Support\Facades\Route as RouteFacade;
use phpDocumentor\Reflection\DocBlock;
use ReflectionClass;

class ApiDocsGeneratorCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'apidocs:generate';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Generates API Documentation.';

	/**
	 * The console command description.
	 *
	 * @var DocsGenerator
	 */

	protected $generator;

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct(ApiDocsGenerator $generator)
	{
		parent::__construct();

		$this->generator = $generator;
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		if (!is_null($this->argument('prefix'))) {
			// Command line argument takes 1st precedence.
			$prefix = $this->argument('prefix');
		}
		else {
			// Check for an environment variable, so you don't have to type
			// the prefix in each time. Otherwise, ask them.
			if (!empty(getenv('APIDOCS_PREFIX'))) {
				$prefix = getenv('APIDOCS_PREFIX');
			} else {
				$prefix = $this->ask('What is the API Prefix?  i.e. "api/v1"');
			}
		}
		$this->info('Generating ' . $prefix . ' API Documentation.');

        $allowedRoutes = [];
        $routePrefix = $prefix.'/*';
        $middleware = '';

        $this->setUserToBeImpersonated(1);

		$generator = new LaravelGenerator();
        $generator->prepareMiddleware(true);

        $parsedRoutes = $this->processLaravelRoutes($generator, $allowedRoutes, $routePrefix, $middleware);
        $parsedRoutes = collect($parsedRoutes)->groupBy('resource')->sort(function ($a, $b) {
            return strcmp($a->first()['resource'], $b->first()['resource']);
        });
        //dd($parsedRoutes);

	   // generate the docs
	   $this->generator->make($prefix, $parsedRoutes);

	   $dot_prefix = str_replace('/', '.', $prefix);

       $this->info('API Docs have been generated!');
       $this->info('');
       $this->info('Add the following Route to "app/routes.php" > ');

		// All done!
        $this->info(sprintf(
            "\n %s" . PHP_EOL,
            "Route::get('docs', function(){
            	return View::make('docs." . $dot_prefix . ".index');
            });"
        ));
	}

    /**
     * @param $actAs
     */
    private function setUserToBeImpersonated($actAs)
    {
        if (! empty($actAs)) {
            if (version_compare($this->laravel->version(), '5.2.0', '<')) {
                $userModel = config('auth.model');
                $user = $userModel::find((int) $actAs);
                $this->laravel['auth']->setUser($user);
            } else {
                $userModel = config('auth.providers.users.model');
                $user = $userModel::find((int) $actAs);
                $this->laravel['auth']->guard()->setUser($user);
            }
        }
    }

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			array('prefix', InputArgument::OPTIONAL, 'Api Prefix (i.e. "api/v1"'),
		);
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	// protected function getOptions()
	// {
	// 	return array(
	// 		array('example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null),
	// 	);
	// }

    /**
     * @param AbstractGenerator  $generator
     * @param $allowedRoutes
     * @param $routePrefix
     *
     * @return array
     */
    private function processLaravelRoutes(AbstractGenerator $generator, $allowedRoutes, $routePrefix, $middleware)
    {
        $withResponse = true;
        $routes = $this->getRoutes();
        $bindings = $this->getBindings();
        $parsedRoutes = [];
        foreach ($routes as $route) {
            if (in_array($route->getName(), $allowedRoutes) || str_is($routePrefix, $generator->getUri($route)) || in_array($middleware, $route->middleware())) {
                if ($this->isValidRoute($route) && $this->isRouteVisibleForDocumentation($route->getAction()['uses'])) {
                    $parsedRoutes[] = $generator->processRoute($route, $bindings, [], $withResponse);
                    $this->info('Processed route: ['.implode(',', $generator->getMethods($route)).'] '.$generator->getUri($route));
                } else {
                    $this->warn('Skipping route: ['.implode(',', $generator->getMethods($route)).'] '.$generator->getUri($route));
                }
            }
        }

        return $parsedRoutes;
    }

    /**
     * @return mixed
     */
    private function getRoutes()
    {
        return RouteFacade::getRoutes();
    }

    /**
     * @return array
     */
    private function getBindings()
    {
        $bindings = '';
        if (empty($bindings)) {
            return [];
        }
        $bindings = explode('|', $bindings);
        $resultBindings = [];
        foreach ($bindings as $binding) {
            list($name, $id) = explode(',', $binding);
            $resultBindings[$name] = $id;
        }

        return $resultBindings;
    }

    /**
     * @param $route
     *
     * @return bool
     */
    private function isValidRoute($route)
    {
        return ! is_callable($route->getAction()['uses']) && ! is_null($route->getAction()['uses']);
    }

    /**
     * @param $route
     *
     * @return bool
     */
    private function isRouteVisibleForDocumentation($route)
    {
        list($class, $method) = explode('@', $route);
        $reflection = new ReflectionClass($class);
        $comment = $reflection->getMethod($method)->getDocComment();
        if ($comment) {
            $phpdoc = new DocBlock($comment);

            return collect($phpdoc->getTags())
                ->filter(function ($tag) use ($route) {
                    return $tag->getName() === 'hideFromAPIDocumentation';
                })
                ->isEmpty();
        }

        return true;
    }

}
