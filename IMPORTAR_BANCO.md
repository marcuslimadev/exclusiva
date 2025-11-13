# 🗄️ Como Importar o Schema no PostgreSQL (Render)

## Opção 1: Via Dashboard Render (Recomendado)

1. **Acesse o Render Dashboard:**
   - https://dashboard.render.com/
   - Vá em **Databases** > **exclusiva-mysql**

2. **Abra o Shell PSQL:**
   - Clique em **"Connect"** no topo direito
   - Selecione **"PSQL Command"**
   - Cole o comando e aperte Enter

3. **Execute o Schema:**
   - Abra o arquivo: `database/schema_postgres.sql`
   - Copie TODO o conteúdo
   - Cole no shell PSQL
   - Pressione Enter

4. **Verifique as tabelas criadas:**
   ```sql
   \dt
   ```

## Opção 2: Via psql Local (se instalado)

```powershell
# Instalar PostgreSQL Client no Windows
# https://www.postgresql.org/download/windows/

# Conectar ao banco
psql "postgresql://crm_exclusiva_user:M99kr2jyGMCANcR5k088oHwEdyfKZenw@dpg-d4at68er433s738idmq0-a.render.com/crm_exclusiva"

# No prompt do psql, executar:
\i C:/xampp/htdocs/imobi/database/schema_postgres.sql

# Verificar tabelas
\dt
```

## Opção 3: Via DBeaver / pgAdmin

1. **Criar nova conexão:**
   - Host: `dpg-d4at68er433s738idmq0-a.render.com`
   - Port: `5432`
   - Database: `crm_exclusiva`
   - User: `crm_exclusiva_user`
   - Password: `M99kr2jyGMCANcR5k088oHwEdyfKZenw`

2. **Executar o SQL:**
   - Abra o arquivo `schema_postgres.sql`
   - Execute (F5 ou Run)

## ✅ Verificação

Após importar, verifique:

```sql
-- Listar tabelas
\dt

-- Contar registros em users
SELECT COUNT(*) FROM users;

-- Deve retornar 2 (admin + corretor)
```

## 📋 Tabelas Criadas

Você deve ver estas tabelas:
- ✅ users (2 registros)
- ✅ leads
- ✅ conversas
- ✅ mensagens
- ✅ imo_properties
- ✅ lead_property_matches
- ✅ atividades

## 🔐 Credenciais Usuários Padrão

**Admin:**
- Email: `admin@exclusiva.com`
- Senha: `password`

**Corretor:**
- Email: `joao@exclusiva.com`
- Senha: `password`

---

**Próximo passo:** Após importar o schema, configurar o webhook do Twilio
