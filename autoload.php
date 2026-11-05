<?php
declare(strict_types=1);

/**
 * ZTFramework - Autoloader Inteligente e Recursivo
 * 
 * 1. Integra o autoloader oficial do Composer (vendor/autoload.php).
 * 2. Suporta resolução direta PSR-4 (app\ -> app/).
 * 3. Fallback Recursivo: Percorre automaticamente todas as pastas e subpastas de 'app/'
 *    para localizar qualquer classe, ignorando diferenças de maiúsculas/minúsculas no Linux.
 */

// 1. Carrega Composer se disponível
if (is_file(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// 2. Registra o Autoloader Recursivo Nativo do ZTFramework
spl_autoload_register(static function (string $class): void {
    static $classMap = null;
    $baseDir = __DIR__ . '/app';

    // A. Tenta resolução direta PSR-4 (alta performance)
    $prefix = 'app\\';
    $len = strlen($prefix);

    if (strncmp($prefix, $class, $len) === 0) {
        $relativeClass = substr($class, $len);
        $directFile = $baseDir . '/' . str_replace('\\', '/', $relativeClass) . '.php';

        if (is_file($directFile)) {
            require $directFile;
            return;
        }
    }

    // B. Resolução Recursiva: Mapeia todas as pastas e subpastas de app/
    if ($classMap === null) {
        $classMap = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($baseDir, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $filenameWithoutExt = pathinfo($file->getFilename(), PATHINFO_FILENAME);
                // Mapeia tanto o nome simples quanto em minúsculas
                $classMap[$filenameWithoutExt] = $file->getPathname();
                $classMap[strtolower($filenameWithoutExt)] = $file->getPathname();
            }
        }
    }

    // Extrai o nome simples da classe (sem namespace)
    $parts = explode('\\', $class);
    $className = end($parts);
    $classNameLower = strtolower($className);

    if (isset($classMap[$className])) {
        require $classMap[$className];
        return;
    }

    if (isset($classMap[$classNameLower])) {
        require $classMap[$classNameLower];
        return;
    }
});

// 3. Funções Helper Globais de Roteamento e RootPath
if (!function_exists('url')) {
    function url(string $path = ''): string {
        return \app\core\utils\Url::to($path);
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string {
        return \app\core\utils\Url::asset($path);
    }
}

if (!function_exists('rootPath')) {
    function rootPath(): string {
        return \app\core\utils\Url::rootPath();
    }
}
