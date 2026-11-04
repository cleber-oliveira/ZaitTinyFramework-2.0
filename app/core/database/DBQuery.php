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
use RuntimeException;
use Throwable;

class DBQuery {
    private string $table;
    private string $fields;
    private array $conditions = [];
    private array $joins = [];
    private array $bindings = [];
    private string $orderBy = '';
    private string $limit = '';
    private ?PDO $db = null;

    /**
     * @param string $table Nome da tabela (ex: 'usuarios' ou 'usuarios u')
     * @param string $fields Campos do SELECT (ex: '*' ou 'id, nome, email')
     * @param PDO|null $db Conexão PDO opcional (usa DBConnection por padrão)
     */
    public function __construct(string $table, string $fields = '*', ?PDO $db = null) {
        $this->table = trim($table);
        $this->fields = trim($fields) === '' ? '*' : trim($fields);
        $this->db = $db;
    }

    private function getDb(): PDO {
        if ($this->db === null) {
            $this->db = DBConnection::getConnection();
        }
        return $this->db;
    }

    /**
     * Adiciona uma condição WHERE simples (ex: AND, status, =, 1).
     */
    public function addCondition(string $logical, string $column, string $operator, mixed $value): self {
        $placeholder = ':p' . count($this->bindings);
        $logical = strtoupper(trim($logical));
        if (!in_array($logical, ['AND', 'OR'], true)) {
            $logical = 'AND';
        }

        $this->conditions[] = [
            'type'        => 'basic',
            'logical'     => $logical,
            'column'      => trim($column),
            'operator'    => trim($operator),
            'placeholder' => $placeholder,
            'value'       => $value
        ];

        $this->bindings[$placeholder] = $value;
        return $this;
    }

    /**
     * Adiciona uma condição WHERE IN (ex: AND, grupo, ['admin', 'vendas']).
     */
    public function addIn(string $logical, string $column, array $values): self {
        if (empty($values)) {
            // Condição sempre falsa se lista vazia
            $this->conditions[] = [
                'type'    => 'raw',
                'logical' => strtoupper(trim($logical)),
                'sql'     => '1 = 0'
            ];
            return $this;
        }

        $logical = strtoupper(trim($logical));
        if (!in_array($logical, ['AND', 'OR'], true)) {
            $logical = 'AND';
        }

        $placeholders = [];
        foreach ($values as $val) {
            $ph = ':p' . count($this->bindings);
            $this->bindings[$ph] = $val;
            $placeholders[] = $ph;
        }

        $this->conditions[] = [
            'type'    => 'raw',
            'logical' => $logical,
            'sql'     => trim($column) . ' IN (' . implode(', ', $placeholders) . ')'
        ];

        return $this;
    }

    /**
     * Adiciona um JOIN estruturado com resolução inteligente de alias.
     * Ex: addJoin('INNER', 'u', 'idGrupo', '=', 'grupos g', 'idGrupo')
     */
    public function addJoin(string $type, string $tableOrAlias, string $key, string $operator, string $foreignTable, string $foreignKey): self {
        $type = strtoupper(trim($type));
        if (!in_array($type, ['INNER', 'LEFT', 'RIGHT', 'FULL'], true)) {
            $type = 'INNER';
        }

        // Se foreignTable possui alias (ex: 'grupos g' ou 'grupos AS g'), extrai o alias para a cláusula ON
        $parts = preg_split('/\s+(?:AS\s+)?/i', trim($foreignTable));
        $foreignAlias = !empty($parts) ? end($parts) : $foreignTable;

        $this->joins[] = sprintf(
            "%s JOIN %s ON %s.%s %s %s.%s",
            $type,
            $foreignTable,
            $tableOrAlias,
            $key,
            $operator,
            $foreignAlias,
            $foreignKey
        );

        return $this;
    }

    /**
     * Adiciona uma cláusula de JOIN personalizada completa.
     * Ex: joinRaw("INNER JOIN usuariosGrupos ug ON ug.idUsuario = u.idUsuario")
     */
    public function joinRaw(string $joinClause): self {
        $this->joins[] = trim($joinClause);
        return $this;
    }

    /**
     * Define ordenação (ORDER BY).
     */
    public function orderBy(string $column, string $direction = 'ASC'): self {
        $dir = strtoupper(trim($direction)) === 'DESC' ? 'DESC' : 'ASC';
        $this->orderBy = "ORDER BY " . trim($column) . " " . $dir;
        return $this;
    }

    /**
     * Define limites e paginação (LIMIT offset, count).
     */
    public function limit(int $limit, int $offset = 0): self {
        if ($offset > 0) {
            $this->limit = "LIMIT {$limit} OFFSET {$offset}";
        } else {
            $this->limit = "LIMIT {$limit}";
        }
        return $this;
    }

    /**
     * Monta a cláusula WHERE a partir das condições registradas.
     */
    public function buildWhere(): string {
        if (empty($this->conditions)) {
            return '';
        }

        $parts = [];
        foreach ($this->conditions as $i => $cond) {
            $logical = ($i === 0) ? '' : $cond['logical'] . ' ';
            if ($cond['type'] === 'basic') {
                $parts[] = $logical . $cond['column'] . ' ' . $cond['operator'] . ' ' . $cond['placeholder'];
            } elseif ($cond['type'] === 'raw') {
                $parts[] = $logical . $cond['sql'];
            }
        }

        return 'WHERE ' . implode(' ', $parts);
    }

    /**
     * Retorna a SQL SELECT compilada.
     */
    public function getSelectSql(): string {
        $sql = "SELECT {$this->fields} FROM {$this->table}";

        if (!empty($this->joins)) {
            $sql .= ' ' . implode(' ', $this->joins);
        }

        $where = $this->buildWhere();
        if (!empty($where)) {
            $sql .= ' ' . $where;
        }

        if (!empty($this->orderBy)) {
            $sql .= ' ' . $this->orderBy;
        }

        if (!empty($this->limit)) {
            $sql .= ' ' . $this->limit;
        }

        return $sql;
    }

    public function getBindings(): array {
        return $this->bindings;
    }

    /**
     * Executa o SELECT e retorna os registros.
     */
    public function select(): array {
        $sql = $this->getSelectSql();
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute($this->bindings);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna o primeiro registro retornado pela consulta, ou null se vazio.
     */
    public function first(): ?array {
        $clone = clone $this;
        $clone->limit(1);
        $rows = $clone->select();
        return !empty($rows) ? $rows[0] : null;
    }

    /**
     * Executa um INSERT seguro.
     */
    public function insert(array $data): string|int {
        if (empty($data)) {
            throw new RuntimeException("Dados vazios para inserção.");
        }

        $columns = array_keys($data);
        $placeholders = [];
        $bindings = [];

        foreach ($data as $col => $val) {
            $ph = ':' . $col;
            $placeholders[] = $ph;
            $bindings[$ph] = $val;
        }

        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $db = $this->getDb();
        $stmt = $db->prepare($sql);
        $stmt->execute($bindings);

        return $db->lastInsertId();
    }

    /**
     * Executa um UPDATE seguro respeitando o WHERE configurado.
     */
    public function update(array $data): int {
        if (empty($data)) {
            throw new RuntimeException("Dados vazios para atualização.");
        }

        $setParts = [];
        $bindings = $this->bindings;

        foreach ($data as $col => $val) {
            $ph = ':up_' . $col;
            $setParts[] = "{$col} = {$ph}";
            $bindings[$ph] = $val;
        }

        $where = $this->buildWhere();
        if (empty($where)) {
            throw new RuntimeException("UPDATE sem cláusula WHERE não é permitido por segurança.");
        }

        $sql = sprintf("UPDATE %s SET %s %s", $this->table, implode(', ', $setParts), $where);
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute($bindings);

        return $stmt->rowCount();
    }

    /**
     * Executa um DELETE seguro respeitando o WHERE configurado.
     */
    public function delete(): int {
        $where = $this->buildWhere();
        if (empty($where)) {
            throw new RuntimeException("DELETE sem cláusula WHERE não é permitido por segurança.");
        }

        $sql = sprintf("DELETE FROM %s %s", $this->table, $where);
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute($this->bindings);

        return $stmt->rowCount();
    }
}
