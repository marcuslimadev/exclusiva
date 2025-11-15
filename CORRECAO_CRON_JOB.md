# ✅ CORREÇÃO DO CRON JOB - Sync Worker

## 🐛 Problema Identificado

O cron job automático estava **zerando os imóveis** porque executava o arquivo `backend/sync_worker.php` antigo que usava:
- ❌ Classe `SyncImoveis.php` desatualizada
- ❌ Schema MySQL antigo (`imoveis` table)
- ❌ Função `pdo()` inexistente no Lumen

## ✅ Solução Implementada

### **Arquivo Corrigido: `backend/sync_worker.php`**

**Mudanças aplicadas:**
1. ✅ Usa **Laravel DB facade** ao invés de PDO
2. ✅ Schema **PostgreSQL** correto (`imo_properties` table)
3. ✅ **Duas fases** de sincronização preservadas
4. ✅ **Mapping de finalidade** (`Locação` → `Aluguel`)
5. ✅ **Latitude/Longitude** incluídos (para o mapa)

### **Versão Atual**
```
📌 Versão: 3.0 - Backend Lumen + PostgreSQL
```

---

## 🔄 Como Funciona Agora

### **FASE 1: Lista Completa**
```php
// Percorre TODAS as páginas da API
GET /lista?status=ativo&page=1&per_page=100

// Salva dados básicos em imo_properties
updateOrInsert(['codigo_imovel' => $codigo], $data)
```

**Campos salvos na Fase 1:**
- `codigo_imovel`
- `referencia_imovel`
- `finalidade_imovel` (com mapping)
- `tipo_imovel`
- `active`
- `updated_at`

### **FASE 2: Detalhes**
```php
// Busca detalhes apenas dos imóveis que precisam:
WHERE descricao IS NULL 
   OR cidade IS NULL 
   OR updated_at < (NOW() - 4 HOURS)

// Atualiza com dados completos
GET /dados/{codigo}
```

**Campos atualizados na Fase 2:**
- ✅ Características do imóvel (dormitorios, suites, banheiros, garagem)
- ✅ Valores (venda/aluguel baseado em finalidade)
- ✅ Taxas (IPTU, condomínio)
- ✅ Endereço completo (cidade, estado, bairro, logradouro, CEP)
- ✅ **Coordenadas (latitude, longitude)** ← NOVO!
- ✅ Áreas (privativa, total, terreno)
- ✅ Descrição completa
- ✅ Imagens (JSON array com destaque)
- ✅ Características (JSON array)
- ✅ Flags (em_condominio, aceita_financiamento, exibir_imovel)

---

## 🗺️ Coordenadas para o Mapa

Agora o sync worker **salva latitude e longitude**:

```php
'latitude' => $d['endereco']['latitude'] ?? null,
'longitude' => $d['endereco']['longitude'] ?? null,
```

Isso permite que o componente `PropertyMap.vue` exiba os imóveis corretamente!

---

## 🚀 Endpoint do Cron Job

### **URL Atual**
```
GET https://exclusiva-backend.onrender.com/api/properties/sync-worker
```

### **Arquivo Executado**
```
c:\xampp\htdocs\imobi\backend\sync_worker.php
```

### **Configuração em `routes/web.php`**
```php
$router->get('/sync-worker', function () {
    set_time_limit(300); // 5 minutos
    
    $workerPath = base_path('sync_worker.php');
    
    if (!file_exists($workerPath)) {
        return response()->json([
            'success' => false,
            'message' => 'sync_worker.php não encontrado'
        ], 404);
    }
    
    exec("php {$workerPath} 2>&1", $output, $exitCode);
    
    return response()->json([
        'success' => $exitCode === 0,
        'exit_code' => $exitCode,
        'output' => implode("\n", $output),
        'timestamp' => date('c')
    ]);
});
```

---

## 🔐 Proteção Contra Execuções Simultâneas

O worker usa **file lock** para evitar múltiplas execuções:

```php
$lockFile = sys_get_temp_dir() . '/sync_2phase.lock';
$lock = fopen($lockFile, 'c+');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    echo "⚠ Já existe um processo de sincronização rodando.\n";
    exit;
}
```

---

## 📊 Resultado Esperado

### **Execução Bem-Sucedida**
```
🚀 Iniciando sincronização em duas fases em 2025-11-15 12:00:00
📌 Versão: 3.0 - Backend Lumen + PostgreSQL

📋 FASE 1: Salvando lista completa de imóveis...
════════════════════════════════════════════════════════════════
📄 Página 1: https://www.exclusivalarimoveis.com.br/api/v1/app/imovel/lista?status=ativo&page=1&per_page=100
   ✓ Encontrados 100 imóveis (total de páginas: 3)
📄 Página 2: https://www.exclusivalarimoveis.com.br/api/v1/app/imovel/lista?status=ativo&page=2&per_page=100
   ✓ Encontrados 100 imóveis (total de páginas: 3)
📄 Página 3: https://www.exclusivalarimoveis.com.br/api/v1/app/imovel/lista?status=ativo&page=3&per_page=100
   ✓ Encontrados 59 imóveis (total de páginas: 3)

✅ FASE 1 CONCLUÍDA: 259 imóveis salvos/atualizados
════════════════════════════════════════════════════════════════

📝 FASE 2: Buscando detalhes dos imóveis...
════════════════════════════════════════════════════════════════
   ℹ️  Total de imóveis para atualizar: 211

✓ Imóvel 4024036 atualizado
✓ Imóvel 4023987 atualizado
... (continua)

✅ FASE 2 CONCLUÍDA: 211 imóveis atualizados, 0 erros
════════════════════════════════════════════════════════════════

🎉 SINCRONIZAÇÃO COMPLETA!
Total salvo na fase 1: 259
Total atualizado na fase 2: 211
Erros: 0
```

---

## ✅ Checklist de Verificação

- [x] Arquivo `backend/sync_worker.php` corrigido
- [x] Usa Laravel DB facade (não PDO)
- [x] Schema PostgreSQL (`imo_properties`)
- [x] Mapping de finalidade (`Locação` → `Aluguel`)
- [x] Salva latitude/longitude para o mapa
- [x] Duas fases preservadas
- [x] Lock file para evitar duplicação
- [x] Endpoint `/api/properties/sync-worker` configurado

---

## 🧪 Como Testar

### **1. Teste Local**
```bash
cd c:\xampp\htdocs\imobi\backend
php sync_worker.php
```

### **2. Teste via API**
```bash
curl https://exclusiva-backend.onrender.com/api/properties/sync-worker
```

### **3. Verificar Resultado**
```bash
curl https://exclusiva-backend.onrender.com/api/properties | jq '.data | length'
```

Deve retornar **230+** imóveis (não zerar!)

---

## 🎯 Próxima Execução Automática

O cron job do Render executa automaticamente a cada X horas.

**Status esperado:**
- ✅ Imóveis **preservados** (não zerados)
- ✅ Detalhes **atualizados** apenas se > 4 horas
- ✅ Novas propriedades **adicionadas** automaticamente
- ✅ Coordenadas **salvas** para o mapa funcionar

---

## 📝 Observações Importantes

1. **Não deleta imóveis:** O sync apenas adiciona/atualiza
2. **Respeita cache:** Só atualiza detalhes se > 4 horas
3. **Mapping de finalidade:** Corrige "Locação" → "Aluguel" automaticamente
4. **Coordenadas:** Agora salvas corretamente para o mapa

---

## 🔗 Arquivos Relacionados

- ✅ `backend/sync_worker.php` - Worker corrigido (v3.0)
- 📄 `backend/routes/web.php` - Endpoint configurado
- 🗺️ `frontend/src/components/PropertyMap.vue` - Usa latitude/longitude
- 📊 `database/schema_postgres.sql` - Schema PostgreSQL

---

**Status:** ✅ **CORRIGIDO E TESTADO**

**Data:** 15/11/2025

**Versão:** 3.0 - Backend Lumen + PostgreSQL
