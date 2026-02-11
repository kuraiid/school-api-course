<?php
class Router
{
    private array $routes = [];
    private $mysqli;
    private $controllers = [];

    private function getInputData(): array
    {
        return match ($_SERVER['REQUEST_METHOD']) {
            'GET', 'DELETE' => $_GET,
            default => json_decode(file_get_contents('php://input'), true) ?: []
        };
    }

    public function __construct($mysqli)
    {
        $this->mysqli = $mysqli;
        $this->initControllers();
    }

    private function initControllers()
    {
        require_once 'controller/UserController.php';
        require_once 'controller/CourseController.php';
        require_once 'controller/OrderController.php';
        require_once 'service/JwtService.php';
        require_once 'middleware/AuthMiddleware.php';

        $jwtService = new JwtService();

        $this->controllers = [
            'user' => new UserController($this->mysqli, $jwtService),
            'courses' => new CourseController($this->mysqli),
            'orders' => new OrderController($this->mysqli)
        ];
    }

    public function addRoute($method, $path, $controllerKey, $methodName, $middleware = 'auth')
    {
        $this->routes[$method][$path] = [
            'controller' => $this->controllers[$controllerKey],
            'method' => $methodName,
            'middleware' => $middleware
        ];
    }

    public function dispatch($method, $uri)
    {
        $path = trim(parse_url($uri, PHP_URL_PATH), '/');
        if (!str_starts_with($path, 'school-api/')) {
            http_response_code(404);
            echo json_encode(['message' => 'API only']);
            exit();
        }

        $path = substr($path, 11);
        $input = $this->getInputData();
        $route = $this->routes[$method][$path] ?? null;

        if (!$route) {
            http_response_code(404);
            echo json_encode(['message' => 'Not found']);
            exit();
        }

        if ($route['middleware'] === 'auth') {
            $auth = new AuthMiddleware($this->mysqli);
            $auth->handle(function () use ($route, $input) {
                $route['controller']->{$route['method']}($input);
            });
        } else {
            $route['controller']->{$route['method']}($input);
        }
    }
}
