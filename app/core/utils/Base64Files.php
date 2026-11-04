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

class Base64Files {
    private string $storageDir;

    public function __construct(?string $storageDir = null) {
        $this->storageDir = $storageDir ?? Config::get('paths.storage', 'app/storage') . '/uploads';
        if (!is_dir($this->storageDir)) {
            @mkdir($this->storageDir, 0755, true);
        }
    }

    /**
     * Salva um arquivo codificado em Base64 no storage privado.
     * 
     * @param string $base64Content Conteúdo puro em base64 ou Data URI (ex: data:image/png;base64,iVBOR...)
     * @param string $originalName Nome original do arquivo
     * @return array Metadados do arquivo gravado
     */
    public function saveFromBase64(string $base64Content, string $originalName): array {
        // Se contiver prefixo data:mime;base64, remove
        if (preg_match('/^data:([a-zA-Z0-9\/\+_\-\.]+);base64,/', $base64Content, $matches)) {
            $base64Content = substr($base64Content, strlen($matches[0]));
        }

        $decodedBinary = base64_decode($base64Content, true);
        if ($decodedBinary === false) {
            throw new RuntimeException("Conteúdo Base64 inválido.");
        }

        $hash = bin2hex(random_bytes(16));
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $storedFilename = "{$hash}.{$ext}.base64";
        $targetPath = $this->storageDir . '/' . $storedFilename;

        // Salva o conteúdo em base64 no disco
        if (file_put_contents($targetPath, $base64Content) === false) {
            throw new RuntimeException("Falha ao gravar arquivo em storage.");
        }

        $meta = [
            'hash'         => $hash,
            'originalName' => $originalName,
            'extension'    => $ext,
            'size'         => strlen($decodedBinary),
            'storedFile'   => $storedFilename,
            'createdAt'    => date('Y-m-d H:i:s')
        ];

        return $meta;
    }

    /**
     * Lê e decodifica um arquivo salvo para binário puro.
     */
    public function readBinary(string $storedFilename): ?string {
        $path = $this->storageDir . '/' . basename($storedFilename);
        if (!is_file($path)) {
            return null;
        }

        $base64 = (string)file_get_contents($path);
        $binary = base64_decode($base64, true);
        return $binary !== false ? $binary : null;
    }
}
