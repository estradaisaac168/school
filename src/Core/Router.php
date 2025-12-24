<?php





class Route
{

    protected static array $routes = []; //Contiene las rutas 

    public static function get(string $uri, callable|array $action)
    {
        self::add('GET', $uri, $action);
    }


    public static function post(string $uri, callable|array $action)
    {
        self::add('POST', $uri, $action);
    }

    protected static function convertToRegex(string $uri): string
    {
        $pattern = preg_replace_callback(
            '#\{([^}:]+)(?::([^}]+))?\}#',
            function ($matches) {
                // {id} o {id:\d+}
                $regex = $matches[2] ?? '[^/]+';
                return "($regex)";
            },
            $uri
        );

        return "#^{$pattern}$#";
    }


    protected static function add(string $method, string $uri, callable |array $action) : void {
        self::$routes[$method][] = [
            'uri'       => $uri,
            'regex'     => self::convertToRegex($uri),
            'action'    => $action
        ];
    }


    protected static function execute(callable|array $action, array $params): void
    {
        if (is_callable($action)) {
            call_user_func_array($action, $params);
            return;
        }

        if (is_array($action)) {
            [$class, $method] = $action;
            $controller = new $class();
            call_user_func_array([$controller, $method], $params);
        }
    }


    public static function run(): void
    {

        $method = $_SERVER['REQUEST_METHOD']; //GET | POST

        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        foreach (self::$routes[$method] ?? [] as $route) {
            if (preg_match($route['regex'], $requestUri, $matches)) {
                array_shift($matches); //quitar el match completo

                self::execute($route['action'], $matches);
                return;
            }
        }

        http_response_code(404);
        echo "404 Ruta no encontrada";
        return;
    }
}



// Route::get('/', function(){
//     echo "Este es un mensaje";
// });