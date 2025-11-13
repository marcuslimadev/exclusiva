-- Script para limpar conversas e reiniciar testes
-- Executar no Render: https://dashboard.render.com/d/dpg-d4at68er433s738idmq0

-- 1. Deletar todas as mensagens
DELETE FROM mensagens;

-- 2. Deletar todas as conversas
DELETE FROM conversas;

-- 3. Deletar leads criados pelo WhatsApp (manter seed data)
DELETE FROM leads WHERE origem = 'whatsapp' AND id > 11;

-- 4. Resetar sequences (opcional)
ALTER SEQUENCE mensagens_id_seq RESTART WITH 1;
ALTER SEQUENCE conversas_id_seq RESTART WITH 46;
ALTER SEQUENCE leads_id_seq RESTART WITH 13;

-- Resultado: Banco limpo, pronto para novos testes!
