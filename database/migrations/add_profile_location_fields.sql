-- Migration: Adicionar campos de foto e localização geográfica
-- Data: 2025-11-13

-- Adicionar campos à tabela leads
ALTER TABLE leads ADD COLUMN IF NOT EXISTS profile_pic_url VARCHAR(500);
ALTER TABLE leads ADD COLUMN IF NOT EXISTS city VARCHAR(100);
ALTER TABLE leads ADD COLUMN IF NOT EXISTS state VARCHAR(100);
ALTER TABLE leads ADD COLUMN IF NOT EXISTS country VARCHAR(100);
ALTER TABLE leads ADD COLUMN IF NOT EXISTS latitude DECIMAL(10, 8);
ALTER TABLE leads ADD COLUMN IF NOT EXISTS longitude DECIMAL(11, 8);

-- Adicionar campos à tabela conversas  
ALTER TABLE conversas ADD COLUMN IF NOT EXISTS wa_id VARCHAR(50);
ALTER TABLE conversas ADD COLUMN IF NOT EXISTS city VARCHAR(100);
ALTER TABLE conversas ADD COLUMN IF NOT EXISTS state VARCHAR(100);
ALTER TABLE conversas ADD COLUMN IF NOT EXISTS country VARCHAR(100);

-- Criar índices para busca por localização
CREATE INDEX IF NOT EXISTS idx_leads_city ON leads(city);
CREATE INDEX IF NOT EXISTS idx_leads_state ON leads(state);
CREATE INDEX IF NOT EXISTS idx_conversas_city ON conversas(city);

-- Comentários
COMMENT ON COLUMN leads.profile_pic_url IS 'URL da foto de perfil do WhatsApp';
COMMENT ON COLUMN leads.city IS 'Cidade do lead (capturada do WhatsApp ou informada)';
COMMENT ON COLUMN leads.state IS 'Estado do lead (capturado do WhatsApp ou informado)';
COMMENT ON COLUMN leads.latitude IS 'Latitude da localização do lead';
COMMENT ON COLUMN leads.longitude IS 'Longitude da localização do lead';
COMMENT ON COLUMN conversas.wa_id IS 'WhatsApp ID do usuário';
COMMENT ON COLUMN conversas.city IS 'Cidade detectada na mensagem';
COMMENT ON COLUMN conversas.state IS 'Estado detectado na mensagem';
