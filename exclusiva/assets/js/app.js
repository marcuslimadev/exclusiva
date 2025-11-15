/**
 * Aplicação Principal Vue.js
 * Orquestra todos os componentes e gerenciadores
 */

const { createApp } = Vue;

const vueApp = createApp({
  data() {
    return {
      // Estados da aplicação
      isInitializing: true,
      loading: false,
      modalAberto: false,
      
      // Dados
      todosImoveis: [],
      imoveisFiltrados: [],
      imovelSelecionado: null,
      
      // Filtros (sincronizados com FilterManager)
      filtros: {
        bairro: '',
        cidade: '',
        precoMin: '',
        precoMax: ''
      },
      
      // Toast notifications
      toasts: [],
      toastIdCounter: 0,
      
      // Configurações
      config: {
        itemsPorPagina: 12,
        paginaAtual: 1
      }
    };
  },

  computed: {
    /**
     * Texto do contador de resultados
     */
    contadorTexto() {
      const total = this.imoveisFiltrados.length;
      if (total === 0) return 'Nenhum imóvel encontrado';
      if (total === 1) return '1 imóvel encontrado';
      return `${total} imóveis encontrados`;
    },

    /**
     * Especificações do imóvel selecionado
     */
    especificacoes() {
      if (!this.imovelSelecionado) return [];
      return modalManager.obterEspecificacoes(this.imovelSelecionado);
    },

    /**
     * Descrição limpa do imóvel selecionado
     */
    descricaoLimpa() {
      if (!this.imovelSelecionado?.descricao) return 'Descrição não disponível.';
      return AppUtils.limparDescricao(this.imovelSelecionado.descricao);
    },

    /**
     * Imóveis paginados
     */
    imoveisPaginados() {
      const inicio = (this.config.paginaAtual - 1) * this.config.itemsPorPagina;
      const fim = inicio + this.config.itemsPorPagina;
      return this.imoveisFiltrados.slice(inicio, fim);
    },

    /**
     * Total de páginas
     */
    totalPaginas() {
      return Math.ceil(this.imoveisFiltrados.length / this.config.itemsPorPagina);
    }
  },

  async mounted() {
    try {
      Logger.info('Inicializando aplicação...');
      
      await this.inicializarAplicacao();
      
      Logger.info('Aplicação inicializada com sucesso');
    } catch (error) {
      Logger.error('Erro ao inicializar aplicação:', error);
      this.mostrarToast('Erro ao inicializar aplicação', 'error');
    } finally {
      this.isInitializing = false;
    }
  },

  methods: {
    /**
     * Inicializa todos os componentes da aplicação
     */
    async inicializarAplicacao() {
      // Inicializar gerenciadores
      await this.inicializarGerenciadores();
      
      // Configurar eventos
      this.configurarEventos();
      
      // Carregar dados iniciais
      await this.carregarDadosIniciais();
      
      // Processar parâmetros da URL
      this.processarParametrosUrl();
    },

    /**
     * Inicializa todos os gerenciadores
     */
    async inicializarGerenciadores() {
      try {
        // Inicializar mapa
        await mapManager.inicializar('map');
        
        // Inicializar modal
        modalManager.inicializar();
        
        // Sincronizar filtros
        this.sincronizarFiltros();
        
        Logger.info('Gerenciadores inicializados');
      } catch (error) {
        Logger.error('Erro ao inicializar gerenciadores:', error);
        throw error;
      }
    },

    /**
     * Configura eventos da aplicação
     */
    configurarEventos() {
      // Evento ESC para fechar modal
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && this.modalAberto) {
          this.fecharModal();
        }
      });

      // Evento de toast personalizado
      window.addEventListener('showToast', (e) => {
        this.mostrarToast(e.detail.message, e.detail.type);
      });

      // Evento de redimensionamento
      window.addEventListener('resize', AppUtils.debounce(() => {
        mapManager.redimensionar();
      }, 250));

      // Evento de mudança de hash para deep linking
      window.addEventListener('hashchange', () => {
        this.processarParametrosUrl();
      });

      Logger.info('Eventos configurados');
    },

    /**
     * Carrega dados iniciais
     */
    async carregarDadosIniciais() {
      this.loading = true;
      
      try {
        Logger.info('Carregando imóveis...');
        
        const response = await apiManager.carregarImoveis();
        this.processarDadosCarregados(response);
        
      } catch (error) {
        Logger.error('Erro ao carregar dados iniciais:', error);
        this.mostrarToast('Erro ao carregar dados. Usando dados locais.', 'warning');
        
        // Usar dados de fallback
        const fallbackData = apiManager.getFallbackData();
        this.processarDadosCarregados(fallbackData);
      } finally {
        this.loading = false;
      }
    },

    /**
     * Processa dados carregados
     */
    processarDadosCarregados(response) {
      if (!response || !response.data || !Array.isArray(response.data)) {
        Logger.warn('Dados inválidos recebidos');
        this.todosImoveis = [];
        this.imoveisFiltrados = [];
        return;
      }

      // Validar e filtrar imóveis válidos
      const imoveisValidos = response.data.filter(imovel => {
        const valido = AppUtils.validarImovel(imovel);
        if (!valido) {
          Logger.warn('Imóvel inválido ignorado:', imovel);
        }
        return valido;
      });

      this.todosImoveis = imoveisValidos;
      this.aplicarFiltros();
      
      Logger.info(`${imoveisValidos.length} imóveis carregados e processados`);
    },

    /**
     * Sincroniza filtros com o FilterManager
     */
    sincronizarFiltros() {
      // Sincronizar filtros do Vue com FilterManager
      Object.keys(this.filtros).forEach(key => {
        filterManager.setFiltro(key, this.filtros[key]);
      });
    },

    /**
     * Aplica filtros aos imóveis
     */
    aplicarFiltros: AppUtils.debounce(function() {
      // Sincronizar filtros
      this.sincronizarFiltros();
      
      // Aplicar filtros
      this.imoveisFiltrados = filterManager.aplicarFiltros(this.todosImoveis);
      
      // Resetar paginação
      this.config.paginaAtual = 1;
      
      // Atualizar mapa
      this.atualizarMapa();
      
      Logger.info(`Filtros aplicados: ${this.imoveisFiltrados.length} resultados`);
    }, 300),

    /**
     * Atualiza marcadores no mapa
     */
    atualizarMapa() {
      if (mapManager.isInitialized) {
        mapManager.atualizarMarkers(this.imoveisFiltrados);
      }
    },

    /**
     * Abre modal de detalhes do imóvel
     */
    abrirModal(imovelOuCodigo) {
      let imovel;
      
      if (typeof imovelOuCodigo === 'object') {
        imovel = imovelOuCodigo;
      } else {
        imovel = this.todosImoveis.find(i => i.codigo == imovelOuCodigo);
      }
      
      if (!imovel) {
        Logger.warn('Imóvel não encontrado para abrir modal:', imovelOuCodigo);
        return;
      }
      
      this.imovelSelecionado = imovel;
      modalManager.abrirModalImovel(imovel);
      this.modalAberto = true;
    },

    /**
     * Fecha modal
     */
    fecharModal() {
      modalManager.fecharModal();
      this.modalAberto = false;
      this.imovelSelecionado = null;
    },

    /**
     * Formata valor monetário
     */
    formatarMoeda(valor) {
      return AppUtils.formatarMoeda(valor);
    },

    /**
     * Trata erro de carregamento de imagem
     */
    handleImageError(event) {
      event.target.src = 'https://via.placeholder.com/400x300?text=Imagem+Indisponível';
      event.target.onerror = null; // Previne loop infinito
    },

    /**
     * Abre imagem em tela cheia
     */
    abrirImagemCompleta(url) {
      window.open(url, '_blank');
    },

    /**
     * Mostra toast notification
     */
    mostrarToast(mensagem, tipo = 'info', duracao = 4000) {
      const toast = {
        id: ++this.toastIdCounter,
        message: mensagem,
        type: tipo,
        timestamp: Date.now()
      };
      
      this.toasts.push(toast);
      
      // Remove toast automaticamente
      setTimeout(() => {
        this.removerToast(toast.id);
      }, duracao);
      
      Logger.info(`Toast ${tipo}: ${mensagem}`);
    },

    /**
     * Remove toast notification
     */
    removerToast(id) {
      const index = this.toasts.findIndex(toast => toast.id === id);
      if (index > -1) {
        this.toasts.splice(index, 1);
      }
    },

    /**
     * Processa parâmetros da URL
     */
    processarParametrosUrl() {
      const urlParams = new URLSearchParams(window.location.search);
      const hash = window.location.hash.substring(1);
      
      // Verificar parâmetro de imóvel específico
      const codigoImovel = urlParams.get('imovel') || hash;
      if (codigoImovel) {
        const imovel = this.todosImoveis.find(i => i.codigo == codigoImovel);
        if (imovel) {
          this.abrirModal(imovel);
        }
      }
      
      // Aplicar filtros da URL
      const filtrosUrl = {};
      ['bairro', 'cidade', 'precoMin', 'precoMax'].forEach(key => {
        const valor = urlParams.get(key);
        if (valor) {
          filtrosUrl[key] = valor;
        }
      });
      
      if (Object.keys(filtrosUrl).length > 0) {
        Object.assign(this.filtros, filtrosUrl);
        this.aplicarFiltros();
      }
    },

    /**
     * Atualiza URL com filtros atuais
     */
    atualizarUrl() {
      const params = new URLSearchParams();
      
      Object.entries(this.filtros).forEach(([key, value]) => {
        if (value) {
          params.set(key, value);
        }
      });
      
      const newUrl = params.toString() ? 
        `${window.location.pathname}?${params.toString()}` : 
        window.location.pathname;
      
      window.history.replaceState({}, '', newUrl);
    },

    /**
     * Limpa todos os filtros
     */
    limparFiltros() {
      Object.keys(this.filtros).forEach(key => {
        this.filtros[key] = '';
      });
      
      filterManager.limparFiltros();
      this.aplicarFiltros();
      this.atualizarUrl();
      
      this.mostrarToast('Filtros limpos', 'info');
    },

    /**
     * Muda página da paginação
     */
    mudarPagina(pagina) {
      if (pagina >= 1 && pagina <= this.totalPaginas) {
        this.config.paginaAtual = pagina;
        
        // Scroll para o topo
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    },

    /**
     * Obtém localização do usuário
     */
    async obterLocalizacao() {
      try {
        await mapManager.obterLocalizacaoUsuario();
        this.mostrarToast('Localização obtida com sucesso!', 'success');
      } catch (error) {
        Logger.error('Erro ao obter localização:', error);
        this.mostrarToast('Erro ao obter localização', 'error');
      }
    },

    /**
     * Exporta dados para CSV
     */
    exportarCSV() {
      if (this.imoveisFiltrados.length === 0) {
        this.mostrarToast('Nenhum dado para exportar', 'warning');
        return;
      }
      
      try {
        const headers = ['Código', 'Tipo', 'Finalidade', 'Valor', 'Cidade', 'Bairro', 'Dormitórios', 'Banheiros'];
        const csvContent = [
          headers.join(','),
          ...this.imoveisFiltrados.map(imovel => [
            imovel.codigo,
            `"${imovel.tipo}"`,
            `"${imovel.finalidade}"`,
            imovel.valor,
            `"${imovel.cidade}"`,
            `"${imovel.bairro}"`,
            imovel.dormitorios || 0,
            imovel.banheiros || 0
          ].join(','))
        ].join('\n');
        
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `imoveis_${new Date().toISOString().split('T')[0]}.csv`;
        link.click();
        
        this.mostrarToast('Dados exportados com sucesso!', 'success');
      } catch (error) {
        Logger.error('Erro ao exportar CSV:', error);
        this.mostrarToast('Erro ao exportar dados', 'error');
      }
    },

    /**
     * Compartilha filtros atuais
     */
    async compartilharFiltros() {
      const url = window.location.href;
      const sucesso = await AppUtils.compartilhar({
        title: 'Filtros de Imóveis - Exclusiva Imóveis',
        text: 'Confira estes imóveis filtrados',
        url: url
      });
      
      if (sucesso) {
        this.mostrarToast('Filtros compartilhados!', 'success');
      } else {
        const copiado = await AppUtils.copiarTexto(url);
        if (copiado) {
          this.mostrarToast('Link copiado para área de transferência!', 'success');
        }
      }
    }
  },

  watch: {
    // Observa mudanças nos filtros para atualizar URL
    filtros: {
      handler() {
        this.atualizarUrl();
      },
      deep: true
    }
  }
});

// Monta a aplicação
const appInstance = vueApp.mount('#app');

// Exporta instância globalmente para compatibilidade
window.vueApp = appInstance;
window.abrirModalGlobal = function(codigo) {
  if (appInstance && appInstance.abrirModal) {
    appInstance.abrirModal(codigo);
  }
};

Logger.info('Aplicação Vue.js inicializada');