-- Banco de Dados: CRM Exclusiva Lar Imóveis (PostgreSQL)
-- Criado: 13/11/2025

-- =============================================
-- TABELA: users - Usuários do sistema
-- =============================================
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    tipo VARCHAR(20) NOT NULL DEFAULT 'corretor' CHECK (tipo IN ('admin', 'corretor')),
    telefone VARCHAR(20),
    ativo SMALLINT DEFAULT 1,
    foto_perfil VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_tipo ON users(tipo);
CREATE INDEX idx_users_ativo ON users(ativo);

-- =============================================
-- TABELA: leads - Leads capturados via WhatsApp
-- =============================================
CREATE TABLE leads (
    id SERIAL PRIMARY KEY,
    telefone VARCHAR(20) NOT NULL,
    nome VARCHAR(100),
    email VARCHAR(100),
    whatsapp_name VARCHAR(100),
    
    -- Dados extraídos pela IA
    budget_min DECIMAL(15,2),
    budget_max DECIMAL(15,2),
    localizacao VARCHAR(255),
    quartos INTEGER,
    suites INTEGER,
    garagem INTEGER,
    caracteristicas_desejadas TEXT,
    
    -- Gestão
    corretor_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    status VARCHAR(20) DEFAULT 'novo' CHECK (status IN ('novo', 'em_atendimento', 'qualificado', 'proposta', 'fechado', 'perdido')),
    origem VARCHAR(50) DEFAULT 'whatsapp',
    score INTEGER DEFAULT 0,
    
    -- Timestamps
    primeira_interacao TIMESTAMP,
    ultima_interacao TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_leads_telefone ON leads(telefone);
CREATE INDEX idx_leads_status ON leads(status);
CREATE INDEX idx_leads_corretor ON leads(corretor_id);
CREATE INDEX idx_leads_nome ON leads(nome);

-- =============================================
-- TABELA: conversas - Conversas WhatsApp
-- =============================================
CREATE TABLE conversas (
    id SERIAL PRIMARY KEY,
    telefone VARCHAR(20) NOT NULL,
    lead_id INTEGER REFERENCES leads(id) ON DELETE CASCADE,
    corretor_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    
    -- Status da conversa
    status VARCHAR(30) DEFAULT 'ativa' CHECK (status IN ('ativa', 'aguardando_lead', 'aguardando_corretor', 'finalizada')),
    stage VARCHAR(50),
    
    -- Dados do WhatsApp
    whatsapp_name VARCHAR(100),
    profile_pic VARCHAR(255),
    
    -- Contexto IA
    contexto_conversa TEXT,
    ultima_mensagem TEXT,
    
    -- Timestamps
    iniciada_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultima_atividade TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    finalizada_em TIMESTAMP
);

CREATE INDEX idx_conversas_telefone ON conversas(telefone);
CREATE INDEX idx_conversas_status ON conversas(status);
CREATE INDEX idx_conversas_lead ON conversas(lead_id);
CREATE INDEX idx_conversas_ultima_atividade ON conversas(ultima_atividade);

-- =============================================
-- TABELA: mensagens - Mensagens WhatsApp
-- =============================================
CREATE TABLE mensagens (
    id SERIAL PRIMARY KEY,
    conversa_id INTEGER NOT NULL REFERENCES conversas(id) ON DELETE CASCADE,
    
    -- Dados Twilio
    message_sid VARCHAR(100),
    direction VARCHAR(10) NOT NULL CHECK (direction IN ('incoming', 'outgoing')),
    message_type VARCHAR(20) DEFAULT 'text' CHECK (message_type IN ('text', 'audio', 'image', 'document')),
    
    -- Conteúdo
    content TEXT,
    media_url VARCHAR(500),
    transcription TEXT,
    
    -- Status
    status VARCHAR(20),
    error_message TEXT,
    
    -- Timestamps
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    delivered_at TIMESTAMP,
    read_at TIMESTAMP
);

CREATE INDEX idx_mensagens_conversa ON mensagens(conversa_id);
CREATE INDEX idx_mensagens_message_sid ON mensagens(message_sid);
CREATE INDEX idx_mensagens_direction ON mensagens(direction);
CREATE INDEX idx_mensagens_sent_at ON mensagens(sent_at);

-- =============================================
-- TABELA: imo_properties - Catálogo de Imóveis
-- =============================================
CREATE TABLE imo_properties (
    id SERIAL PRIMARY KEY,
    codigo_imovel VARCHAR(100) NOT NULL UNIQUE,
    referencia_imovel VARCHAR(100),
    finalidade_imovel VARCHAR(20) NOT NULL DEFAULT 'Venda' CHECK (finalidade_imovel IN ('Venda','Aluguel','Venda/Aluguel')),
    tipo_imovel VARCHAR(50) NOT NULL,
    descricao TEXT,
    observacoes TEXT,
    dormitorios SMALLINT DEFAULT 0,
    suites SMALLINT DEFAULT 0,
    banheiros SMALLINT DEFAULT 0,
    garagem SMALLINT DEFAULT 0,
    valor_venda DECIMAL(15,2),
    valor_aluguel DECIMAL(15,2),
    condominio DECIMAL(10,2),
    iptu DECIMAL(10,2),
    cidade VARCHAR(100),
    estado CHAR(2),
    bairro VARCHAR(100),
    endereco VARCHAR(255),
    numero VARCHAR(20),
    cep VARCHAR(15),
    area_privativa DECIMAL(10,2),
    area_total DECIMAL(10,2),
    em_condominio SMALLINT DEFAULT 0,
    nome_condominio VARCHAR(255),
    aceita_financiamento SMALLINT DEFAULT 0,
    exibir_imovel SMALLINT DEFAULT 1,
    destaque SMALLINT DEFAULT 0,
    active SMALLINT DEFAULT 1,
    imagem_destaque TEXT,
    caracteristicas TEXT,
    imagens TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP,
    last_sync TIMESTAMP
);

CREATE INDEX idx_imo_properties_finalidade ON imo_properties(finalidade_imovel);
CREATE INDEX idx_imo_properties_tipo ON imo_properties(tipo_imovel);
CREATE INDEX idx_imo_properties_cidade ON imo_properties(cidade);
CREATE INDEX idx_imo_properties_bairro ON imo_properties(bairro);
CREATE INDEX idx_imo_properties_valor_venda ON imo_properties(valor_venda);
CREATE INDEX idx_imo_properties_valor_aluguel ON imo_properties(valor_aluguel);
CREATE INDEX idx_imo_properties_dormitorios ON imo_properties(dormitorios);
CREATE INDEX idx_imo_properties_exibir ON imo_properties(exibir_imovel);
CREATE INDEX idx_imo_properties_active ON imo_properties(active);

-- =============================================
-- TABELA: lead_property_matches - Matches Lead x Imóvel
-- =============================================
CREATE TABLE lead_property_matches (
    id SERIAL PRIMARY KEY,
    lead_id INTEGER NOT NULL REFERENCES leads(id) ON DELETE CASCADE,
    property_id INTEGER NOT NULL REFERENCES imo_properties(id) ON DELETE CASCADE,
    conversa_id INTEGER REFERENCES conversas(id) ON DELETE SET NULL,
    
    -- Score de compatibilidade (0-100)
    match_score DECIMAL(5,2) DEFAULT 0.00,
    
    -- PDF enviado
    pdf_sent SMALLINT DEFAULT 0,
    pdf_sent_at TIMESTAMP,
    pdf_path VARCHAR(500),
    
    -- Interação do lead
    visualizado SMALLINT DEFAULT 0,
    interesse VARCHAR(10) CHECK (interesse IN ('baixo', 'medio', 'alto')),
    feedback TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_lead_property_matches_lead ON lead_property_matches(lead_id);
CREATE INDEX idx_lead_property_matches_property ON lead_property_matches(property_id);
CREATE INDEX idx_lead_property_matches_score ON lead_property_matches(match_score);

-- =============================================
-- TABELA: atividades - Log de atividades
-- =============================================
CREATE TABLE atividades (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    lead_id INTEGER REFERENCES leads(id) ON DELETE SET NULL,
    tipo VARCHAR(50) NOT NULL,
    descricao TEXT,
    dados JSONB,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_atividades_user ON atividades(user_id);
CREATE INDEX idx_atividades_tipo ON atividades(tipo);
CREATE INDEX idx_atividades_created ON atividades(created_at);

-- =============================================
-- TRIGGERS para updated_at automático
-- =============================================

CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_users_updated_at BEFORE UPDATE ON users
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_leads_updated_at BEFORE UPDATE ON leads
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_conversas_ultima_atividade BEFORE UPDATE ON conversas
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_imo_properties_updated_at BEFORE UPDATE ON imo_properties
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- =============================================
-- DADOS INICIAIS
-- =============================================

-- Usuário Admin
INSERT INTO users (nome, email, senha, tipo, ativo) 
VALUES ('Administrador', 'admin@exclusiva.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1);
-- Senha: password

-- Corretor de exemplo
INSERT INTO users (nome, email, senha, tipo, telefone, ativo) 
VALUES ('João Corretor', 'joao@exclusiva.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'corretor', '(31) 9 8765-4321', 1);
-- Senha: password
