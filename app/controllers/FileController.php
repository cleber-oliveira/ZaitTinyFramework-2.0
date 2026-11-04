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

namespace app\controllers;

use app\core\database\DBConnection;
use app\core\utils\JsonResponse;
use app\core\utils\Sanitize;
use PDO;
use RuntimeException;

/**
 * ZTFramework - FileController
 * 
 * Gerencia uploads de arquivos codificados em Base64 e controla o acesso seguro (ACL):
 * - Dono do arquivo (idDono)
 * - Nível de visibilidade (Público / Privado)
 * - Grupos autorizados (gruposPermitidos)
 * - Usuários autorizados individualmente (usuariosPermitidos)
 */
class FileController {
    private string $storageUploadsDir;

    public function __construct() {
        $this->storageUploadsDir = dirname(__DIR__, 2) . '/storage/uploads';
        if (!is_dir($this->storageUploadsDir)) {
            @mkdir($this->storageUploadsDir, 0755, true);
        }
    }

    private function initSession(): void {
        if (session_status() === PHP_SESSION_NONE && !headers_sent() && php_sapi_name() !== 'cli') {
            session_start();
        }
    }

    /**
     * Upload de arquivo em Base64 com definição de ACL.
     */
    public function upload(): void {
        $this->initSession();

        $idUsuario = $_SESSION['user']['idUsuario'] ?? null;
        if (!$idUsuario) {
            JsonResponse::error(401, 'ERR_UNAUTHORIZED', 'Autenticação necessária para realizar uploads.');
            return;
        }

        $base64Data   = $_POST['arquivo_base64'] ?? '';
        $nomeOriginal = Sanitize::string($_POST['nome_original'] ?? 'arquivo_sem_nome');
        $publico      = (int)($_POST['publico'] ?? 0);
        $grupos       = $_POST['grupos_permitidos'] ?? [];
        $usuarios     = $_POST['usuarios_permitidos'] ?? [];

        if (is_string($grupos)) {
            $grupos = json_decode($grupos, true) ?: [];
        }
        if (is_string($usuarios)) {
            $usuarios = json_decode($usuarios, true) ?: [];
        }

        if (empty($base64Data)) {
            JsonResponse::error(400, 'ERR_EMPTY_FILE', 'Nenhum conteúdo Base64 foi fornecido.');
            return;
        }

        $mimeType = 'application/octet-stream';
        if (preg_match('/^data:([a-zA-Z0-9\/\+_\-\.]+);base64,/', $base64Data, $matches)) {
            $mimeType = $matches[1];
            $base64Data = substr($base64Data, strlen($matches[0]));
        }

        $decodedBinary = base64_decode($base64Data, true);
        if ($decodedBinary === false) {
            JsonResponse::error(400, 'ERR_INVALID_BASE64', 'Conteúdo Base64 corrompido ou inválido.');
            return;
        }

        $ext = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION)) ?: 'bin';
        $hash = bin2hex(random_bytes(16));
        $storedFilename = "{$hash}.{$ext}.base64";
        $targetPath = $this->storageUploadsDir . '/' . $storedFilename;

        if (file_put_contents($targetPath, $base64Data) === false) {
            JsonResponse::error(500, 'ERR_STORAGE_WRITE', 'Falha ao gravar arquivo no disco.');
            return;
        }

        $db = DBConnection::getConnection();
        $stmt = $db->prepare("
            INSERT INTO arquivosUpload 
            (hash, nomeOriginal, mimeType, tamanhoBytes, idDono, publico, gruposPermitidos, usuariosPermitidos, caminhoFisico)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $hash,
            $nomeOriginal,
            $mimeType,
            strlen($decodedBinary),
            $idUsuario,
            $publico,
            !empty($grupos) ? json_encode(array_values($grupos)) : null,
            !empty($usuarios) ? json_encode(array_values($usuarios)) : null,
            $storedFilename
        ]);

        JsonResponse::success([
            'hash'         => $hash,
            'nomeOriginal' => $nomeOriginal,
            'url'          => "/arquivo/{$hash}",
            'tamanhoBytes' => strlen($decodedBinary)
        ], 'Upload em Base64 realizado com sucesso e regras de ACL gravadas!');
    }

    /**
     * Entrega segura de arquivos baseada no Dono e regras de ACL.
     */
    public function download(): void {
        $this->initSession();

        $hash = Sanitize::string($_GET['hash'] ?? '');
        if (empty($hash)) {
            $uriParts = explode('/', trim($_SERVER['REQUEST_URI'] ?? '', '/'));
            $hash = end($uriParts);
        }

        $db = DBConnection::getConnection();
        $stmt = $db->prepare("SELECT * FROM arquivosUpload WHERE hash = ? LIMIT 1");
        $stmt->execute([$hash]);
        $arquivo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$arquivo) {
            JsonResponse::error(404, 'ERR_FILE_NOT_FOUND', 'Arquivo não encontrado.');
            return;
        }

        $temAcesso = false;
        $idUsuario = $_SESSION['user']['idUsuario'] ?? null;
        $gruposUser = (array)($_SESSION['user']['grupos'] ?? []);

        if ((int)$arquivo['publico'] === 1) {
            $temAcesso = true;
        }

        if ($idUsuario && (int)$arquivo['idDono'] === (int)$idUsuario) {
            $temAcesso = true;
        }

        if (in_array('admin', $gruposUser, true)) {
            $temAcesso = true;
        }

        if (!$temAcesso && $idUsuario && !empty($arquivo['gruposPermitidos'])) {
            $gruposPerm = json_decode((string)$arquivo['gruposPermitidos'], true) ?: [];
            if (!empty(array_intersect($gruposPerm, $gruposUser))) {
                $temAcesso = true;
            }
        }

        if (!$temAcesso && $idUsuario && !empty($arquivo['usuariosPermitidos'])) {
            $usersPerm = json_decode((string)$arquivo['usuariosPermitidos'], true) ?: [];
            if (in_array($idUsuario, $usersPerm, true)) {
                $temAcesso = true;
            }
        }

        if (!$temAcesso) {
            JsonResponse::error(403, 'ERR_ACCESS_DENIED', 'Acesso negado: Você não possui permissão para visualizar este arquivo.');
            return;
        }

        $caminhoFisico = $this->storageUploadsDir . '/' . basename((string)$arquivo['caminhoFisico']);
        if (!is_file($caminhoFisico)) {
            JsonResponse::error(404, 'ERR_FILE_CORRUPT', 'Arquivo físico ausente no storage.');
            return;
        }

        $base64 = (string)file_get_contents($caminhoFisico);
        $binary = base64_decode($base64);

        if (php_sapi_name() !== 'cli' && !headers_sent()) {
            $safeMimesInline = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf', 'text/plain'];
            $mimeLower = strtolower((string)$arquivo['mimeType']);
            $disposition = in_array($mimeLower, $safeMimesInline, true) ? 'inline' : 'attachment';

            header("Content-Type: {$arquivo['mimeType']}");
            header("Content-Length: " . strlen($binary));
            header("Content-Disposition: {$disposition}; filename=\"" . addslashes((string)$arquivo['nomeOriginal']) . '"');
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: SAMEORIGIN');
            header('Cache-Control: private, max-age=3600');
        }

        echo $binary;
    }

    /**
     * Lista arquivos acessíveis pelo usuário atual.
     */
    public function listar(): void {
        $this->initSession();

        $idUsuario = $_SESSION['user']['idUsuario'] ?? null;
        if (!$idUsuario) {
            JsonResponse::error(401, 'ERR_UNAUTHORIZED', 'Autenticação necessária.');
            return;
        }

        $isAdmin = in_array('admin', (array)($_SESSION['user']['grupos'] ?? []), true);
        $db = DBConnection::getConnection();

        if ($isAdmin) {
            $stmt = $db->query("
                SELECT a.*, u.nome AS nomeDono, u.email AS emailDono 
                FROM arquivosUpload a 
                INNER JOIN usuarios u ON u.idUsuario = a.idDono
                ORDER BY a.idArquivo DESC
            ");
        } else {
            $stmt = $db->prepare("
                SELECT a.*, u.nome AS nomeDono, u.email AS emailDono 
                FROM arquivosUpload a 
                INNER JOIN usuarios u ON u.idUsuario = a.idDono
                WHERE a.idDono = ? OR a.publico = 1
                ORDER BY a.idArquivo DESC
            ");
            $stmt->execute([$idUsuario]);
        }

        $arquivos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        JsonResponse::success($arquivos, 'Lista de arquivos recuperada com sucesso.');
    }
}
