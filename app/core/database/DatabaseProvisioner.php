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
use Throwable;
use app\core\utils\Config;

class DatabaseProvisioner {
    private static ?array $cachedStatus = null;

    /**
     * Tabelas essenciais que devem existir no banco de dados.
     */
    private const REQUIRED_TABLES = [
        'usuarios',
        'grupos',
        'permissoes',
        'usuariosGrupos',
        'gruposPermissoes',
        'arquivosUpload'
    ];

    /**
     * Verifica e, se necessário, provisiona a base de dados e tabelas.
     * 
     * @param string|null $connectionName Nome da conexão no config.json (padrão: 'mysql')
     * @param bool $forceRecheck Se true, ignora o cache em memória e refaz a verificação.
     * @return array Resumo do status [ok => bool, action => string, message => string, details => array]
     */
    public static function checkAndProvision(?string $connectionName = null, bool $forceRecheck = false): array {
        $defaultName = (string)Config::get('database.default', 'mysql');
        $name = $connectionName ?? $defaultName;

        if (!$forceRecheck && self::$cachedStatus !== null && isset(self::$cachedStatus[$name])) {
            return self::$cachedStatus[$name];
        }

        $config = Config::get("database.connections.{$name}");
        if (empty($config) || !is_array($config)) {
            $result = [
                'ok'      => false,
                'error'   => 'ERR_DATABASE_CONFIG_MISSING',
                'message' => "Configuração da conexão '{$name}' não encontrada no arquivo config.json.",
                'details' => ['connection' => $name]
            ];
            self::$cachedStatus[$name] = $result;
            return $result;
        }

        $driver   = strtolower((string)($config['driver'] ?? 'mysql'));
        $host     = (string)($config['host'] ?? '127.0.0.1');
        $port     = (int)($config['port'] ?? ($driver === 'pgsql' ? 5432 : 3306));
        $dbname   = (string)($config['dbname'] ?? $config['database'] ?? 'ztframework');
        $user     = (string)($config['user'] ?? $config['username'] ?? 'root');
        $password = (string)($config['password'] ?? '');
        $charset  = (string)($config['charset'] ?? 'utf8mb4');

        if ($driver !== 'mysql') {
            // Para outros drivers (ex: pgsql ou sqlite de teste), consideramos ok se conseguir conectar
            $result = [
                'ok'      => true,
                'action'  => 'skipped_non_mysql',
                'message' => "Driver '{$driver}' não necessita de auto-provisionamento MySQL.",
                'details' => ['driver' => $driver]
            ];
            self::$cachedStatus[$name] = $result;
            return $result;
        }

        // 1. Tentar conectar ao servidor MySQL (sem especificar banco)
        $serverDsn = "mysql:host={$host};port={$port};charset=utf8mb4";
        $pdoOptions = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_MULTI_STATEMENTS => true
        ];

        try {
            $serverPdo = new PDO($serverDsn, $user, $password, $pdoOptions);
        } catch (PDOException $e) {
            $result = [
                'ok'      => false,
                'error'   => 'ERR_DATABASE_SERVER_CONNECTION',
                'message' => "Não foi possível conectar ao servidor MySQL em '{$host}:{$port}' com o usuário '{$user}'. Verifique se o MySQL está rodando e as credenciais no config.json.",
                'details' => [
                    'host'      => $host,
                    'port'      => $port,
                    'user'      => $user,
                    'dbname'    => $dbname,
                    'pdo_error' => $e->getMessage(),
                    'pdo_code'  => $e->getCode()
                ]
            ];
            self::$cachedStatus[$name] = $result;
            return $result;
        }

        $actionsPerformed = [];

        // 2. Conferir se a base de dados $dbname existe
        try {
            $stmt = $serverPdo->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = :dbname");
            $stmt->execute(['dbname' => $dbname]);
            $dbExists = (bool)$stmt->fetchColumn();

            if (!$dbExists) {
                // Config está ok para acessar o servidor, podemos criar o banco!
                $serverPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $actionsPerformed[] = 'created_database';
            }
        } catch (Throwable $e) {
            $result = [
                'ok'      => false,
                'error'   => 'ERR_DATABASE_CREATION_FAILED',
                'message' => "Falha ao verificar ou criar a base de dados '{$dbname}': " . $e->getMessage(),
                'details' => [
                    'host'      => $host,
                    'user'      => $user,
                    'dbname'    => $dbname,
                    'error'     => $e->getMessage()
                ]
            ];
            self::$cachedStatus[$name] = $result;
            return $result;
        }

        // 3. Conectar diretamente à base de dados para inspecionar tabelas
        $dbDsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
        try {
            $dbPdo = new PDO($dbDsn, $user, $password, $pdoOptions);
        } catch (PDOException $e) {
            $result = [
                'ok'      => false,
                'error'   => 'ERR_DATABASE_SELECTION_FAILED',
                'message' => "Não foi possível selecionar o banco '{$dbname}' após verificação: " . $e->getMessage(),
                'details' => [
                    'dbname'    => $dbname,
                    'pdo_error' => $e->getMessage()
                ]
            ];
            self::$cachedStatus[$name] = $result;
            return $result;
        }

        // 4. Conferir tabelas existentes
        try {
            $stmtTables = $dbPdo->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :dbname");
            $stmtTables->execute(['dbname' => $dbname]);
            $existingTables = $stmtTables->fetchAll(PDO::FETCH_COLUMN) ?: [];

            $missingTables = array_diff(self::REQUIRED_TABLES, $existingTables);

            // Se faltar qualquer tabela essencial ou se o banco foi recém-criado, executa script.sql
            if (!empty($missingTables) || in_array('created_database', $actionsPerformed, true)) {
                $scriptPath = realpath(__DIR__ . '/script.sql') ?: (__DIR__ . '/script.sql');
                if (is_file($scriptPath)) {
                    $sqlContent = (string)file_get_contents($scriptPath);

                    // Se o nome do banco configurado for diferente do padrão 'ztframework', substitui no script
                    if ($dbname !== 'ztframework') {
                        $sqlContent = str_replace('`ztframework`', "`{$dbname}`", $sqlContent);
                    }

                    // Executa a DDL e DML do script.sql
                    $dbPdo->exec($sqlContent);
                    $actionsPerformed[] = 'executed_script_sql';

                    // Re-inspeciona tabelas
                    $stmtTables->execute(['dbname' => $dbname]);
                    $existingTables = $stmtTables->fetchAll(PDO::FETCH_COLUMN) ?: [];
                }
            }

            // Registra conexão reutilizável no DBConnection
            DBConnection::setConnection($name, $dbPdo);

            $actionSummary = empty($actionsPerformed) ? 'database_ready' : implode(', ', $actionsPerformed);

            $result = [
                'ok'             => true,
                'action'         => $actionSummary,
                'message'        => "Base de dados '{$dbname}' pronta para operação.",
                'database'       => $dbname,
                'existingTables' => $existingTables,
                'missingTables'  => array_diff(self::REQUIRED_TABLES, $existingTables)
            ];

            self::$cachedStatus[$name] = $result;
            return $result;

        } catch (Throwable $e) {
            $result = [
                'ok'      => false,
                'error'   => 'ERR_DATABASE_PROVISIONING_FAILED',
                'message' => "Falha ao provisionar tabelas e dados iniciais no banco '{$dbname}': " . $e->getMessage(),
                'details' => [
                    'dbname' => $dbname,
                    'error'  => $e->getMessage()
                ]
            ];
            self::$cachedStatus[$name] = $result;
            return $result;
        }
    }

    /**
     * Limpa o cache de status (útil para testes ou reconexão em runtime).
     */
    public static function clearCache(): void {
        self::$cachedStatus = null;
    }
}
