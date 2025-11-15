/**
 * Aplicação Principal jQuery
 * Sistema de catálogo de imóveis com mapa satelital
 */

$(document).ready(function() {
  // Estado da aplicação
  const AppState = {
    isInitializing: true,
    loading: false,
    modalAberto: false,
    galeriaAberta: false,
    imagemAtualIndex: 0,
    imagensGaleria: [],
    mapFullscreen: false,
    todosImoveis: [],
    imoveisFiltrados: [],
    imovelSelecionado: null,
    filtros: {
      bairro: '',
      cidade: '',
      precoMin: '',
      precoMax: ''
    },
    toasts: [],
    toastIdCounter: 0,
    config: {
      itemsPorPagina: 12,
      paginaAtual: 1
    }
  };

  // Inicialização da aplicação
  async function inicializarAplicacao() {
    try {
      Logger.info('Inicializando aplicação...');
      await inicializarGerenciadores();
      configurarEventos();
      await carregarDadosIniciais();
      processarParametrosUrl();
      Logger.info('Aplicação inicializada com sucesso');
    } catch (error) {
      Logger.error('Erro ao inicializar aplicação:', error);
      mostrarToast('Erro ao inicializar aplicação', 'error');
    } finally {
      AppState.isInitializing = false;
      $('#loading-screen').fadeOut();
    }
  }

  // Inicializa gerenciadores
  async function inicializarGerenciadores() {
    try {
      await mapManager.inicializar('map');
      modalManager.inicializar();
      
      if (mapManager.setOnBoundsChangeCallback) {
        mapManager.setOnBoundsChangeCallback((bounds, zoom) => {
          aplicarFiltroArea(bounds, zoom);
        });
      }
      
      sincronizarFiltros();
      Logger.info('Gerenciadores inicializados');
    } catch (error) {
      Logger.error('Erro ao inicializar gerenciadores:', error);
      throw error;
    }
  }

  // Configura eventos
  function configurarEventos() {
    // ESC para fechar modal ou galeria
    $(document).on('keydown', function(e) {
      if (e.key === 'Escape') {
        if (AppState.galeriaAberta) {
          fecharGaleria();
        } else if (AppState.modalAberto) {
          fecharModal();
        } else if (AppState.mapFullscreen) {
          toggleMapFullscreen();
        }
      }
      
      // Navegação na galeria
      if (AppState.galeriaAberta) {
        if (e.key === 'ArrowLeft') {
          imagemAnterior();
        } else if (e.key === 'ArrowRight') {
          proximaImagem();
        }
      }
    });

    // Filtros
    $('#filtro-bairro').on('input', AppUtils.debounce(function() {
      AppState.filtros.bairro = $(this).val();
      aplicarFiltros();
    }, 300));

    $('#filtro-cidade').on('input', AppUtils.debounce(function() {
      AppState.filtros.cidade = $(this).val();
      aplicarFiltros();
    }, 300));

    $('#filtro-preco-min').on('change', function() {
      AppState.filtros.precoMin = $(this).val();
      aplicarFiltros();
    });

    $('#filtro-preco-max').on('change', function() {
      AppState.filtros.precoMax = $(this).val();
      aplicarFiltros();
    });

    // Botões
    $('#btn-buscar').on('click', aplicarFiltros);
    $('#btn-limpar').on('click', limparFiltros);
    $('#btn-fullscreen').on('click', toggleMapFullscreen);

    // Modal
    $(document).on('click', '.btn-ver-detalhes', function() {
      const codigo = $(this).data('codigo');
      const imovel = AppState.todosImoveis.find(i => i.codigo == codigo);
      if (imovel) {
        abrirModal(imovel);
      }
    });

    $('#modal-backdrop').on('click', function(e) {
      if (e.target === this) {
        fecharModal();
      }
    });

    $('#btn-fechar-modal').on('click', fecharModal);

    // Galeria
    $(document).on('click', '.gallery-image', function() {
      const index = $(this).data('index');
      abrirGaleriaImagem(index);
    });

    $('#galeria-backdrop').on('click', function(e) {
      if (e.target === this) {
        fecharGaleria();
      }
    });

    $('#btn-fechar-galeria').on('click', fecharGaleria);
    $('#btn-galeria-anterior').on('click', imagemAnterior);
    $('#btn-galeria-proximo').on('click', proximaImagem);

    // Toast
    $(document).on('click', '.toast-close', function() {
      const id = $(this).data('id');
      removerToast(id);
    });

    // Redimensionamento
    $(window).on('resize', AppUtils.debounce(() => {
      if (mapManager && mapManager.redimensionar) {
        mapManager.redimensionar();
      }
    }, 250));

    Logger.info('Eventos configurados');
  }

  // Carrega dados iniciais
  async function carregarDadosIniciais() {
    AppState.loading = true;
    $('#loading-indicator').show();
    
    try {
      Logger.info('Carregando imóveis...');
      const response = await apiManager.carregarImoveis();
      processarDadosCarregados(response);
    } catch (error) {
      Logger.error('Erro ao carregar dados iniciais:', error);
      mostrarToast('Usando dados de demonstração', 'warning');
      
      const fallbackData = apiManager.getFallbackData();
      processarDadosCarregados(fallbackData);
    } finally {
      AppState.loading = false;
      $('#loading-indicator').hide();
    }
  }

  // Processa dados carregados
  function processarDadosCarregados(response) {
    if (!response || !response.data || response.data.length === 0) {
      AppState.todosImoveis = [];
      AppState.imoveisFiltrados = [];
      return;
    }
    
    AppState.todosImoveis = response.data;
    aplicarFiltros();
    
    if (response.source === 'fallback') {
      mostrarToast('Sistema carregado com dados de demonstração', 'info', 3000);
    } else {
      mostrarToast('Imóveis carregados com sucesso!', 'success', 2000);
    }
    
    Logger.info(`${AppState.todosImoveis.length} imóveis carregados`);
  }

  // Aplica filtros
  const aplicarFiltros = AppUtils.debounce(function() {
    filterManager.setFiltros(AppState.filtros);
    let imoveisFiltrados = filterManager.aplicarFiltros(AppState.todosImoveis);
    
    AppState.imoveisFiltrados = imoveisFiltrados;
    
    if (mapManager && mapManager.atualizarMarkers) {
      mapManager.atualizarMarkers(AppState.imoveisFiltrados);
    }
    
    AppState.config.paginaAtual = 1;
    renderizarImoveis();
    atualizarContador();
    
    Logger.info(`Filtros aplicados: ${AppState.imoveisFiltrados.length} resultados`);
  }, 300);

  // Aplica filtro por área
  function aplicarFiltroArea(bounds, zoom) {
    if (!bounds) {
      aplicarFiltros();
      return;
    }

    filterManager.setFiltros(AppState.filtros);
    let imoveisFiltrados = filterManager.aplicarFiltros(AppState.todosImoveis);
    
    imoveisFiltrados = filtrarPorArea(imoveisFiltrados, bounds);
    AppState.imoveisFiltrados = imoveisFiltrados;
    
    renderizarImoveis();
    atualizarContador();
    
    Logger.info(`Filtro por área aplicado: ${AppState.imoveisFiltrados.length} imóveis na área visível`);
  }

  // Filtra por área geográfica
  function filtrarPorArea(imoveis, bounds) {
    return imoveis.filter(imovel => {
      if (!AppUtils.validarCoordenadas(imovel.lat, imovel.lng)) {
        return false;
      }
      
      const lat = parseFloat(imovel.lat);
      const lng = parseFloat(imovel.lng);
      
      return bounds.contains([lat, lng]);
    });
  }

  // Sincroniza filtros
  function sincronizarFiltros() {
    if (filterManager && filterManager.setFiltro) {
      Object.keys(AppState.filtros).forEach(key => {
        filterManager.setFiltro(key, AppState.filtros[key]);
      });
    }
  }

  // Renderiza imóveis
  function renderizarImoveis() {
    const container = $('#imoveis-grid');
    
    if (AppState.imoveisFiltrados.length === 0) {
      container.html(`
        <div class="col-span-full text-center py-20">
          <i class="fas fa-search text-8xl text-gray-300 mb-6"></i>
          <h3 class="text-3xl font-bold text-gray-600 mb-4">Nenhum imóvel encontrado</h3>
          <p class="text-xl text-gray-500 mb-8">Tente ajustar os filtros ou explorar outras áreas no mapa.</p>
          <button id="btn-limpar-vazio" class="btn-primary py-3 px-8 rounded-xl font-semibold">
            <i class="fas fa-refresh mr-2"></i>
            Limpar Filtros
          </button>
        </div>
      `);
      
      $('#btn-limpar-vazio').on('click', limparFiltros);
      return;
    }

    let html = '';
    AppState.imoveisFiltrados.forEach(imovel => {
      html += criarCardImovel(imovel);
    });
    
    container.html(html);
  }

  // Cria card do imóvel
  function criarCardImovel(imovel) {
    const preco = AppUtils.formatarMoeda(imovel.valor);
    const statusClass = imovel.finalidade === 'Venda' ? 'status-badge venda' : 'status-badge';
    const statusIcon = imovel.finalidade === 'Venda' ? 'fas fa-tag' : 'fas fa-key';
    
    return `
      <div class="property-card smooth-transition">
        <div class="relative overflow-hidden">
          <span class="${statusClass} absolute top-4 left-4 z-10">
            <i class="${statusIcon} mr-1"></i>
            ${imovel.finalidade}
          </span>
          <img 
            src="${imovel.thumb_url || 'https://via.placeholder.com/400x300'}" 
            alt="${imovel.tipo}" 
            class="property-image w-full h-56 object-cover"
            onerror="this.src='https://via.placeholder.com/400x300/3b82f6/ffffff?text=Exclusiva+Imóveis'">
        </div>
        
        <div class="p-6">
          <div class="mb-4">
            <h3 class="text-3xl font-bold text-primary mb-2">${preco}</h3>
            <p class="text-gray-600 flex items-center text-lg">
              <i class="fas fa-map-marker-alt mr-2 text-secondary"></i>
              ${imovel.bairro}, ${imovel.cidade}
            </p>
          </div>
          
          <div class="flex items-center justify-between mb-4">
            <span class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white px-4 py-2 rounded-full text-sm font-semibold">
              ${imovel.tipo}
            </span>
            <span class="text-gray-500 text-sm font-medium">Ref: ${imovel.referencia}</span>
          </div>
          
          <div class="flex items-center text-gray-600 text-sm mb-6 space-x-6">
            ${imovel.dormitorios ? `
              <span class="flex items-center">
                <i class="fas fa-bed mr-2 text-primary text-lg"></i>${imovel.dormitorios} quartos
              </span>
            ` : ''}
            ${imovel.banheiros ? `
              <span class="flex items-center">
                <i class="fas fa-bath mr-2 text-secondary text-lg"></i>${imovel.banheiros} banheiros
              </span>
            ` : ''}
            ${imovel.garagem ? `
              <span class="flex items-center">
                <i class="fas fa-car mr-2 text-accent text-lg"></i>${imovel.garagem} vagas
              </span>
            ` : ''}
          </div>
          
          <button 
            class="btn-ver-detalhes w-full btn-primary py-4 px-6 rounded-xl font-bold text-lg flex items-center justify-center"
            data-codigo="${imovel.codigo}">
            <i class="fas fa-eye mr-3"></i>
            Ver Detalhes
          </button>
        </div>
      </div>
    `;
  }

  // Atualiza contador
  function atualizarContador() {
    const total = AppState.imoveisFiltrados.length;
    let texto = 'Nenhum imóvel encontrado';
    
    if (total === 1) {
      texto = '1 imóvel encontrado';
    } else if (total > 1) {
      texto = `${total} imóveis encontrados`;
    }
    
    $('#contador-resultados').html(`
      <i class="fas fa-home mr-2"></i>
      ${texto}
    `);
  }

  // Toggle fullscreen do mapa
  function toggleMapFullscreen() {
    AppState.mapFullscreen = !AppState.mapFullscreen;
    
    if (AppState.mapFullscreen) {
      $('#map').addClass('map-fullscreen');
      $('#fullscreen-overlay').show();
      $('body').addClass('overflow-hidden');
      mostrarToast('Mapa em tela cheia - ESC para sair', 'info', 3000);
    } else {
      $('#map').removeClass('map-fullscreen');
      $('#fullscreen-overlay').hide();
      $('body').removeClass('overflow-hidden');
    }
    
    setTimeout(() => {
      if (mapManager && mapManager.redimensionar) {
        mapManager.redimensionar();
      }
    }, 100);
  }

  // Abre modal
  function abrirModal(imovel) {
    AppState.imovelSelecionado = imovel;
    AppState.modalAberto = true;
    
    // Preenche dados do modal
    $('#modal-titulo').text(`${imovel.tipo} - ${imovel.bairro}`);
    $('#modal-preco').text(AppUtils.formatarMoeda(imovel.valor));
    $('#modal-endereco').text(`${imovel.logradouro || ''}, ${imovel.numero || ''} - ${imovel.bairro}, ${imovel.cidade}/${imovel.estado || ''}`);
    $('#modal-coordenadas').text(`${imovel.lat}, ${imovel.lng}`);
    $('#modal-descricao').text(AppUtils.limparDescricao(imovel.descricao) || 'Descrição não disponível.');
    
    // Galeria de imagens
    renderizarGaleriaModal(imovel.imagens);
    
    // Especificações
    renderizarEspecificacoes(imovel);
    
    // Mostra modal
    $('#modal-detalhes').show();
    $('body').addClass('overflow-hidden');
    
    // Inicializa mapa do modal
    modalManager.abrirModalImovel(imovel);
  }

  // Fecha modal
  function fecharModal() {
    AppState.modalAberto = false;
    AppState.imovelSelecionado = null;
    $('#modal-detalhes').hide();
    $('body').removeClass('overflow-hidden');
    modalManager.fecharModal();
  }

  // Renderiza galeria do modal
  function renderizarGaleriaModal(imagens) {
    const container = $('#modal-galeria');
    
    if (!imagens || imagens.length === 0) {
      container.html(`
        <div class="text-center py-12 text-gray-500 bg-gray-50 rounded-2xl">
          <i class="fas fa-image text-6xl mb-4"></i>
          <p class="text-xl">Nenhuma imagem disponível</p>
        </div>
      `);
      return;
    }

    let html = '<div class="image-gallery">';
    imagens.slice(0, 8).forEach((img, index) => {
      html += `
        <img 
          src="${img}" 
          class="gallery-image" 
          data-index="${index}"
          onerror="this.src='https://via.placeholder.com/400x300/3b82f6/ffffff?text=Exclusiva+Imóveis'">
      `;
    });
    html += '</div>';
    
    container.html(html);
  }

  // Renderiza especificações
  function renderizarEspecificacoes(imovel) {
    const specs = modalManager.obterEspecificacoes(imovel);
    const container = $('#modal-especificacoes');
    
    let html = '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';
    specs.forEach(spec => {
      html += `
        <div class="spec-item">
          <i class="${spec.icon} spec-icon"></i>
          <span class="font-medium">
            <strong>${spec.label}:</strong> 
            <span class="text-gray-700">${spec.value}</span>
          </span>
        </div>
      `;
    });
    html += '</div>';
    
    container.html(html);
  }

  // Abre galeria de imagens
  function abrirGaleriaImagem(index) {
    if (!AppState.imovelSelecionado?.imagens?.length) return;
    
    AppState.imagensGaleria = [...AppState.imovelSelecionado.imagens];
    AppState.imagemAtualIndex = index;
    AppState.galeriaAberta = true;
    
    atualizarImagemGaleria();
    $('#galeria-modal').show();
    $('body').addClass('overflow-hidden');
  }

  // Fecha galeria
  function fecharGaleria() {
    AppState.galeriaAberta = false;
    AppState.imagensGaleria = [];
    AppState.imagemAtualIndex = 0;
    $('#galeria-modal').hide();
    $('body').removeClass('overflow-hidden');
  }

  // Imagem anterior
  function imagemAnterior() {
    if (AppState.imagemAtualIndex > 0) {
      AppState.imagemAtualIndex--;
      atualizarImagemGaleria();
    }
  }

  // Próxima imagem
  function proximaImagem() {
    if (AppState.imagemAtualIndex < AppState.imagensGaleria.length - 1) {
      AppState.imagemAtualIndex++;
      atualizarImagemGaleria();
    }
  }

  // Atualiza imagem da galeria
  function atualizarImagemGaleria() {
    const img = AppState.imagensGaleria[AppState.imagemAtualIndex];
    $('#galeria-imagem').attr('src', img);
    $('#galeria-contador').text(`${AppState.imagemAtualIndex + 1} / ${AppState.imagensGaleria.length}`);
    
    // Controles de navegação
    $('#btn-galeria-anterior').toggle(AppState.imagemAtualIndex > 0);
    $('#btn-galeria-proximo').toggle(AppState.imagemAtualIndex < AppState.imagensGaleria.length - 1);
  }

  // Mostra toast
  function mostrarToast(mensagem, tipo = 'info', duracao = 4000) {
    const toast = {
      id: ++AppState.toastIdCounter,
      message: mensagem,
      type: tipo,
      timestamp: Date.now()
    };
    
    AppState.toasts.push(toast);
    
    const toastHtml = `
      <div id="toast-${toast.id}" class="toast ${tipo} px-6 py-4 rounded-xl shadow-2xl animate-slide-up max-w-sm">
        <div class="flex items-center">
          <i class="${getToastIcon(tipo)} mr-3 text-xl"></i>
          <span class="font-medium">${mensagem}</span>
          <button class="toast-close ml-4 text-gray-400 hover:text-gray-600" data-id="${toast.id}">
            <i class="fas fa-times"></i>
          </button>
        </div>
      </div>
    `;
    
    $('#toast-container').append(toastHtml);
    
    setTimeout(() => {
      removerToast(toast.id);
    }, duracao);
  }

  // Remove toast
  function removerToast(id) {
    $(`#toast-${id}`).fadeOut(() => {
      $(`#toast-${id}`).remove();
    });
    
    const index = AppState.toasts.findIndex(toast => toast.id === id);
    if (index > -1) {
      AppState.toasts.splice(index, 1);
    }
  }

  // Obtém ícone do toast
  function getToastIcon(tipo) {
    const icons = {
      success: 'fas fa-check-circle text-success',
      error: 'fas fa-exclamation-circle text-error',
      warning: 'fas fa-exclamation-triangle text-warning',
      info: 'fas fa-info-circle text-info'
    };
    return icons[tipo] || icons.info;
  }

  // Processa parâmetros da URL
  function processarParametrosUrl() {
    const urlParams = new URLSearchParams(window.location.search);
    const imovelId = urlParams.get('imovel');
    
    if (imovelId) {
      const imovel = AppState.todosImoveis.find(i => i.codigo == imovelId);
      if (imovel) {
        setTimeout(() => {
          abrirModal(imovel);
        }, 1000);
      }
    }
  }

  // Limpa filtros
  function limparFiltros() {
    AppState.filtros = {
      bairro: '',
      cidade: '',
      precoMin: '',
      precoMax: ''
    };
    
    $('#filtro-bairro').val('');
    $('#filtro-cidade').val('');
    $('#filtro-preco-min').val('');
    $('#filtro-preco-max').val('');
    
    if (filterManager && filterManager.limparFiltros) {
      filterManager.limparFiltros();
    }
    
    aplicarFiltros();
    mostrarToast('Filtros limpos', 'info', 2000);
  }

  // Exporta funções globais
  window.AppState = AppState;
  window.abrirModalGlobal = function(codigo) {
    const imovel = AppState.todosImoveis.find(i => i.codigo == codigo);
    if (imovel) {
      abrirModal(imovel);
    }
  };

  // Inicia aplicação
  inicializarAplicacao();
  
  Logger.info('Aplicação jQuery inicializada com sucesso');
});