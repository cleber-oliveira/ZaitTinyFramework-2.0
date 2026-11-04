<?php
declare(strict_types=1);

/**
 * ============================================================================
 * ZTFramework 2.0
 * ============================================================================
 * 
 * @package   ZTFramework
 * @version   2.0.0
 * @author    Professor Drº Cleber S. Oliveira
 * @author    Professora Drª Marcia Aparecida Silva Bissaco
 * @copyright (c) 2026 ZTFramework
 * ============================================================================
 */

namespace app\core\utils;

use RuntimeException;
use Throwable;
use app\core\database\DBConnection;
use app\core\database\DBQuery;
use app\core\database\DatabaseProvisioner;

/**
 * ZTFramework - Router 2.0
 * 
 * Gerenciador central de rotas com suporte a:
 * - Parâmetros dinâmicos na URI (ex: /arquivo/{hash})
 * - Verbos HTTP (GET, POST, PUT, DELETE, OPTIONS, HEAD)
 * - publicZone com verificação estrita de MimeTypes
 * - ACL em memória via $_SESSION['user']
 * - Resposta obrigatória em JSON para requisições AJAX/jQuery
 */
class Router {
    private array $routes = [];
    private string $routesFilePath;
    private string $basePath;

    public function __construct(?string $routesFilePath = null, ?string $basePath = null) {
        $this->basePath = $basePath ?? realpath(__DIR__ . '/../../../') ?: dirname(__DIR__, 3);
        $this->routesFilePath = $routesFilePath ?? $this->basePath . '/app/etc/routes.json';

        $this->loadRoutes();
    }

    public function loadRoutes(): void {
        if (!is_file($this->routesFilePath)) {
            throw new RuntimeException("Arquivo de rotas não encontrado em: {$this->routesFilePath}");
        }

        $content = (string)file_get_contents($this->routesFilePath);
        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            throw new RuntimeException("Erro ao decodificar routes.json: " . json_last_error_msg());
        }

        $this->routes = $decoded;
    }

    public function getRoutes(): array {
        return $this->routes;
    }

    public function isAjaxRequest(): bool {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            return true;
        }

        if (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
            return true;
        }

        return false;
    }

    /**
     * Localiza a rota correspondente (exata ou via parâmetro dinâmico {param}).
     */
    private function matchRoute(string $uri): ?array {
        if (isset($this->routes[$uri])) {
            return ['config' => $this->routes[$uri], 'params' => []];
        }

        foreach ($this->routes as $routePattern => $methods) {
            if (!str_contains($routePattern, '{')) {
                continue;
            }

            $regexPattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $routePattern);
            $regex = '#^' . $regexPattern . '$#';

            if (preg_match($regex, $uri, $matches)) {
                $params = [];
                foreach ($matches as $k => $v) {
                    if (is_string($k)) {
                        $params[$k] = $v;
                    }
                }
                return ['config' => $methods, 'params' => $params];
            }
        }

        return null;
    }

    public function dispatch(?string $uri = null, ?string $method = null): mixed {
        if (session_status() === PHP_SESSION_NONE && php_sapi_name() !== 'cli') {
            session_start();
        }

        $normalizedUri = $this->normalizeUri($uri);
        $httpMethod = strtolower($method ?? $_SERVER['REQUEST_METHOD'] ?? 'get');
        if ($httpMethod === 'head') {
            $httpMethod = 'get';
        }

        // 1. Arquivos da publicZone
        $publicPrefix = Config::get('publicZone.prefix', '/public');
        if (str_starts_with($normalizedUri, $publicPrefix . '/') || $normalizedUri === $publicPrefix) {
            return $this->serveStaticFile($normalizedUri);
        }

        // 2. Verificação de Saúde e Auto-Provisionamento do Banco de Dados
        $dbStatus = DatabaseProvisioner::checkAndProvision();
        if (!$dbStatus['ok']) {
            return $this->handleDatabaseError($dbStatus);
        }

        // 3. Busca pela rota
        $match = $this->matchRoute($normalizedUri);
        if (!$match) {
            return $this->responseError(404, 'ERR_NOT_FOUND', "Rota '{$normalizedUri}' não encontrada.");
        }

        $routeMethods = $match['config'];
        $routeParams  = $match['params'];

        foreach ($routeParams as $pk => $pv) {
            $_GET[$pk] = $pv;
        }

        // 3. Verbo HTTP
        if (!isset($routeMethods[$httpMethod])) {
            if ($httpMethod === 'options') {
                return $this->handlePreflight($routeMethods);
            }
            return $this->responseError(405, 'ERR_METHOD_NOT_ALLOWED', "Método HTTP '{$httpMethod}' não suportado para esta rota.");
        }

        $routeConfig = $routeMethods[$httpMethod];

        // 4. CORS
        $this->handleCors($routeConfig['cors'] ?? null);

        // 5. ACL de Sessão
        $authCheck = $this->checkAccessControl($routeConfig);
        if ($authCheck['allowed'] === false) {
            return $this->responseError(
                $authCheck['statusCode'],
                $authCheck['errorCode'],
                $authCheck['message'],
                $routeConfig['responseType'] ?? 'json'
            );
        }

        // 6. Execução
        return $this->executeRoute($routeConfig, $routeParams);
    }

    public function checkAccessControl(array $routeConfig): array {
        $sessionConfig = $routeConfig['session'] ?? [];
        $isAuthRequired = (bool)($sessionConfig['required'] ?? false);
        $userSession = $_SESSION['user'] ?? null;

        if ($isAuthRequired) {
            if (empty($userSession) || !is_array($userSession)) {
                return [
                    'allowed'    => false,
                    'statusCode' => 401,
                    'errorCode'  => 'ERR_UNAUTHORIZED',
                    'message'    => 'Autenticação necessária para acessar este recurso.'
                ];
            }

            $requiredKeys = $sessionConfig['keys'] ?? ['idUsuario'];
            foreach ($requiredKeys as $key) {
                if (!isset($userSession[$key])) {
                    return [
                        'allowed'    => false,
                        'statusCode' => 401,
                        'errorCode'  => 'ERR_INVALID_SESSION',
                        'message'    => "Sessão inválida: chave obrigatória '{$key}' não encontrada."
                    ];
                }
            }
        }

        if (!empty($userSession) && is_array($userSession)) {
            $userGroups = (array)($userSession['grupos'] ?? []);
            $userPermissions = (array)($userSession['permissoes'] ?? []);
            $userId = $userSession['idUsuario'] ?? null;

            $isSuperAdmin = in_array('admin', $userGroups, true);

            if (!$isSuperAdmin) {
                $allowedUsers = $routeConfig['users'] ?? [];
                if (!empty($allowedUsers) && !in_array($userId, $allowedUsers, true)) {
                    return [
                        'allowed'    => false,
                        'statusCode' => 403,
                        'errorCode'  => 'ERR_FORBIDDEN_USER',
                        'message'    => 'Seu usuário não possui permissão para este recurso.'
                    ];
                }

                $requiredGroups = $routeConfig['groups'] ?? [];
                if (!empty($requiredGroups)) {
                    $hasGroup = !empty(array_intersect($requiredGroups, $userGroups));
                    if (!$hasGroup) {
                        return [
                            'allowed'    => false,
                            'statusCode' => 403,
                            'errorCode'  => 'ERR_FORBIDDEN_GROUP',
                            'message'    => 'Você não pertence ao grupo autorizado para acessar esta funcionalidade.'
                        ];
                    }
                }

                $requiredPermissions = $routeConfig['permissions'] ?? [];
                if (!empty($requiredPermissions)) {
                    $hasPerm = !empty(array_intersect($requiredPermissions, $userPermissions));
                    if (!$hasPerm) {
                        return [
                            'allowed'    => false,
                            'statusCode' => 403,
                            'errorCode'  => 'ERR_FORBIDDEN_PERMISSION',
                            'message'    => 'Você não possui as permissões necessárias para esta operação.'
                        ];
                    }
                }
            }
        }

        return ['allowed' => true];
    }

    private function executeRoute(array $routeConfig, array $params = []): mixed {
        $responseType = $routeConfig['responseType'] ?? 'json';

        try {
            if (isset($routeConfig['path'])) {
                $filePath = $this->basePath . '/' . ltrim($routeConfig['path'], '/');

                if (!is_file($filePath)) {
                    throw new RuntimeException("Arquivo não encontrado: {$routeConfig['path']}");
                }

                if ($responseType === 'html' && php_sapi_name() !== 'cli' && !headers_sent()) {
                    header('Content-Type: text/html; charset=utf-8');
                }

                $request = $this->getRequestPayload();
                $session = $_SESSION['user'] ?? null;
                $route = $routeConfig;

                return require $filePath;
            }

            if (isset($routeConfig['controller'])) {
                $controllerClass = "app\\controllers\\" . $routeConfig['controller'];
                $action = $routeConfig['action'] ?? 'index';

                if (!class_exists($controllerClass)) {
                    throw new RuntimeException("Classe Controller '{$controllerClass}' não encontrada.");
                }

                $instance = new $controllerClass();
                if (!method_exists($instance, $action)) {
                    throw new RuntimeException("Método '{$action}' não encontrado em {$controllerClass}.");
                }

                return $instance->$action();
            }

            if (isset($routeConfig['query'])) {
                return $this->executeDeclarativeQuery($routeConfig['query']);
            }

            if (isset($routeConfig['dbquery'])) {
                return $this->executeDeclarativeDBQuery($routeConfig['dbquery']);
            }

            throw new RuntimeException("Nenhum manipulador válido definido para esta rota.");

        } catch (Throwable $e) {
            return $this->responseError(500, 'ERR_SERVER_ERROR', $e->getMessage(), $responseType);
        }
    }

    private function executeDeclarativeQuery(array $queryConfig): mixed {
        $sql = (string)($queryConfig['sql'] ?? '');
        $paramNames = (array)($queryConfig['params'] ?? []);

        $requestData = $this->getRequestPayload();
        $bindValues = [];

        foreach ($paramNames as $name) {
            $bindValues[] = $requestData[$name] ?? null;
        }

        $db = DBConnection::getConnection();
        $stmt = $db->prepare($sql);
        $stmt->execute($bindValues);
        $data = $stmt->fetchAll();

        return JsonResponse::success($data, 'Consulta executada com sucesso.');
    }

    private function executeDeclarativeDBQuery(array $dbqConfig): mixed {
        $table = (string)($dbqConfig['tableName'] ?? '');
        $fields = $dbqConfig['fieldsName'] ?? '*';
        $fieldsStr = is_array($fields) ? implode(', ', $fields) : (string)$fields;

        $dbQuery = new DBQuery($table, $fieldsStr);

        $where = $dbqConfig['where'] ?? null;
        if (is_array($where) && !empty($where)) {
            foreach ($where as $col => $val) {
                $dbQuery->addCondition('AND', (string)$col, '=', $val);
            }
        }

        $data = $dbQuery->select();

        return JsonResponse::success($data, 'Consulta DBQuery executada com sucesso.');
    }

    private function serveStaticFile(string $uri): mixed {
        $cleanPath = ltrim(parse_url($uri, PHP_URL_PATH) ?? '', '/');
        $fullPath = realpath($this->basePath . '/' . $cleanPath);
        $publicBase = realpath($this->basePath . '/public');

        if (!$fullPath || !$publicBase || !str_starts_with($fullPath, $publicBase) || !is_file($fullPath)) {
            return $this->responseError(404, 'ERR_STATIC_NOT_FOUND', 'Arquivo estático não encontrado.');
        }

        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $allowedMimes = Config::get('publicZone.allowedMimes', []);

        $defaultMimes = [
            'css'   => 'text/css; charset=utf-8',
            'js'    => 'application/javascript; charset=utf-8',
            'json'  => 'application/json; charset=utf-8',
            'png'   => 'image/png',
            'jpg'   => 'image/jpeg',
            'jpeg'  => 'image/jpeg',
            'gif'   => 'image/gif',
            'svg'   => 'image/svg+xml',
            'ico'   => 'image/x-icon',
            'pdf'   => 'application/pdf',
            'txt'   => 'text/plain; charset=utf-8',
            'mp3'   => 'audio/mpeg',
            'wav'   => 'audio/wav',
            'woff'  => 'font/woff',
            'woff2' => 'font/woff2'
        ];

        $contentType = $allowedMimes[$ext] ?? $defaultMimes[$ext] ?? 'application/octet-stream';

        if (php_sapi_name() !== 'cli' && !headers_sent()) {
            $cacheSeconds = (int)Config::get('publicZone.cacheSeconds', 86400);
            header("Content-Type: {$contentType}");
            header("Cache-Control: public, max-age={$cacheSeconds}");
        }

        return readfile($fullPath);
    }

    public function checkDatabase(): array {
        return DatabaseProvisioner::checkAndProvision();
    }

    private function handleDatabaseError(array $dbStatus): mixed {
        $isAjax = $this->isAjaxRequest();

        if ($isAjax) {
            return JsonResponse::error(
                500,
                $dbStatus['error'] ?? 'ERR_DATABASE_ERROR',
                $dbStatus['message'] ?? 'Falha na conexão com o banco de dados.',
                $dbStatus['details'] ?? null,
                false
            );
        }

        if (php_sapi_name() !== 'cli' && !headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=utf-8');
        }

        $errorDetails = $dbStatus;
        $viewPath = $this->basePath . '/app/views/errors/db_error.php';
        if (is_file($viewPath)) {
            return require $viewPath;
        }

        echo "<h1>500 - Erro de Banco de Dados</h1><p>" . htmlspecialchars($dbStatus['message'] ?? '', ENT_QUOTES, 'UTF-8') . "</p>";
        return false;
    }

    private function responseError(int $statusCode, string $errorCode, string $message, string $responseType = 'json'): mixed {
        $isAjax = $this->isAjaxRequest();

        if (!$isAjax && $responseType === 'html') {
            $views = [
                401 => 'errors/401.php',
                403 => 'errors/403.php',
                404 => 'errors/404.php',
                500 => 'errors/500.php'
            ];

            $viewRelative = $views[$statusCode] ?? 'errors/404.php';
            $viewPath = $this->basePath . '/app/views/' . $viewRelative;

            if (is_file($viewPath)) {
                if (php_sapi_name() !== 'cli' && !headers_sent()) {
                    http_response_code($statusCode);
                    header('Content-Type: text/html; charset=utf-8');
                }
                return require $viewPath;
            }
        }

        return JsonResponse::error($statusCode, $errorCode, $message, null, false);
    }

    private function handleCors(?array $corsConfig): void {
        $globalCors = Config::get('cors', []);
        $enabled = (bool)($corsConfig['enable'] ?? $globalCors['default_enable'] ?? false);

        if (!$enabled || php_sapi_name() === 'cli' || headers_sent()) {
            return;
        }

        $origins = $corsConfig['origins'] ?? $globalCors['allowed_origins'] ?? ['*'];
        $originHeader = is_array($origins) ? implode(', ', $origins) : (string)$origins;

        $methods = $globalCors['allowed_methods'] ?? ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'];
        $headers = $globalCors['allowed_headers'] ?? ['Content-Type', 'Authorization', 'X-Requested-With', 'Accept'];

        header("Access-Control-Allow-Origin: {$originHeader}");
        header("Access-Control-Allow-Methods: " . implode(', ', $methods));
        header("Access-Control-Allow-Headers: " . implode(', ', $headers));
    }

    private function handlePreflight(array $routeMethods): mixed {
        $this->handleCors(['enable' => true]);
        if (php_sapi_name() !== 'cli' && !headers_sent()) {
            http_response_code(204);
        }
        return true;
    }

    private function normalizeUri(?string $uri): string {
        $rawUri = $uri ?? parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');

        if ($scriptDir !== '/' && $scriptDir !== '\\' && str_starts_with($rawUri, $scriptDir)) {
            $rawUri = substr($rawUri, strlen($scriptDir));
        }

        $clean = '/' . trim($rawUri, '/');
        return ($clean === '//' || $clean === '/index.php') ? '/' : $clean;
    }

    private function getRequestPayload(): array {
        $data = array_merge($_GET, $_POST);
        $raw = file_get_contents('php://input');
        if (!empty($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $data = array_merge($data, $decoded);
            }
        }
        return $data;
    }
}
