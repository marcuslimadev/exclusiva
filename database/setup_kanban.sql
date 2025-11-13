-- ================================================================
-- Script para configurar Kanban de Leads
-- Execute este script no Render PostgreSQL Console
-- ================================================================

-- 1. Atualizar leads de seed com estados brasileiros
UPDATE leads SET state = 'MG', city = 'Belo Horizonte', localizacao = 'Belo Horizonte, MG' WHERE id = 1;
UPDATE leads SET state = 'SP', city = 'São Paulo', localizacao = 'São Paulo, SP' WHERE id = 2;
UPDATE leads SET state = 'RJ', city = 'Rio de Janeiro', localizacao = 'Rio de Janeiro, RJ' WHERE id = 3;
UPDATE leads SET state = 'MG', city = 'Belo Horizonte', localizacao = 'Belo Horizonte, MG' WHERE id = 4;
UPDATE leads SET state = 'SP', city = 'Campinas', localizacao = 'Campinas, SP' WHERE id = 5;
UPDATE leads SET state = 'MG', city = 'Contagem', localizacao = 'Contagem, MG' WHERE id = 6;
UPDATE leads SET state = 'RJ', city = 'Niterói', localizacao = 'Niterói, RJ' WHERE id = 7;
UPDATE leads SET state = 'SP', city = 'Santos', localizacao = 'Santos, SP' WHERE id = 8;
UPDATE leads SET state = 'MG', city = 'Uberlândia', localizacao = 'Uberlândia, MG' WHERE id = 9;
UPDATE leads SET state = 'RJ', city = 'Petrópolis', localizacao = 'Petrópolis, RJ' WHERE id = 10;
UPDATE leads SET state = 'SP', city = 'Sorocaba', localizacao = 'Sorocaba, SP' WHERE id = 11;
UPDATE leads SET state = 'AM', city = 'Manaus', localizacao = 'Manaus, AM' WHERE id = 12;

-- 2. Verificar resultados
SELECT id, nome, city, state, status FROM leads ORDER BY state, id;

-- 3. (OPCIONAL) Limpar conversas e mensagens antigas para testes
-- DESCOMENTE as linhas abaixo se quiser limpar os dados:
-- DELETE FROM mensagens;
-- DELETE FROM conversas;
-- DELETE FROM leads WHERE origem = 'whatsapp' AND id > 11;
-- ALTER SEQUENCE mensagens_id_seq RESTART WITH 1;
-- ALTER SEQUENCE conversas_id_seq RESTART WITH 46;
-- ALTER SEQUENCE leads_id_seq RESTART WITH 13;
