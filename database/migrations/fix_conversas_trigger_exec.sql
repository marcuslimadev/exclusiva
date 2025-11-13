-- ============================================
-- Corrigir Trigger da Tabela Conversas
-- ============================================

-- 1. Criar função que atualiza ultima_atividade
CREATE OR REPLACE FUNCTION update_conversas_ultima_atividade()
RETURNS TRIGGER AS $$
BEGIN
    NEW.ultima_atividade = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- 2. Remover trigger antigo (se existir)
DROP TRIGGER IF EXISTS update_conversas_ultima_atividade ON conversas;

-- 3. Criar trigger correto
CREATE TRIGGER update_conversas_ultima_atividade
    BEFORE UPDATE ON conversas
    FOR EACH ROW
    EXECUTE FUNCTION update_conversas_ultima_atividade();

-- 4. Verificar se foi criado
SELECT 
    tgname as nome_trigger,
    tgtype as tipo,
    proname as funcao
FROM pg_trigger t
JOIN pg_proc p ON t.tgfoid = p.oid
WHERE tgrelid = 'conversas'::regclass;
