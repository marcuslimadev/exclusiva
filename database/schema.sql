-- Banco de Dados: CRM Exclusiva Lar Imóveis
-- Criado: 12/11/2025

CREATE DATABASE IF NOT EXISTS `crm_exclusiva` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `crm_exclusiva`;

-- =============================================
-- TABELA: users - Usuários do sistema
-- =============================================
CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nome` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) UNIQUE NOT NULL,
    `senha` VARCHAR(255) NOT NULL,
    `tipo` ENUM('admin', 'corretor') NOT NULL DEFAULT 'corretor',
    `telefone` VARCHAR(20),
    `ativo` TINYINT(1) DEFAULT 1,
    `foto_perfil` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_email` (`email`),
    INDEX `idx_tipo` (`tipo`),
    INDEX `idx_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TABELA: leads - Leads capturados via WhatsApp
-- =============================================
CREATE TABLE `leads` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `telefone` VARCHAR(20) NOT NULL,
    `nome` VARCHAR(100),
    `email` VARCHAR(100),
    `whatsapp_name` VARCHAR(100),
    
    -- Dados extraídos pela IA
    `budget_min` DECIMAL(15,2),
    `budget_max` DECIMAL(15,2),
    `localizacao` VARCHAR(255),
    `quartos` INT,
    `suites` INT,
    `garagem` INT,
    `caracteristicas_desejadas` TEXT,
    
    -- Gestão
    `corretor_id` INT UNSIGNED,
    `status` ENUM('novo', 'em_atendimento', 'qualificado', 'proposta', 'fechado', 'perdido') DEFAULT 'novo',
    `origem` VARCHAR(50) DEFAULT 'whatsapp',
    `score` INT DEFAULT 0,
    
    -- Timestamps
    `primeira_interacao` TIMESTAMP NULL,
    `ultima_interacao` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`corretor_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_telefone` (`telefone`),
    INDEX `idx_status` (`status`),
    INDEX `idx_corretor` (`corretor_id`),
    INDEX `idx_nome` (`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TABELA: conversas - Conversas WhatsApp
-- =============================================
CREATE TABLE `conversas` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `telefone` VARCHAR(20) NOT NULL,
    `lead_id` INT UNSIGNED,
    `corretor_id` INT UNSIGNED,
    
    -- Status da conversa
    `status` ENUM('ativa', 'aguardando_lead', 'aguardando_corretor', 'finalizada') DEFAULT 'ativa',
    `stage` VARCHAR(50),
    
    -- Dados do WhatsApp
    `whatsapp_name` VARCHAR(100),
    `profile_pic` VARCHAR(255),
    
    -- Contexto IA
    `contexto_conversa` TEXT,
    `ultima_mensagem` TEXT,
    
    -- Timestamps
    `iniciada_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `ultima_atividade` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `finalizada_em` TIMESTAMP NULL,
    
    FOREIGN KEY (`lead_id`) REFERENCES `leads`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`corretor_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_telefone` (`telefone`),
    INDEX `idx_status` (`status`),
    INDEX `idx_lead` (`lead_id`),
    INDEX `idx_ultima_atividade` (`ultima_atividade`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TABELA: mensagens - Mensagens WhatsApp
-- =============================================
CREATE TABLE `mensagens` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `conversa_id` INT UNSIGNED NOT NULL,
    
    -- Dados Twilio
    `message_sid` VARCHAR(100),
    `direction` ENUM('incoming', 'outgoing') NOT NULL,
    `message_type` ENUM('text', 'audio', 'image', 'document') DEFAULT 'text',
    
    -- Conteúdo
    `content` TEXT,
    `media_url` VARCHAR(500),
    `transcription` TEXT,
    
    -- Status
    `status` VARCHAR(20),
    `error_message` TEXT,
    
    -- Timestamps
    `sent_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `delivered_at` TIMESTAMP NULL,
    `read_at` TIMESTAMP NULL,
    
    FOREIGN KEY (`conversa_id`) REFERENCES `conversas`(`id`) ON DELETE CASCADE,
    INDEX `idx_conversa` (`conversa_id`),
    INDEX `idx_message_sid` (`message_sid`),
    INDEX `idx_direction` (`direction`),
    INDEX `idx_sent_at` (`sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TABELA: imo_properties - Catálogo de Imóveis
-- =============================================
CREATE TABLE `imo_properties` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `codigo_imovel` VARCHAR(100) NOT NULL UNIQUE,
    `referencia_imovel` VARCHAR(100),
    `finalidade_imovel` ENUM('Venda','Aluguel','Venda/Aluguel') NOT NULL DEFAULT 'Venda',
    `tipo_imovel` VARCHAR(50) NOT NULL,
    `descricao` TEXT,
    `observacoes` TEXT,
    `dormitorios` TINYINT DEFAULT 0,
    `suites` TINYINT DEFAULT 0,
    `banheiros` TINYINT DEFAULT 0,
    `garagem` TINYINT DEFAULT 0,
    `valor_venda` DECIMAL(15,2),
    `valor_aluguel` DECIMAL(15,2),
    `condominio` DECIMAL(10,2),
    `iptu` DECIMAL(10,2),
    `cidade` VARCHAR(100),
    `estado` CHAR(2),
    `bairro` VARCHAR(100),
    `endereco` VARCHAR(255),
    `numero` VARCHAR(20),
    `cep` VARCHAR(15),
    `area_privativa` DECIMAL(10,2),
    `area_total` DECIMAL(10,2),
    `em_condominio` TINYINT(1) DEFAULT 0,
    `nome_condominio` VARCHAR(255),
    `aceita_financiamento` TINYINT(1) DEFAULT 0,
    `exibir_imovel` TINYINT(1) DEFAULT 1,
    `destaque` TINYINT(1) DEFAULT 0,
    `active` TINYINT(1) DEFAULT 1,
    `imagem_destaque` TEXT,
    `caracteristicas` TEXT,
    `imagens` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    `last_sync` TIMESTAMP NULL,
    
    INDEX `idx_finalidade` (`finalidade_imovel`),
    INDEX `idx_tipo` (`tipo_imovel`),
    INDEX `idx_cidade` (`cidade`),
    INDEX `idx_bairro` (`bairro`),
    INDEX `idx_valor_venda` (`valor_venda`),
    INDEX `idx_valor_aluguel` (`valor_aluguel`),
    INDEX `idx_dormitorios` (`dormitorios`),
    INDEX `idx_exibir` (`exibir_imovel`),
    INDEX `idx_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TABELA: lead_property_matches - Matches Lead x Imóvel
-- =============================================
CREATE TABLE `lead_property_matches` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `lead_id` INT UNSIGNED NOT NULL,
    `property_id` INT UNSIGNED NOT NULL,
    `conversa_id` INT UNSIGNED,
    
    -- Score de compatibilidade (0-100)
    `match_score` DECIMAL(5,2) DEFAULT 0.00,
    
    -- PDF enviado
    `pdf_sent` TINYINT(1) DEFAULT 0,
    `pdf_sent_at` TIMESTAMP NULL,
    `pdf_path` VARCHAR(500),
    
    -- Interação do lead
    `visualizado` TINYINT(1) DEFAULT 0,
    `interesse` ENUM('baixo', 'medio', 'alto') NULL,
    `feedback` TEXT,
    
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`lead_id`) REFERENCES `leads`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`property_id`) REFERENCES `imo_properties`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`conversa_id`) REFERENCES `conversas`(`id`) ON DELETE SET NULL,
    INDEX `idx_lead` (`lead_id`),
    INDEX `idx_property` (`property_id`),
    INDEX `idx_score` (`match_score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TABELA: atividades - Log de atividades
-- =============================================
CREATE TABLE `atividades` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED,
    `lead_id` INT UNSIGNED,
    `tipo` VARCHAR(50) NOT NULL,
    `descricao` TEXT,
    `dados` JSON,
    `ip_address` VARCHAR(45),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`lead_id`) REFERENCES `leads`(`id`) ON DELETE SET NULL,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_tipo` (`tipo`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- DADOS INICIAIS
-- =============================================

-- Usuário Admin
INSERT INTO `users` (`nome`, `email`, `senha`, `tipo`, `ativo`) 
VALUES ('Administrador', 'admin@exclusiva.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1);
-- Senha: password

-- Corretor de exemplo
INSERT INTO `users` (`nome`, `email`, `senha`, `tipo`, `telefone`, `ativo`) 
VALUES ('João Corretor', 'joao@exclusiva.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'corretor', '(31) 9 8765-4321', 1);
-- Senha: password
