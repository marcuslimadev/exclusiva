# 🧪 Guia de Teste - Novas Funcionalidades

## 1. Testar Sincronização de Imóveis

### Via API (Recomendado)
```bash
curl https://exclusiva-backend.onrender.com/api/properties/sync
```

**Resposta Esperada:**
```json
{
  "success": true,
  "message": "Sincronização concluída com sucesso",
  "data": {
    "found": 150,
    "new": 5,
    "updated": 145,
    "errors": 0
  },
  "time_ms": 45230.5
}
```

### Verificar no Banco de Dados
```sql
-- Total de imóveis
SELECT COUNT(*) FROM imo_properties;

-- Imóveis ativos
SELECT COUNT(*) FROM imo_properties WHERE active = 1;

-- Últimos 5 imóveis sincronizados
SELECT codigo_imovel, tipo_imovel, cidade, bairro, valor_venda, updated_at 
FROM imo_properties 
ORDER BY updated_at DESC 
LIMIT 5;
```

---

## 2. Testar Áudio para Texto

### Passo a Passo:

1. **Enviar áudio pelo WhatsApp:**
   - Abra o WhatsApp
   - Envie mensagem para: `+55 31 7334-1150`
   - Grave um áudio de voz dizendo: *"Quero um apartamento de 3 quartos em BH"*

2. **Verificar Transcrição:**

```sql
-- Última mensagem de áudio
SELECT 
    id,
    content,
    transcription,
    message_type,
    sent_at
FROM mensagens 
WHERE message_type = 'audio' 
ORDER BY sent_at DESC 
LIMIT 1;
```

**Resultado Esperado:**
| Campo | Valor |
|-------|-------|
| content | `[vazio ou ""]` |
| transcription | `"Quero um apartamento de 3 quartos em BH"` |
| message_type | `audio` |

3. **Verificar Resposta da IA:**

A IA deve processar o texto transcrito e responder normalmente, por exemplo:
> *"Ótimo! Vou te ajudar a encontrar um apartamento de 3 quartos em BH. Qual é o seu orçamento aproximado?"*

---

## 3. Testar Fallback da IA

### Cenários de Teste:

#### ✅ Teste 1: Pergunta Técnica/Jurídica
**Cliente envia:**
```
"Qual a taxa de juros do financiamento habitacional?"
```

**Resposta Esperada da IA:**
```
"Vou encaminhar sua dúvida para um dos nossos corretores especializados. Em breve entraremos em contato! 📱"
```

#### ✅ Teste 2: Pergunta Fora do Contexto
**Cliente envia:**
```
"Qual o melhor restaurante em BH?"
```

**Resposta Esperada da IA:**
```
"Vou encaminhar sua dúvida para um dos nossos corretores especializados. Em breve entraremos em contato! 📱"
```

#### ✅ Teste 3: Pergunta Muito Específica
**Cliente envia:**
```
"O imóvel da Rua X, 123 tem vaga de garagem coberta ou descoberta?"
```

**Resposta Esperada da IA:**
```
"Vou encaminhar sua dúvida para um dos nossos corretores especializados. Em breve entraremos em contato! 📱"
```

#### ✅ Teste 4: Pergunta Normal (NÃO deve usar fallback)
**Cliente envia:**
```
"Procuro apartamento de 2 quartos"
```

**Resposta Esperada da IA:**
```
"Ótimo! Vou te ajudar. Qual região você prefere e qual seu orçamento?"
```

---

## 4. Teste Completo (E2E)

### Fluxo Completo de Atendimento com Áudio:

1. **Primeira Mensagem (Texto):**
   ```
   Cliente: "Oi"
   IA: "Olá! Bem-vindo à Exclusiva Lar Imóveis! 🏡 Como posso te ajudar hoje?"
   ```

2. **Segunda Mensagem (Áudio):**
   ```
   Cliente: [áudio] "Estou procurando um apartamento de 3 quartos"
   Sistema: [transcreve o áudio]
   IA: "Entendi! Qual é o seu orçamento aproximado?"
   ```

3. **Terceira Mensagem (Texto):**
   ```
   Cliente: "Entre 400 e 500 mil"
   IA: "Perfeito! E em qual região você prefere?"
   ```

4. **Quarta Mensagem (Pergunta fora do escopo):**
   ```
   Cliente: "Vocês fazem financiamento direto?"
   IA: "Vou encaminhar sua dúvida para um dos nossos corretores especializados. Em breve entraremos em contato! 📱"
   ```

---

## 5. Monitorar Logs em Tempo Real

### No Render (Backend):

1. Acesse: https://dashboard.render.com
2. Selecione o serviço `exclusiva-backend`
3. Clique em **Logs**
4. Procure por:
   - `🏠 Iniciando sincronização de imóveis...`
   - `🎤 Áudio detectado, iniciando transcrição`
   - `📨 Processando mensagem regular`
   - `🤖 Resposta da IA`

### Comandos úteis:

```bash
# Filtrar logs de sincronização
grep "SYNC" storage/logs/lumen.log

# Filtrar logs de áudio
grep "AUDIO" storage/logs/lumen.log

# Últimas 50 linhas do log
tail -50 storage/logs/lumen.log
```

---

## 6. Verificar Cron Job

### Render Auto-Schedule

O Render executa automaticamente o cron job configurado em `Kernel.php`.

**Para verificar se está rodando:**

1. Aguarde 4 horas após o deploy
2. Verifique os logs no Render
3. Procure por: `🏠 Iniciando sincronização de imóveis...`

**Forçar execução manual:**

```bash
# Via SSH no Render (se disponível)
php artisan properties:sync

# Ou via endpoint
curl https://exclusiva-backend.onrender.com/api/properties/sync
```

---

## 7. Checklist de Validação

### ✅ Sincronização de Imóveis
- [ ] Endpoint `/api/properties/sync` funciona
- [ ] Retorna estatísticas (found, new, updated, errors)
- [ ] Imóveis aparecem na tabela `imo_properties`
- [ ] Campos `updated_at` estão sendo atualizados
- [ ] Cron job executa automaticamente (aguardar 4h)

### ✅ Áudio para Texto
- [ ] Áudio enviado pelo WhatsApp é recebido
- [ ] Sistema detecta tipo "audio"
- [ ] Áudio é transcrito corretamente
- [ ] Transcrição é salva no campo `transcription`
- [ ] IA processa o texto transcrito
- [ ] Resposta é enviada normalmente

### ✅ Fallback da IA
- [ ] Perguntas técnicas → Fallback ativado
- [ ] Perguntas fora do contexto → Fallback ativado
- [ ] Perguntas normais → IA responde normalmente
- [ ] Mensagem de fallback está correta
- [ ] Fallback NÃO é usado quando não deveria

---

## 8. Troubleshooting

### ❌ Sincronização não funciona

**Erro:** `EXCLUSIVA_API_TOKEN não configurado`

**Solução:**
```bash
# Adicionar no .env do Render
EXCLUSIVA_API_TOKEN=seu_token_aqui
```

---

### ❌ Áudio não é transcrito

**Erro:** `OpenAI API Key inválida`

**Solução:**
```bash
# Verificar .env
OPENAI_API_KEY=sk-proj-...
OPENAI_MODEL=gpt-4o-mini
```

**Erro:** `Diretório temp não existe`

**Solução:**
```bash
mkdir -p storage/app/temp
chmod 777 storage/app/temp
```

---

### ❌ Fallback não está funcionando

**Problema:** IA responde informações incorretas em vez de usar fallback

**Solução:** O system prompt foi atualizado. Fazer redeploy:
```bash
git push origin main
```

---

## 9. Testes Automatizados (Futuro)

### Script de Teste Automatizado:

```bash
#!/bin/bash

echo "🧪 Iniciando testes..."

# Teste 1: Sincronização
echo "1️⃣ Testando sincronização..."
curl -s https://exclusiva-backend.onrender.com/api/properties/sync | jq .

# Teste 2: Verificar imóveis
echo "2️⃣ Verificando imóveis no banco..."
psql $DATABASE_URL -c "SELECT COUNT(*) FROM imo_properties;"

# Teste 3: Enviar mensagem de teste (via Twilio)
echo "3️⃣ Enviando mensagem de teste..."
curl -X POST https://exclusiva-backend.onrender.com/webhook/whatsapp \
  -d "From=whatsapp:+5531999999999" \
  -d "Body=teste automatizado" \
  -d "ProfileName=Teste Bot"

echo "✅ Testes concluídos!"
```

---

## 📊 Métricas de Sucesso

| Métrica | Objetivo | Como Medir |
|---------|----------|------------|
| Sincronização bem-sucedida | 100% | `errors: 0` na resposta |
| Áudios transcritos | >95% | `SELECT COUNT(*) WHERE transcription IS NOT NULL` |
| Fallback usado corretamente | >90% | Análise manual de conversas |
| Tempo de sincronização | <60s | `time_ms` na resposta |

---

## ✅ Conclusão

Após seguir este guia, você deve ter validado:
1. ✅ Sincronização automática de imóveis funcionando
2. ✅ Áudio sendo convertido para texto com Whisper
3. ✅ Fallback inteligente ativando quando necessário
4. ✅ Sistema completo funcionando end-to-end
