-- =============================================
-- SEED DATA - Dados de exemplo para testes
-- =============================================

-- =============================================
-- 1. IMÓVEIS DE EXEMPLO
-- =============================================

INSERT INTO imo_properties (codigo_imovel, referencia_imovel, finalidade_imovel, tipo_imovel, descricao, dormitorios, suites, banheiros, garagem, valor_venda, valor_aluguel, condominio, iptu, cidade, estado, bairro, endereco, numero, cep, area_privativa, area_total, em_condominio, nome_condominio, aceita_financiamento, exibir_imovel, destaque, active, caracteristicas) VALUES
('IMO001', 'AP-001', 'Venda', 'Apartamento', 'Lindo apartamento com 3 quartos no Savassi, próximo ao metrô e shopping. Acabamento de primeira, varanda gourmet, 2 vagas de garagem.', 3, 1, 2, 2, 450000.00, NULL, 800.00, 150.00, 'Belo Horizonte', 'MG', 'Savassi', 'Rua Pernambuco', '1500', '30130-150', 95.00, 110.00, 1, 'Edifício Excellence', 1, 1, 1, 1, 'Varanda gourmet, Piso porcelanato, Armários planejados, 2 vagas, Portaria 24h'),

('IMO002', 'AP-002', 'Venda', 'Apartamento', 'Apartamento compacto e moderno no Funcionários, ideal para investimento. 2 quartos, 1 vaga, área de lazer completa.', 2, 0, 1, 1, 320000.00, NULL, 550.00, 120.00, 'Belo Horizonte', 'MG', 'Funcionários', 'Rua da Bahia', '890', '30160-011', 65.00, 75.00, 1, 'Residencial Harmonia', 1, 1, 0, 1, 'Piscina, Academia, Churrasqueira, Salão de festas'),

('IMO003', 'CS-001', 'Venda', 'Casa', 'Casa espaçosa no Castelo com 4 quartos, quintal amplo, garagem para 3 carros. Perfeita para famílias grandes.', 4, 2, 3, 3, 850000.00, NULL, 0.00, 280.00, 'Belo Horizonte', 'MG', 'Castelo', 'Rua Professor Moraes', '420', '30840-030', 180.00, 250.00, 0, NULL, 1, 1, 1, 1, 'Quintal amplo, Churrasqueira, Edícula, Garagem coberta'),

('IMO004', 'AP-003', 'Aluguel', 'Apartamento', 'Apartamento mobiliado no Lourdes, 2 quartos, próximo a hospitais e universidades. Ideal para profissionais.', 2, 1, 2, 1, NULL, 2500.00, 650.00, 180.00, 'Belo Horizonte', 'MG', 'Lourdes', 'Rua Curitiba', '1200', '30170-120', 75.00, 85.00, 1, 'Edifício Santa Clara', 0, 1, 0, 1, 'Mobiliado, Ar condicionado, Varanda, Portaria 24h'),

('IMO005', 'CS-002', 'Venda', 'Casa', 'Casa de condomínio no Buritis, 3 quartos sendo 1 suíte, área gourmet, piscina. Condomínio com segurança 24h.', 3, 1, 2, 2, 680000.00, NULL, 450.00, 200.00, 'Belo Horizonte', 'MG', 'Buritis', 'Rua dos Aimorés', '350', '30575-180', 140.00, 180.00, 1, 'Condomínio Jardim das Acácias', 1, 1, 0, 1, 'Piscina, Área gourmet, Jardim, Segurança 24h'),

('IMO006', 'AP-004', 'Venda/Aluguel', 'Apartamento', 'Cobertura duplex no Belvedere, 4 suítes, vista panorâmica, 3 vagas. Alto padrão com acabamento luxuoso.', 4, 4, 5, 3, 1800000.00, 8000.00, 1200.00, 450.00, 'Belo Horizonte', 'MG', 'Belvedere', 'Avenida Alvares Cabral', '2800', '30170-001', 280.00, 320.00, 1, 'Edifício Privilege', 1, 1, 1, 1, 'Cobertura, Vista panorâmica, Terraço, Piscina privativa, 4 suítes'),

('IMO007', 'AP-005', 'Aluguel', 'Apartamento', 'Studio moderno no Centro, perfeito para quem trabalha na região. Mobiliado e equipado.', 1, 0, 1, 0, NULL, 1200.00, 0.00, 80.00, 'Belo Horizonte', 'MG', 'Centro', 'Rua Rio de Janeiro', '567', '30160-040', 35.00, 40.00, 0, NULL, 0, 1, 0, 1, 'Studio, Mobiliado, Equipado, Próximo ao metrô'),

('IMO008', 'CS-003', 'Venda', 'Casa', 'Sobrado no Santa Amélia, 3 quartos, churrasqueira, quintal. Rua tranquila, ótima localização.', 3, 1, 2, 2, 520000.00, NULL, 0.00, 180.00, 'Belo Horizonte', 'MG', 'Santa Amélia', 'Rua Lagoa Santa', '890', '31560-000', 120.00, 200.00, 0, NULL, 1, 1, 0, 1, 'Sobrado, Churrasqueira, Quintal, Garagem'),

('IMO009', 'AP-006', 'Venda', 'Apartamento', 'Apartamento novo no Sion, 2 quartos com suíte, varanda, 1 vaga. Prédio com lazer completo.', 2, 1, 2, 1, 380000.00, NULL, 600.00, 140.00, 'Belo Horizonte', 'MG', 'Sion', 'Rua Aimorés', '1450', '30315-370', 70.00, 80.00, 1, 'Residencial Parque Sion', 1, 1, 1, 1, 'Novo, Piscina, Academia, Playground'),

('IMO010', 'CS-004', 'Venda', 'Casa', 'Casa ampla no Mangabeiras, 5 quartos, piscina, área de lazer. Vista privilegiada da Serra do Curral.', 5, 3, 4, 4, 1500000.00, NULL, 0.00, 500.00, 'Belo Horizonte', 'MG', 'Mangabeiras', 'Rua do Ouro', '1200', '30210-040', 300.00, 450.00, 0, NULL, 1, 1, 1, 1, 'Piscina, Vista para serra, Área gourmet, 5 quartos');

-- =============================================
-- 2. LEADS COM DIFERENTES PERFIS
-- =============================================

INSERT INTO leads (telefone, nome, email, whatsapp_name, budget_min, budget_max, localizacao, quartos, suites, garagem, caracteristicas_desejadas, corretor_id, status, origem, score, primeira_interacao, ultima_interacao) VALUES
-- Lead 1: Procurando apartamento no Savassi (Match com IMO001)
('5531987654321', 'Maria Silva', 'maria.silva@email.com', 'Maria S.', 400000.00, 500000.00, 'Savassi', 3, 1, 2, 'Varanda, Próximo ao metrô, Condomínio com segurança', 2, 'qualificado', 'whatsapp', 85, NOW() - INTERVAL '3 days', NOW() - INTERVAL '1 hour'),

-- Lead 2: Investidor procurando apartamento pequeno (Match com IMO002)
('5531976543210', 'João Santos', 'joao.santos@email.com', 'João Santos', 250000.00, 350000.00, 'Funcionários', 2, 0, 1, 'Investimento, Área de lazer, Próximo a comércio', 2, 'em_atendimento', 'whatsapp', 75, NOW() - INTERVAL '2 days', NOW() - INTERVAL '3 hours'),

-- Lead 3: Família grande procurando casa (Match com IMO003)
('5531965432109', 'Ana Paula Costa', 'ana.costa@email.com', 'Ana Paula', 700000.00, 900000.00, 'Castelo', 4, 2, 3, 'Quintal amplo, Garagem para 3 carros, Área para pets', 2, 'proposta', 'whatsapp', 90, NOW() - INTERVAL '5 days', NOW() - INTERVAL '30 minutes'),

-- Lead 4: Profissional da saúde procurando aluguel (Match com IMO004)
('5531954321098', 'Dr. Carlos Mendes', 'carlos.mendes@email.com', 'Dr. Carlos', NULL, NULL, 'Lourdes', 2, 1, 1, 'Mobiliado, Próximo a hospitais, Ar condicionado', 2, 'fechado', 'whatsapp', 95, NOW() - INTERVAL '7 days', NOW() - INTERVAL '1 day'),

-- Lead 5: Casal jovem procurando primeiro imóvel (Match com IMO005)
('5531943210987', 'Rafael e Juliana', 'rafael.juliana@email.com', 'Rafa & Ju', 600000.00, 750000.00, 'Buritis', 3, 1, 2, 'Condomínio fechado, Piscina, Segurança', 2, 'qualificado', 'whatsapp', 80, NOW() - INTERVAL '4 days', NOW() - INTERVAL '2 hours'),

-- Lead 6: Executivo buscando alto padrão (Match com IMO006)
('5531932109876', 'Roberto Almeida', 'roberto.almeida@email.com', 'Roberto A.', 1500000.00, 2000000.00, 'Belvedere', 4, 4, 3, 'Cobertura, Vista panorâmica, Alto padrão', NULL, 'novo', 'whatsapp', 60, NOW() - INTERVAL '1 hour', NOW() - INTERVAL '1 hour'),

-- Lead 7: Estudante procurando aluguel (Match com IMO007)
('5531921098765', 'Fernanda Lima', 'fernanda.lima@email.com', 'Fer Lima', NULL, NULL, 'Centro', 1, 0, 0, 'Studio, Próximo ao metrô, Mobiliado', NULL, 'novo', 'whatsapp', 50, NOW() - INTERVAL '2 hours', NOW() - INTERVAL '2 hours'),

-- Lead 8: Família tradicional (Match com IMO008)
('5531910987654', 'Pedro Oliveira', 'pedro.oliveira@email.com', 'Pedro O.', 450000.00, 550000.00, 'Santa Amélia', 3, 1, 2, 'Casa, Quintal, Rua tranquila', 2, 'em_atendimento', 'whatsapp', 70, NOW() - INTERVAL '6 days', NOW() - INTERVAL '4 hours'),

-- Lead 9: Casal sem filhos (Match com IMO009)
('5531909876543', 'Lucas e Marina', 'lucas.marina@email.com', 'Lu & Mari', 350000.00, 420000.00, 'Sion', 2, 1, 1, 'Apartamento novo, Área de lazer, Varanda', 2, 'qualificado', 'whatsapp', 82, NOW() - INTERVAL '3 days', NOW() - INTERVAL '5 hours'),

-- Lead 10: Empresário (Match com IMO010)
('5531908765432', 'Gustavo Ferreira', 'gustavo.ferreira@email.com', 'Gustavo F.', 1200000.00, 1600000.00, 'Mangabeiras', 5, 3, 4, 'Casa grande, Piscina, Vista para serra', NULL, 'novo', 'whatsapp', 65, NOW() - INTERVAL '30 minutes', NOW() - INTERVAL '30 minutes'),

-- Lead 11: Perdido - desistiu
('5531897654321', 'Marcos Souza', 'marcos.souza@email.com', 'Marcos S.', 300000.00, 400000.00, 'Centro', 2, 0, 1, NULL, 2, 'perdido', 'whatsapp', 40, NOW() - INTERVAL '15 days', NOW() - INTERVAL '10 days');

-- =============================================
-- 3. CONVERSAS WHATSAPP
-- =============================================

INSERT INTO conversas (telefone, lead_id, corretor_id, status, stage, whatsapp_name, contexto_conversa, ultima_mensagem, iniciada_em, ultima_atividade) VALUES
-- Conversa 1: Lead qualificado - Maria Silva
('5531987654321', 1, 2, 'ativa', 'envio_propostas', 'Maria S.', 'Cliente procura apartamento 3 quartos no Savassi, orçamento 400-500k. Já enviado IMO001. Cliente gostou da localização e pediu para agendar visita.', 'Ótimo! Você pode me mostrar o apartamento amanhã às 14h?', NOW() - INTERVAL '3 days', NOW() - INTERVAL '1 hour'),

-- Conversa 2: Em atendimento - João Santos
('5531976543210', 2, 2, 'ativa', 'qualificacao', 'João Santos', 'Investidor buscando apartamento pequeno no Funcionários. Orçamento até 350k. Enviado IMO002.', 'Tem como me enviar mais fotos desse apartamento?', NOW() - INTERVAL '2 days', NOW() - INTERVAL '3 hours'),

-- Conversa 3: Proposta enviada - Ana Paula
('5531965432109', 3, 2, 'ativa', 'negociacao', 'Ana Paula', 'Família grande, 4 quartos, quintal. IMO003 enviado e proposta em análise. Cliente pediu desconto.', 'Conseguimos negociar para 820 mil?', NOW() - INTERVAL '5 days', NOW() - INTERVAL '30 minutes'),

-- Conversa 4: Fechado - Dr. Carlos
('5531954321098', 4, 2, 'finalizada', 'fechado', 'Dr. Carlos', 'Contrato de aluguel assinado. IMO004 - R$ 2.500/mês. Cliente satisfeito.', 'Obrigado! Já peguei as chaves hoje.', NOW() - INTERVAL '7 days', NOW() - INTERVAL '1 day'),

-- Conversa 5: Rafael e Juliana
('5531943210987', 5, 2, 'ativa', 'envio_propostas', 'Rafa & Ju', 'Casal jovem, primeira compra. IMO005 no Buritis. Cliente vai visitar no fim de semana.', 'Podemos visitar no sábado pela manhã?', NOW() - INTERVAL '4 days', NOW() - INTERVAL '2 hours'),

-- Conversa 6: Roberto - Novo lead
('5531932109876', 6, NULL, 'ativa', 'boas_vindas', 'Roberto A.', 'Lead novo, alto padrão, aguardando qualificação completa.', 'Olá! Estou procurando uma cobertura no Belvedere.', NOW() - INTERVAL '1 hour', NOW() - INTERVAL '1 hour'),

-- Conversa 7: Fernanda - Estudante
('5531921098765', 7, NULL, 'ativa', 'coleta_dados', 'Fer Lima', 'Estudante buscando aluguel, orçamento limitado.', 'Preciso de algo próximo à PUC.', NOW() - INTERVAL '2 hours', NOW() - INTERVAL '2 hours'),

-- Conversa 8: Pedro Oliveira
('5531910987654', 8, 2, 'ativa', 'qualificacao', 'Pedro O.', 'Família procurando casa no Santa Amélia. IMO008 apresentado.', 'Gostei da casa! Como funciona o financiamento?', NOW() - INTERVAL '6 days', NOW() - INTERVAL '4 hours'),

-- Conversa 9: Lucas e Marina
('5531909876543', 9, 2, 'ativa', 'envio_propostas', 'Lu & Mari', 'Casal sem filhos, apartamento no Sion. IMO009 enviado, aguardando feedback.', 'Esse apartamento do Sion parece perfeito!', NOW() - INTERVAL '3 days', NOW() - INTERVAL '5 hours'),

-- Conversa 10: Gustavo - Empresário
('5531908765432', 10, NULL, 'ativa', 'boas_vindas', 'Gustavo F.', 'Empresário, alto poder aquisitivo, casa no Mangabeiras.', 'Bom dia! Vi que vocês têm casas no Mangabeiras.', NOW() - INTERVAL '30 minutes', NOW() - INTERVAL '30 minutes'),

-- Conversa 11: Marcos - Perdido
('5531897654321', 11, 2, 'finalizada', 'perdido', 'Marcos S.', 'Cliente parou de responder após envio de propostas.', 'Vou pensar e te retorno.', NOW() - INTERVAL '15 days', NOW() - INTERVAL '10 days');

-- =============================================
-- 4. MENSAGENS WHATSAPP (Histórico detalhado)
-- =============================================

-- Conversa 1: Maria Silva (Qualificada)
INSERT INTO mensagens (conversa_id, direction, message_type, content, status, sent_at) VALUES
(1, 'incoming', 'text', 'Olá! Estou procurando apartamento', 'delivered', NOW() - INTERVAL '3 days'),
(1, 'outgoing', 'text', 'Olá Maria! Bem-vinda à Exclusiva Lar Imóveis! 🏠 Vou te ajudar a encontrar o imóvel ideal. Me conta: você está procurando para comprar ou alugar?', 'delivered', NOW() - INTERVAL '3 days' + INTERVAL '1 minute'),
(1, 'incoming', 'text', 'Quero comprar! 3 quartos, Savassi se possível', 'delivered', NOW() - INTERVAL '3 days' + INTERVAL '2 minutes'),
(1, 'outgoing', 'text', 'Perfeito! E qual seria sua faixa de orçamento?', 'delivered', NOW() - INTERVAL '3 days' + INTERVAL '3 minutes'),
(1, 'incoming', 'text', 'Entre 400 e 500 mil', 'delivered', NOW() - INTERVAL '3 days' + INTERVAL '4 minutes'),
(1, 'outgoing', 'text', 'Ótimo! Encontrei imóveis perfeitos para você! 🎯 Vou te enviar as opções:', 'delivered', NOW() - INTERVAL '3 days' + INTERVAL '5 minutes'),
(1, 'outgoing', 'text', '🏢 IMO001 - Apartamento no Savassi\n💰 R$ 450.000\n🛏️ 3 quartos (1 suíte)\n🚗 2 vagas\n📍 Rua Pernambuco, 1500', 'delivered', NOW() - INTERVAL '3 days' + INTERVAL '6 minutes'),
(1, 'incoming', 'text', 'Nossa, esse do Savassi parece perfeito!', 'delivered', NOW() - INTERVAL '2 days'),
(1, 'outgoing', 'text', 'Que ótimo! 😊 Quer agendar uma visita?', 'delivered', NOW() - INTERVAL '2 days' + INTERVAL '1 minute'),
(1, 'incoming', 'text', 'Ótimo! Você pode me mostrar o apartamento amanhã às 14h?', 'delivered', NOW() - INTERVAL '1 hour');

-- Conversa 2: João Santos (Em atendimento)
INSERT INTO mensagens (conversa_id, direction, message_type, content, status, sent_at) VALUES
(2, 'incoming', 'text', 'Oi, busco apartamento para investir', 'delivered', NOW() - INTERVAL '2 days'),
(2, 'outgoing', 'text', 'Olá João! Bem-vindo! 🏠 Ótima escolha investir em imóveis. Qual região você prefere e qual seu orçamento?', 'delivered', NOW() - INTERVAL '2 days' + INTERVAL '1 minute'),
(2, 'incoming', 'text', 'Funcionários, até 350k. 2 quartos tá bom', 'delivered', NOW() - INTERVAL '2 days' + INTERVAL '3 minutes'),
(2, 'outgoing', 'text', 'Perfeito! Tenho uma ótima opção:\n\n🏢 IMO002 - Ap. Funcionários\n💰 R$ 320.000\n🛏️ 2 quartos\n🚗 1 vaga\n🏊 Área de lazer completa', 'delivered', NOW() - INTERVAL '2 days' + INTERVAL '5 minutes'),
(2, 'incoming', 'text', 'Tem como me enviar mais fotos desse apartamento?', 'delivered', NOW() - INTERVAL '3 hours');

-- Conversa 3: Ana Paula (Proposta)
INSERT INTO mensagens (conversa_id, direction, message_type, content, status, sent_at) VALUES
(3, 'incoming', 'text', 'Procuro casa grande, tenho 3 filhos', 'delivered', NOW() - INTERVAL '5 days'),
(3, 'outgoing', 'text', 'Olá Ana Paula! Que maravilha! Família grande precisa de espaço mesmo. Me conta: quantos quartos você precisa?', 'delivered', NOW() - INTERVAL '5 days' + INTERVAL '1 minute'),
(3, 'incoming', 'text', '4 quartos, quintal para as crianças brincarem', 'delivered', NOW() - INTERVAL '5 days' + INTERVAL '2 minutes'),
(3, 'outgoing', 'text', 'Perfeito! E a faixa de preço?', 'delivered', NOW() - INTERVAL '5 days' + INTERVAL '3 minutes'),
(3, 'incoming', 'text', 'Até 900 mil mais ou menos', 'delivered', NOW() - INTERVAL '5 days' + INTERVAL '4 minutes'),
(3, 'outgoing', 'text', '🏡 Encontrei a casa ideal!\n\nIMO003 - Casa no Castelo\n💰 R$ 850.000\n🛏️ 4 quartos (2 suítes)\n🚗 3 vagas\n🌳 Quintal amplo com churrasqueira', 'delivered', NOW() - INTERVAL '4 days'),
(3, 'incoming', 'text', 'Adorei! Podemos visitar?', 'delivered', NOW() - INTERVAL '3 days'),
(3, 'outgoing', 'text', 'Claro! Visitamos ontem, lembra? Você gostou bastante! Quer fazer uma proposta?', 'delivered', NOW() - INTERVAL '2 days'),
(3, 'incoming', 'text', 'Conseguimos negociar para 820 mil?', 'delivered', NOW() - INTERVAL '30 minutes');

-- Conversa 4: Dr. Carlos (Fechado)
INSERT INTO mensagens (conversa_id, direction, message_type, content, status, sent_at) VALUES
(4, 'incoming', 'text', 'Preciso alugar urgente, trabalho no hospital', 'delivered', NOW() - INTERVAL '7 days'),
(4, 'outgoing', 'text', 'Olá Dr. Carlos! Entendo a urgência. Qual região você prefere? Próximo ao hospital?', 'delivered', NOW() - INTERVAL '7 days' + INTERVAL '1 minute'),
(4, 'incoming', 'text', 'Sim, Lourdes seria ideal. Mobiliado de preferência', 'delivered', NOW() - INTERVAL '7 days' + INTERVAL '2 minutes'),
(4, 'outgoing', 'text', '🏢 Tenho uma opção PERFEITA!\n\nIMO004 - Ap. Mobiliado Lourdes\n💰 R$ 2.500/mês\n🛏️ 2 quartos (1 suíte)\n❄️ Ar condicionado\n📍 Rua Curitiba, 1200', 'delivered', NOW() - INTERVAL '7 days' + INTERVAL '5 minutes'),
(4, 'incoming', 'text', 'Perfeito! Quando posso ver?', 'delivered', NOW() - INTERVAL '6 days'),
(4, 'outgoing', 'text', 'Hoje mesmo se quiser! Às 17h?', 'delivered', NOW() - INTERVAL '6 days' + INTERVAL '10 minutes'),
(4, 'incoming', 'text', 'Fechado! Quero alugar', 'delivered', NOW() - INTERVAL '5 days'),
(4, 'outgoing', 'text', 'Excelente! Vou preparar o contrato. Parabéns pela nova casa! 🎉', 'delivered', NOW() - INTERVAL '5 days' + INTERVAL '5 minutes'),
(4, 'incoming', 'text', 'Obrigado! Já peguei as chaves hoje.', 'delivered', NOW() - INTERVAL '1 day');

-- Conversa 6: Roberto - Lead novo
INSERT INTO mensagens (conversa_id, direction, message_type, content, status, sent_at) VALUES
(6, 'incoming', 'text', 'Olá! Estou procurando uma cobertura no Belvedere.', 'delivered', NOW() - INTERVAL '1 hour'),
(6, 'outgoing', 'text', 'Olá Roberto! Bem-vindo à Exclusiva Lar Imóveis! 🏢 Excelente escolha, o Belvedere é uma região nobre. Quantos quartos você procura?', 'delivered', NOW() - INTERVAL '1 hour' + INTERVAL '2 minutes');

-- =============================================
-- 5. MATCHES LEAD x IMÓVEL
-- =============================================

INSERT INTO lead_property_matches (lead_id, property_id, conversa_id, match_score, pdf_sent, pdf_sent_at, visualizado, interesse) VALUES
-- Maria Silva (Lead 1) - IMO001
(1, 1, 1, 95.50, 1, NOW() - INTERVAL '3 days', 1, 'alto'),

-- João Santos (Lead 2) - IMO002
(2, 2, 2, 88.20, 1, NOW() - INTERVAL '2 days', 1, 'medio'),

-- Ana Paula (Lead 3) - IMO003
(3, 3, 3, 92.00, 1, NOW() - INTERVAL '4 days', 1, 'alto'),

-- Dr. Carlos (Lead 4) - IMO004 (Fechado)
(4, 4, 4, 98.00, 1, NOW() - INTERVAL '7 days', 1, 'alto'),

-- Rafael e Juliana (Lead 5) - IMO005
(5, 5, 5, 90.30, 1, NOW() - INTERVAL '4 days', 1, 'alto'),

-- Roberto (Lead 6) - IMO006
(6, 6, 6, 85.00, 0, NULL, 0, NULL),

-- Fernanda (Lead 7) - IMO007
(7, 7, 7, 80.00, 0, NULL, 0, NULL),

-- Pedro (Lead 8) - IMO008
(8, 8, 8, 87.50, 1, NOW() - INTERVAL '5 days', 1, 'medio'),

-- Lucas e Marina (Lead 9) - IMO009
(9, 9, 9, 91.20, 1, NOW() - INTERVAL '3 days', 1, 'alto'),

-- Gustavo (Lead 10) - IMO010
(10, 10, 10, 82.00, 0, NULL, 0, NULL);

-- =============================================
-- 6. ATIVIDADES DO SISTEMA
-- =============================================

INSERT INTO atividades (user_id, lead_id, tipo, descricao, ip_address) VALUES
-- Atividades do corretor João
(2, 1, 'lead_criado', 'Lead Maria Silva criado via WhatsApp', '177.55.234.12'),
(2, 1, 'imovel_enviado', 'IMO001 enviado para Maria Silva', '177.55.234.12'),
(2, 1, 'visita_agendada', 'Visita agendada para 14h de amanhã', '177.55.234.12'),

(2, 2, 'lead_criado', 'Lead João Santos criado via WhatsApp', '177.55.234.12'),
(2, 2, 'imovel_enviado', 'IMO002 enviado para João Santos', '177.55.234.12'),

(2, 3, 'lead_criado', 'Lead Ana Paula Costa criado via WhatsApp', '177.55.234.12'),
(2, 3, 'imovel_enviado', 'IMO003 enviado para Ana Paula', '177.55.234.12'),
(2, 3, 'visita_realizada', 'Visita realizada no IMO003', '177.55.234.12'),
(2, 3, 'proposta_enviada', 'Proposta de R$ 850.000 enviada', '177.55.234.12'),

(2, 4, 'lead_criado', 'Lead Dr. Carlos criado via WhatsApp', '177.55.234.12'),
(2, 4, 'imovel_enviado', 'IMO004 enviado para Dr. Carlos', '177.55.234.12'),
(2, 4, 'visita_realizada', 'Visita realizada no IMO004', '177.55.234.12'),
(2, 4, 'contrato_assinado', 'Contrato de aluguel assinado - R$ 2.500/mês', '177.55.234.12'),

(2, 5, 'lead_criado', 'Lead Rafael e Juliana criado via WhatsApp', '177.55.234.12'),
(2, 5, 'imovel_enviado', 'IMO005 enviado para Rafael e Juliana', '177.55.234.12'),

-- Atividades do admin
(1, NULL, 'login', 'Admin acessou o sistema', '177.55.234.10'),
(1, NULL, 'relatorio_gerado', 'Relatório mensal de vendas gerado', '177.55.234.10');

-- =============================================
-- RESUMO DOS DADOS INSERIDOS
-- =============================================
-- ✅ 10 Imóveis (variados: apartamentos, casas, venda, aluguel)
-- ✅ 11 Leads (diferentes perfis e estágios: novo, qualificado, proposta, fechado, perdido)
-- ✅ 11 Conversas WhatsApp (com históricos realistas)
-- ✅ 45+ Mensagens (diálogos completos simulando atendimento real)
-- ✅ 10 Matches Lead x Imóvel (com scores de compatibilidade)
-- ✅ 16 Atividades (log de ações do sistema)

SELECT 'Seed data inserido com sucesso! 🎉' as status;
