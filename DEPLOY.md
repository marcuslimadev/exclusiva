# 🚀 Guia de Deploy - CRM Exclusiva

## 📦 Backend - Render.com

### 1. Criar conta no Render
- Acesse: https://render.com/
- Faça login com GitHub

### 2. Criar Web Service
```bash
1. Click "New +" → "Web Service"
2. Connect ao GitHub: marcuslimadev/exclusiva
3. Configure:
   - Name: exclusiva-backend
   - Environment: Docker
   - Branch: main
   - Root Directory: backend
```

### 3. Criar MySQL Database
```bash
1. No dashboard Render, click "New +" → "PostgreSQL"
   (Render não tem MySQL free, use PostgreSQL)
2. Name: exclusiva-db
3. Database: crm_exclusiva
4. User: exclusiva_user
5. Aguarde provisionar
6. Copie a "Internal Database URL"
```

### 4. Configurar variáveis de ambiente
No Web Service criado, vá em "Environment" e adicione:

```env
# App
APP_NAME=Exclusiva-CRM
APP_ENV=production
APP_DEBUG=false
APP_URL=https://seu-app.onrender.com

# Database (copie da Internal Database URL)
DATABASE_URL=postgresql://user:pass@host:5432/crm_exclusiva

# Twilio
TWILIO_ACCOUNT_SID=seu_account_sid
TWILIO_AUTH_TOKEN=seu_auth_token
TWILIO_WHATSAPP_NUMBER=+5531XXXXXXXX

# OpenAI
OPENAI_API_KEY=sk-proj-xxxxx
```

### 5. Importar banco de dados
```bash
# Conecte via psql ou DBeaver
psql $DATABASE_URL < database/schema_postgres.sql

# Ou via Render dashboard:
1. Vá no PostgreSQL criado
2. Click "Connect" → "External Connection"
3. Use credenciais no MySQL Workbench/DBeaver
```

### 6. Deploy
```bash
# Render fará deploy automático ao detectar push no GitHub
git push origin main

# O Dockerfile será usado automaticamente
```

### 7. Verificar deploy
```bash
1. No Render dashboard, vá no Web Service
2. Click em "Logs" para ver o build
3. Aguarde "Live" aparecer
4. Anote a URL: https://seu-app.onrender.com
```

---

## 🎨 Frontend - Vercel

### 1. Criar conta no Vercel
- Acesse: https://vercel.com/
- Faça login com GitHub

### 2. Importar projeto
```bash
1. Click "Add New Project"
2. Import: marcuslimadev/exclusiva
3. Configure:
   - Framework Preset: Vite
   - Root Directory: frontend
   - Build Command: npm run build
   - Output Directory: dist
```

### 3. Configurar variáveis de ambiente
No dashboard Vercel, vá em "Settings" → "Environment Variables":

```env
VITE_API_URL=https://seu-backend.railway.app
```

### 4. Atualizar API URL no frontend
Edite `frontend/src/services/api.js`:
```javascript
const API_URL = import.meta.env.VITE_API_URL || 'https://seu-backend.railway.app';
```

### 4. Atualizar variável de ambiente
No Vercel, adicione:
```env
VITE_API_URL=https://seu-backend.onrender.com
```

### 5. Deploy
```bash
# Vercel fará deploy automático ao detectar push
git push origin main

# Ou via CLI:
vercel --prod
```

### 6. Domínio customizado (opcional)
```bash
1. No Vercel, vá em "Settings" → "Domains"
2. Adicione seu domínio
3. Configure DNS conforme instruções
```

---

## 🔗 Conectar Frontend → Backend

### 1. Configurar CORS no backend
O arquivo `backend/app/Http/Middleware/CorsMiddleware.php` já está configurado para aceitar todas as origens em produção.

### 2. Atualizar .env do frontend
```env
VITE_API_URL=https://seu-backend.onrender.com
```

### 3. Rebuild do frontend
```bash
cd frontend
npm run build
git add .
git commit -m "chore: Atualiza API URL para Render"
git push origin main
```

---

## 📱 Configurar Webhook do Twilio

### 1. Obter URL do Render
```
https://seu-backend.onrender.com/webhook/whatsapp
```

### 2. Configurar no Twilio
```bash
1. Acesse: https://console.twilio.com/
2. Vá em: Messaging → Settings → WhatsApp Sandbox
3. Configure webhook:
   - URL: https://seu-backend.onrender.com/webhook/whatsapp
   - Method: HTTP POST
4. Salve
```

### 3. Testar webhook
Envie uma mensagem WhatsApp para o número configurado e verifique os logs no Railway.

---

## 🔍 Monitoramento

### Render (Backend)
```bash
1. Acesse o Web Service no Render
2. Click em "Logs"
3. Veja logs em tempo real
4. Métricas em "Metrics"
```

### Vercel (Frontend)
```bash
1. Acesse o projeto no Vercel
2. Vá em "Deployments"
3. Click no deploy ativo
4. Veja logs e analytics
```

---

## 🐛 Troubleshooting

### Backend não inicia
```bash
# Verifique logs no Render dashboard
# Vá em: Web Service → Logs

# Teste Docker localmente:
cd backend
docker build -t exclusiva-backend .
docker run -p 8080:8080 exclusiva-backend
```

### Frontend não conecta ao backend
```bash
# Verifique CORS
curl -I https://seu-backend.onrender.com/webhook/whatsapp

# Teste API
curl https://seu-backend.onrender.com/dashboard
```

### Webhook não responde
```bash
# Verifique logs do Twilio
# Verifique logs do Render
# Teste webhook manualmente:
curl -X POST https://seu-backend.onrender.com/webhook/whatsapp \
  -d "From=whatsapp:+5531999999999" \
  -d "Body=teste"

# ⚠️ Render free tier hiberna após 15min de inatividade
# Primeira chamada pode demorar 30s para "acordar"
```

---

## ✅ Checklist Final

- [ ] Backend no Render funcionando
- [ ] PostgreSQL no Render criado e importado
- [ ] Variáveis de ambiente configuradas no Render
- [ ] Frontend no Vercel funcionando
- [ ] VITE_API_URL configurado no Vercel
- [ ] Webhook do Twilio configurado
- [ ] Teste de mensagem WhatsApp realizado
- [ ] Logs verificados (Render + Vercel)
- [ ] Domínio customizado configurado (opcional)

## ⚠️ Limitações Render Free Tier

- **Sleep após 15min**: Primeira chamada demora ~30s
- **750h/mês**: ~31 dias se ficar sempre ativo
- **PostgreSQL**: 90 dias de retenção
- **Solução**: Usar cron job para manter ativo (ex: UptimeRobot)

---

## 🎉 Deploy Completo!

**URLs Finais:**
- Backend: https://seu-backend.onrender.com
- Frontend: https://seu-frontend.vercel.app
- Webhook: https://seu-backend.onrender.com/webhook/whatsapp

🚀 **Sistema no ar!**

## 🔄 Manter Backend Ativo (Opcional)

Para evitar hibernação do Render:

1. **UptimeRobot** (gratuito):
   - Crie conta: https://uptimerobot.com/
   - Adicione monitor HTTP(S)
   - URL: https://seu-backend.onrender.com/
   - Intervalo: 5 minutos

2. **Cron-Job.org** (alternativa):
   - Acesse: https://cron-job.org/
   - Configure job para chamar sua URL a cada 5min
