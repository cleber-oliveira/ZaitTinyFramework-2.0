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

namespace app\core\database;

use PDO;
use PDOException;
use RuntimeException;
use app\core\utils\Config;

class DBConnection {
    private static array $instances = [];

    /**
     * Retorna uma instância PDO para a conexão informada (ou a padrão).
     * 
     * @param string|null $connectionName Nome da conexão no config.json (ex: 'mysql', 'pgsql')
     * @return PDO
     */
    public static function getConnection(?string $connectionName = null): PDO {
        $defaultName = (string)Config::get('database.default', 'mysql');
        $name = $connectionName ?? $defaultName;

        if (isset(self::$instances[$name])) {
            return self::$instances[$name];
        }

        $config = Config::get("database.connections.{$name}");
        if (empty($config) || !is_array($config)) {
            throw new RuntimeException("Configurações para a conexão de banco '{$name}' não encontradas no config.json.");
        }

        $driver   = $config['driver'] ?? 'mysql';
        $host     = $config['host'] ?? '127.0.0.1';
        $port     = (int)($config['port'] ?? ($driver === 'pgsql' ? 5432 : 3306));
        $dbname   = $config['dbname'] ?? $config['database'] ?? '';
        $user     = $config['user'] ?? $config['username'] ?? 'root';
        $password = (string)($config['password'] ?? '');
        $charset  = $config['charset'] ?? ($driver === 'pgsql' ? 'utf8' : 'utf8mb4');

        if ($driver === 'mysql') {
            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
        } elseif ($driver === 'pgsql') {
            $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};options='--client_encoding={$charset}'";
        } else {
            throw new RuntimeException("Driver de banco '{$driver}' não suportado.");
        }

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, $user, $password, $options);
            self::$instances[$name] = $pdo;
            return $pdo;
        } catch (PDOException $e) {
            throw new RuntimeException("Falha na conexão com o banco de dados [{$name}]: " . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * Define manualmente uma instância PDO (ideal para testes unitários com SQLite em memória).
     */
    public static function setConnection(string $name, PDO $pdo): void {
        self::$instances[$name] = $pdo;
    }

    /**
     * Fecha todas as conexões ativas.
     */
    public static function closeAll(): void {
        self::$instances = [];
    }
}
