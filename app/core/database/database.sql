-- ZTFramework 2.0 - Esquema Oficial do Banco de Dados
-- Charset: UTF-8 (utf8mb4) / Collation: utf8mb4_unicode_ci

CREATE DATABASE IF NOT EXISTS `ztframework` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `ztframework`;

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- 1. Tabela de Usuários
CREATE TABLE IF NOT EXISTS `usuarios` (
    `idUsuario` INT AUTO_INCREMENT PRIMARY KEY,
    `nome` VARCHAR(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    `email` VARCHAR(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL UNIQUE,
    `senha` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    `foto` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Foto do perfil em formato Base64',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '1=Ativo, 0=Inativo',
    `primeiroAcesso` TINYINT NOT NULL DEFAULT 1 COMMENT '1=Precisa trocar senha inicial',
    `dataCriacao` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `dataAtualizacao` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_usuario_email` (`email`),
    INDEX `idx_usuario_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabela de Grupos (Níveis de Acesso)
CREATE TABLE IF NOT EXISTS `grupos` (
    `idGrupo` INT AUTO_INCREMENT PRIMARY KEY,
    `grupo` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL UNIQUE,
    `descricao` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `isAdmin` TINYINT NOT NULL DEFAULT 0 COMMENT '1=SuperAdmin',
    INDEX `idx_grupo_nome` (`grupo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabela de Permissões Granulares
CREATE TABLE IF NOT EXISTS `permissoes` (
    `idPermissao` INT AUTO_INCREMENT PRIMARY KEY,
    `permissao` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL UNIQUE,
    `descricao` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    INDEX `idx_permissao_nome` (`permissao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Associação Usuários <-> Grupos (Muitos para Muitos)
CREATE TABLE IF NOT EXISTS `usuariosGrupos` (
    `idUsuario` INT NOT NULL,
    `idGrupo` INT NOT NULL,
    PRIMARY KEY (`idUsuario`, `idGrupo`),
    CONSTRAINT `fk_ug_usuario` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`idUsuario`) ON DELETE CASCADE,
    CONSTRAINT `fk_ug_grupo` FOREIGN KEY (`idGrupo`) REFERENCES `grupos` (`idGrupo`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Associação Grupos <-> Permissões (Muitos para Muitos)
CREATE TABLE IF NOT EXISTS `gruposPermissoes` (
    `idGrupo` INT NOT NULL,
    `idPermissao` INT NOT NULL,
    PRIMARY KEY (`idGrupo`, `idPermissao`),
    CONSTRAINT `fk_gp_grupo` FOREIGN KEY (`idGrupo`) REFERENCES `grupos` (`idGrupo`) ON DELETE CASCADE,
    CONSTRAINT `fk_gp_permissao` FOREIGN KEY (`idPermissao`) REFERENCES `permissoes` (`idPermissao`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Tabela de Uploads Privados em Base64 com ACL (Dono + Grupos + Usuários permitidos)
CREATE TABLE IF NOT EXISTS `arquivosUpload` (
    `idArquivo` INT AUTO_INCREMENT PRIMARY KEY,
    `hash` VARCHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL UNIQUE,
    `nomeOriginal` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    `mimeType` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    `tamanhoBytes` INT NOT NULL,
    `idDono` INT NOT NULL,
    `publico` TINYINT NOT NULL DEFAULT 0 COMMENT '1=Livre, 0=Restrito por ACL',
    `gruposPermitidos` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'JSON de grupos autorizados ex: ["admin","gerente"]',
    `usuariosPermitidos` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'JSON de idUsuarios autorizados ex: [2,3]',
    `caminhoFisico` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nome do arquivo salvo no storage',
    `dataUpload` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_arquivo_hash` (`hash`),
    INDEX `idx_arquivo_dono` (`idDono`),
    CONSTRAINT `fk_arq_dono` FOREIGN KEY (`idDono`) REFERENCES `usuarios` (`idUsuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SEEDS INICIAIS
-- 1. Grupos padrão: todo usuário começa no nível 'usuario'
INSERT IGNORE INTO `grupos` (`idGrupo`, `grupo`, `descricao`, `isAdmin`) VALUES
(1, 'admin', 'Administrador do Sistema com Acesso Total', 1),
(2, 'usuario', 'Usuário Padrão do Sistema (Nível Inicial)', 0),
(3, 'gerente', 'Gestor de Recursos e Conteúdo', 0);

-- 2. Permissões padrão
INSERT IGNORE INTO `permissoes` (`idPermissao`, `permissao`, `descricao`) VALUES
(1, 'usuarios.view', 'Visualizar lista e perfis de usuários'),
(2, 'usuarios.create', 'Cadastrar novos usuários manualmente'),
(3, 'usuarios.edit', 'Editar dados e status de usuários'),
(4, 'usuarios.delete', 'Excluir usuários do sistema'),
(5, 'grupos.manage', 'Gerenciar e associar grupos e permissões'),
(6, 'arquivos.upload', 'Efetuar uploads de arquivos em Base64'),
(7, 'arquivos.manage', 'Gerenciar e auditar arquivos do sistema');

-- 3. Associação padrão de permissões
INSERT IGNORE INTO `gruposPermissoes` (`idGrupo`, `idPermissao`) VALUES
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 6), (1, 7),
(2, 6),
(3, 1), (3, 6);
