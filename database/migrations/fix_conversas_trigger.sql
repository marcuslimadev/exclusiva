-- Corrigir trigger da tabela conversas
-- A função update_updated_at_column() tenta setar NEW.updated_at
-- mas conversas não tem updated_at, tem ultima_atividade

-- Criar função específica para conversas
CREATE OR REPLACE FUNCTION update_conversas_ultima_atividade()
RETURNS TRIGGER AS $$
BEGIN
    NEW.ultima_atividade = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Remover trigger antigo
DROP TRIGGER IF EXISTS update_conversas_ultima_atividade ON conversas;

-- Criar trigger correto
CREATE TRIGGER update_conversas_ultima_atividade 
    BEFORE UPDATE ON conversas
    FOR EACH ROW 
    EXECUTE FUNCTION update_conversas_ultima_atividade();
