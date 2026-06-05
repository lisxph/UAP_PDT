<?php
class Router {
    protected $basePath = '';
    protected $appBasePath = '';

    public function __construct($basePath = ''){
        $this->basePath = rtrim($basePath, '/');
        $this->appBasePath = $this->basePath;
        if(substr($this->appBasePath, -7) === '/public'){
            $this->appBasePath = substr($this->appBasePath, 0, -7);
        }
    }

    public function dispatch(){
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        // remove base path if app is hosted in subfolder
        if($this->basePath && strpos($uri, $this->basePath) === 0){
            $uri = substr($uri, strlen($this->basePath));
        } elseif($this->appBasePath && strpos($uri, $this->appBasePath) === 0){
            $uri = substr($uri, strlen($this->appBasePath));
        }
        $uri = trim($uri, '/');

        if($uri === 'index.php'){
            $uri = '';
        }

        $segments = $uri === '' ? [] : explode('/', $uri);

        if($this->basePath && substr($this->basePath, -7) === '/public' && isset($segments[1]) && substr($segments[1], -4) === '.php'){
            $appRoot = substr($this->basePath, 0, -7);
            $query = $_SERVER['QUERY_STRING'] ?? '';
            header('Location: ' . $appRoot . '/' . $uri . ($query ? '?' . $query : ''));
            exit;
        }

        $controllerName = !empty($segments[0]) ? ucfirst($segments[0]) . 'Controller' : 'HomeController';
        $action = $segments[1] ?? 'index';
        $action = preg_replace('/\.php$/', '', $action);

        if($controllerName === 'AuthController' && $action === 'loginregister'){
            $action = 'index';
        }

        $params = array_slice($segments, 2);

        $controllerFile = __DIR__ . '/../app/controllers/' . $controllerName . '.php';

        if(!file_exists($controllerFile)){
            header('HTTP/1.1 404 Not Found');
            echo "Controller not found: $controllerName";
            exit;
        }

        require_once $controllerFile;

        if(!class_exists($controllerName)){
            header('HTTP/1.1 500 Internal Server Error');
            echo "Controller class missing: $controllerName";
            exit;
        }

        $controller = new $controllerName($GLOBALS['conn'] ?? null);

        if(!method_exists($controller, $action)){
            header('HTTP/1.1 404 Not Found');
            echo "Action not found: $action";
            exit;
        }

        call_user_func_array([$controller, $action], $params);
    }
}
