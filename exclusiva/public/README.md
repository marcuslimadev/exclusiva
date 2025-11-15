# Catálogo de Imóveis - Exclusiva Imóveis

## 📋 Sobre o Projeto

Sistema moderno e otimizado para catálogo de imóveis com interface responsiva, mapa interativo e funcionalidades avançadas de busca e filtragem.

## ✨ Funcionalidades Principais

### 🏠 Catálogo de Imóveis
- Listagem responsiva com cards otimizados
- Filtros avançados (preço, localização, características)
- Busca em tempo real com debounce
- Paginação inteligente
- Ordenação por múltiplos critérios

### 🗺️ Mapa Interativo
- Visualização geográfica dos imóveis
- Markers customizados por tipo de negócio
- Popups informativos
- Controles de localização e tela cheia
- Integração com MapTiler (premium) ou OpenStreetMap

### 🔍 Sistema de Busca
- Filtros por bairro, cidade, preço
- Sugestões automáticas
- Histórico de filtros
- Deep linking para compartilhamento
- Exportação de resultados (CSV)

### 📱 Modal Avançado
- Galeria de imagens otimizada
- Especificações detalhadas
- Mapa de localização integrado
- Botões de ação (contato, favoritos, compartilhamento)
- Navegação por teclado

### 🎨 Interface Moderna
- Design responsivo e acessível
- Animações suaves e micro-interações
- Toast notifications
- Loading states otimizados
- Tema claro com preparação para modo escuro

## 🏗️ Arquitetura

### Estrutura Modular
```
projeto-otimizado/
├── index.html              # Página principal
├── assets/
│   ├── css/
│   │   └── styles.css      # Estilos customizados
│   └── js/
│       ├── config.js       # Configurações centralizadas
│       ├── utils.js        # Utilitários gerais
│       ├── api.js          # Gerenciador de API
│       ├── map.js          # Gerenciador do mapa
│       ├── filters.js      # Gerenciador de filtros
│       ├── modal.js        # Gerenciador de modais
│       └── app.js          # Aplicação Vue.js principal
├── .env                    # Variáveis de ambiente
└── README.md              # Documentação
```

### Tecnologias Utilizadas
- **Frontend**: Vue.js 3, Tailwind CSS
- **Mapa**: Leaflet.js com MapTiler/OpenStreetMap
- **Ícones**: Font Awesome 6
- **Arquitetura**: Modular com separação de responsabilidades

## 🚀 Instalação e Uso

### Pré-requisitos
- Servidor web (Apache, Nginx, ou servidor de desenvolvimento)
- Navegador moderno com suporte a ES6+

### Configuração
1. Configure as variáveis no arquivo `.env`:
   ```env
   MAPTILER_KEY=sua_chave_maptiler
   OPENAI_API_KEY=sua_chave_openai
   API_BASE_URL=../api
   DEBUG_MODE=true
   ```

2. Coloque os arquivos em um servidor web

3. Acesse `index.html` no navegador

### Configuração da API
O sistema funciona com dados de fallback, mas para integração completa:

1. Configure endpoint da API em `config.js`
2. Implemente endpoints:
   - `GET /api/imoveis.php` - Lista de imóveis
   - `GET /api/detalhes.php?codigo=X` - Detalhes do imóvel

## 🔧 Configurações Avançadas

### Personalização do Mapa
```javascript
// Em config.js
MAP_CONFIG: {
  DEFAULT_CENTER: [-19.9, -43.9], // Coordenadas padrão
  DEFAULT_ZOOM: 11,                // Zoom inicial
  MAX_ZOOM: 19,                    // Zoom máximo
  TILE_LAYER: 'url_do_tile_server' // Servidor de tiles
}
```

### Cache e Performance
```javascript
// Configurações de cache
CACHE: {
  DURATION: 5 * 60 * 1000, // 5 minutos
  MAX_SIZE: 100             // Máximo de itens
}
```

### Filtros Personalizados
```javascript
// Adicionar novos filtros em filters.js
filtros: {
  bairro: '',
  cidade: '',
  precoMin: '',
  precoMax: '',
  // Adicione novos filtros aqui
  novoFiltro: ''
}
```

## 📊 Funcionalidades Técnicas

### Sistema de Cache
- Cache inteligente de requisições API
- Armazenamento local de preferências
- Otimização de imagens com lazy loading

### Tratamento de Erros
- Fallback para dados locais
- Logs estruturados para debugging
- Notificações user-friendly

### Acessibilidade
- Navegação por teclado
- Contraste adequado
- Semântica HTML correta
- Suporte a screen readers

### Performance
- Debounce em filtros
- Lazy loading de imagens
- Paginação otimizada
- Minificação de assets

## 🔍 Debugging

### Modo Debug
Ative o modo debug adicionando `?debug=true` na URL ou configurando `DEBUG_MODE=true` no `.env`.

### Logs
O sistema gera logs estruturados no console:
```javascript
Logger.info('Mensagem informativa');
Logger.warn('Aviso');
Logger.error('Erro', errorObject);
```

### Ferramentas de Desenvolvimento
- Console do navegador para logs
- Vue DevTools para debugging do estado
- Network tab para monitorar requisições

## 🚀 Otimizações Implementadas

### Performance
- ✅ Debounce em filtros (300ms)
- ✅ Throttle em eventos de scroll/resize
- ✅ Lazy loading de imagens
- ✅ Cache de requisições API
- ✅ Paginação de resultados

### UX/UI
- ✅ Loading states em todas as operações
- ✅ Animações suaves (CSS transitions)
- ✅ Toast notifications
- ✅ Feedback visual em interações
- ✅ Responsividade completa

### Código
- ✅ Modularização por funcionalidades
- ✅ Separação de responsabilidades
- ✅ Tratamento robusto de erros
- ✅ Validação de dados
- ✅ Documentação inline

### SEO e Acessibilidade
- ✅ Meta tags otimizadas
- ✅ Estrutura semântica HTML5
- ✅ Alt text em imagens
- ✅ Navegação por teclado
- ✅ Contraste adequado

## 📱 Compatibilidade

### Navegadores Suportados
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

### Dispositivos
- Desktop (1920x1080+)
- Tablet (768x1024)
- Mobile (375x667+)

## 🤝 Contribuição

### Estrutura de Commits
```
feat: nova funcionalidade
fix: correção de bug
docs: documentação
style: formatação
refactor: refatoração
test: testes
chore: manutenção
```

### Padrões de Código
- Use ESLint para JavaScript
- Siga convenções de nomenclatura camelCase
- Documente funções complexas
- Mantenha funções pequenas e focadas

## 📄 Licença

Este projeto está sob licença MIT. Veja o arquivo LICENSE para detalhes.

## 📞 Suporte

Para suporte técnico ou dúvidas:
- Abra uma issue no repositório
- Consulte a documentação inline no código
- Verifique os logs do console em modo debug

---

**Versão**: 2.0.0  
**Última atualização**: Setembro 2024  
**Desenvolvido por**: Alex - MGX Team