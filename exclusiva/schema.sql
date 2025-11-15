CREATE DATABASE IF NOT EXISTS exclusiva CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE exclusiva;

CREATE TABLE IF NOT EXISTS imoveis (
  codigo BIGINT PRIMARY KEY,
  referencia VARCHAR(64),
  finalidade VARCHAR(32),
  tipo VARCHAR(128),
  dormitorios INT,
  suites INT,
  banheiros INT,
  salas INT,
  garagem INT,
  acomodacoes INT,
  ano_construcao INT,
  valor DECIMAL(14,2),
  cidade VARCHAR(128),
  estado CHAR(2),
  bairro VARCHAR(128),
  logradouro VARCHAR(255),
  numero VARCHAR(32),
  cep VARCHAR(20),
  area_privativa DECIMAL(12,2),
  area_total DECIMAL(12,2),
  terreno DECIMAL(12,2),
  descricao MEDIUMTEXT,
  status_ativo TINYINT(1),
  atualizado_em DATETIME,
  cadastrado_em DATE,
  INDEX idx_bairro (bairro),
  INDEX idx_cidade (cidade),
  INDEX idx_valor (valor)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS imoveis_imagens (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo BIGINT,
  url TEXT,
  destaque TINYINT(1),
  INDEX idx_codigo (codigo),
  FOREIGN KEY (codigo) REFERENCES imoveis(codigo) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS imoveis_caracteristicas (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo BIGINT,
  grupo VARCHAR(128),
  nome VARCHAR(128),
  INDEX idx_codigo (codigo),
  FOREIGN KEY (codigo) REFERENCES imoveis(codigo) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS import_status (
  id TINYINT PRIMARY KEY DEFAULT 1,
  last_import_at DATETIME NULL,
  is_running TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB;

INSERT IGNORE INTO import_status (id, last_import_at, is_running) VALUES (1, NULL, 0);
