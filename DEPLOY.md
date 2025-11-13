# 🚀 Guia de Deploy - CRM Exclusiva

## 📦 Backend - Railway.app

### 1. Criar conta no Railway
- Acesse: https://railway.app/
- Faça login com GitHub

### 2. Criar novo projeto
```bash
1. Click "New Project"
2. Escolha "Deploy from GitHub repo"
3. Selecione: marcuslimadev/exclusiva
4. Railway detectará automaticamente o PHP
```

### 3. Configurar variáveis de ambiente
No dashboard do Railway, vá em "Variables" e adicione:

```env
# App
APP_NAME=Exclusiva-CRM
APP_ENV=production
APP_KEY=base64:SUA_KEY_AQUI
APP_DEBUG=false
APP_URL=https://seu-app.railway.app

# Database (Railway MySQL)
DB_CONNECTION=mysql
DB_HOST=${{MYSQLHOST}}
DB_PORT=${{MYSQLPORT}}
DB_DATABASE=${{MYSQLDATABASE}}
DB_USERNAME=${{MYSQLUSER}}
DB_PASSWORD=${{MYSQLPASSWORD}}

# Twilio
TWILIO_ACCOUNT_SID=seu_account_sid
TWILIO_AUTH_TOKEN=seu_auth_token
TWILIO_WHATSAPP_NUMBER=+5531XXXXXXXX

# OpenAI
OPENAI_API_KEY=sk-proj-xxxxx
```

### 4. Adicionar MySQL
```bash
1. No projeto Railway, click "New Service"
2. Selecione "Database" → "MySQL"
3. Railway criará automaticamente as variáveis ${{MYSQL*}}
```

### 5. Importar banco de dados
```bash
# Conecte via Railway CLI ou MySQL Workbench
mysql -h RAILWAY_HOST -P RAILWAY_PORT -u RAILWAY_USER -p RAILWAY_DATABASE < database/schema.sql
```

### 6. Deploy
```bash
# Railway fará deploy automático ao detectar push no GitHub
git push origin main

# Ou use Railway CLI:
railway up
```

### 7. Configurar domínio
```bash
1. No Railway, vá em "Settings" → "Networking"
2. Click "Generate Domain"
3. Anote a URL: https://seu-app.railway.app
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
VITE_API_URL=https://seu-backend.railway.app
```

### 3. Rebuild do frontend
```bash
cd frontend
npm run build
git add .
git commit -m "chore: Atualiza API URL para Railway"
git push origin main
```

---

## 📱 Configurar Webhook do Twilio

### 1. Obter URL do Railway
```
https://seu-backend.railway.app/webhook/whatsapp
```

### 2. Configurar no Twilio
```bash
1. Acesse: https://console.twilio.com/
2. Vá em: Messaging → Settings → WhatsApp Sandbox
3. Configure webhook:
   - URL: https://seu-backend.railway.app/webhook/whatsapp
   - Method: HTTP POST
4. Salve
```

### 3. Testar webhook
Envie uma mensagem WhatsApp para o número configurado e verifique os logs no Railway.

---

## 🔍 Monitoramento

### Railway (Backend)
```bash
1. Acesse o projeto no Railway
2. Vá em "Deployments"
3. Click no deploy ativo
4. Veja logs em tempo real
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
# Verifique logs no Railway
railway logs

# Teste localmente
php -S localhost:8000 -t public
```

### Frontend não conecta ao backend
```bash
# Verifique CORS
curl -I https://seu-backend.railway.app/webhook/whatsapp

# Teste API
curl https://seu-backend.railway.app/dashboard
```

### Webhook não responde
```bash
# Verifique logs do Twilio
# Verifique logs do Railway
# Teste webhook manualmente:
curl -X POST https://seu-backend.railway.app/webhook/whatsapp \
  -d "From=whatsapp:+5531999999999" \
  -d "Body=teste"
```

---

## ✅ Checklist Final

- [ ] Backend no Railway funcionando
- [ ] MySQL no Railway criado e importado
- [ ] Variáveis de ambiente configuradas no Railway
- [ ] Frontend no Vercel funcionando
- [ ] VITE_API_URL configurado no Vercel
- [ ] Webhook do Twilio configurado
- [ ] Teste de mensagem WhatsApp realizado
- [ ] Logs verificados (Railway + Vercel)
- [ ] Domínio customizado configurado (opcional)

---

## 🎉 Deploy Completo!

**URLs Finais:**
- Backend: https://seu-backend.railway.app
- Frontend: https://seu-frontend.vercel.app
- Webhook: https://seu-backend.railway.app/webhook/whatsapp

🚀 **Sistema no ar!**
