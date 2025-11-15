# 📸 Importação de Imagens dos Imóveis

## 🎯 Objetivo

Este documento explica como as imagens dos imóveis são importadas durante a sincronização, garantindo que **todas as imagens** (incluindo as do slide/carrossel) sejam corretamente armazenadas tanto na sincronização manual quanto automática.

## 🔄 Mecanismos de Sincronização

Existem **dois mecanismos** principais de sincronização no sistema:

### 1. Sincronização Manual
- **Endpoint**: `GET /api/properties/sync`
- **Classe**: `App\Services\PropertySyncService`
- **Comando**: `php artisan properties:sync`
- **Uso**: Executada manualmente por administradores quando necessário

### 2. Sincronização Automática (Cron Job)
- **Endpoint**: `GET /api/properties/sync-worker`  
- **Arquivo**: `backend/sync_worker.php`
- **Uso**: Executada automaticamente pelo cron job do Render a cada X horas

## 📥 Como as Imagens São Importadas

Ambos os mecanismos seguem o mesmo processo para importar imagens:

### Fase 1: Lista Básica
- Busca todas as páginas de imóveis ativos da API
- Salva apenas dados básicos (código, referência, tipo, etc.)
- **Não importa imagens nesta fase** (para performance)

### Fase 2: Detalhes Completos
- Busca detalhes de cada imóvel (endpoint `/dados/{codigo}`)
- Importa **TODAS as imagens** do array `imagens` da API
- Armazena:
  - `imagem_destaque`: URL da imagem destacada ou primeira imagem
  - `imagens`: Array JSON com todas as imagens e suas propriedades

### Estrutura de Dados de Imagens

```json
[
  {
    "url": "https://example.com/imagem1.jpg",
    "destaque": true
  },
  {
    "url": "https://example.com/imagem2.jpg", 
    "destaque": false
  },
  {
    "url": "https://example.com/imagem3.jpg",
    "destaque": false
  }
]
```

## ✅ Validações Implementadas

Para garantir a importação correta das imagens, as seguintes validações foram implementadas:

1. **Verificação de Array**: 
   ```php
   if (!empty($d['imagens']) && is_array($d['imagens']))
   ```

2. **Verificação de URL**:
   ```php
   if (isset($img['url']) && !empty($img['url']))
   ```

3. **Fallback para Imagem Destaque**:
   - Se nenhuma imagem tem `destaque: true`, a primeira imagem é usada como destaque

## 📊 Logging e Monitoramento

### Sincronização Manual (PropertySyncService)
```
✏️ Imóvel 4024036 atualizado (5 imagens)
➕ Imóvel 4023987 criado (3 imagens)
```

Estatísticas finais incluem:
- `found`: Total de imóveis encontrados
- `new`: Imóveis novos criados
- `updated`: Imóveis atualizados
- `with_images`: Total de imóveis com imagens
- `errors`: Total de erros

### Sincronização Automática (sync_worker.php)
```
✓ Imóvel 4024036 atualizado (5 imagens)
✓ Imóvel 4023987 atualizado (3 imagens)

🎉 SINCRONIZAÇÃO COMPLETA!
Total salvo na fase 1: 259
Total atualizado na fase 2: 211
Erros: 0
Imóveis com imagens: 205
```

## 🎨 Exibição no Frontend

As imagens são exibidas no frontend através do componente de carrossel/slideshow:

```vue
<div v-for="(imagem, index) in imovelSelecionado.imagens" :key="index">
  <img :src="imagem.url" :alt="`Imagem ${index + 1}`">
</div>
```

### Características do Slideshow:
- **Navegação**: Setas esquerda/direita
- **Indicadores**: Pontos clicáveis para cada imagem
- **Destaque**: Primeira imagem ou marcada com `destaque: true`
- **Transições**: Fade suave entre imagens

## 🔍 Verificação de Importação

Para verificar se as imagens foram importadas corretamente:

### Via SQL
```sql
-- Contar imóveis com imagens
SELECT COUNT(*) 
FROM imo_properties 
WHERE imagens IS NOT NULL 
  AND imagens != '[]' 
  AND imagens != '';

-- Ver detalhes de um imóvel específico
SELECT codigo_imovel, imagem_destaque, imagens 
FROM imo_properties 
WHERE codigo_imovel = '4024036';
```

### Via API
```bash
# Listar imóveis
curl https://exclusiva-backend.onrender.com/api/properties | jq '.data[0].imagens'

# Ver imóvel específico
curl https://exclusiva-backend.onrender.com/api/properties/123 | jq '.imagens'
```

## ⚠️ Solução de Problemas

### Problema: Imóvel sem imagens

**Possíveis causas:**
1. API não retornou imagens para este imóvel
2. Imóvel ainda não passou pela Fase 2 da sincronização
3. Erro durante importação (verificar logs)

**Solução:**
```bash
# Forçar re-sincronização do imóvel
curl https://exclusiva-backend.onrender.com/api/properties/sync
```

### Problema: Imagens desatualizadas

**Causa:** Imóvel não foi atualizado recentemente (> 4 horas)

**Solução:** A próxima sincronização automática atualizará as imagens

### Problema: Slideshow não funciona

**Verificar:**
1. Campo `imagens` está no formato JSON correto
2. Cada imagem tem a propriedade `url`
3. URLs das imagens são acessíveis

## 📝 Manutenção

### Logs de Sincronização
- **Manual**: Verificar logs do Laravel em `storage/logs/laravel.log`
- **Automática**: Verificar output do endpoint `/api/properties/sync-worker`

### Frequência de Atualização
- **Automática**: Configurada no cron job do Render
- **Imagens desatualizadas**: Imóveis com `updated_at > 4 horas` são re-sincronizados

## 🎉 Resumo

✅ **Todas as imagens da API são importadas** em ambos os métodos de sincronização
✅ **Validações robustas** garantem que apenas URLs válidas sejam armazenadas
✅ **Logging detalhado** permite monitorar a importação de imagens
✅ **Frontend preparado** para exibir todas as imagens em slideshow

---

**Última atualização**: 15/11/2025
**Versão**: 3.1 - Com melhorias de validação e logging de imagens
