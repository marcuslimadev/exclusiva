/**
 * Gerenciador de Modal
 * Centraliza toda a lógica relacionada aos modais da aplicação
 */

class ModalManager {
  constructor() {
    this.modals = new Map();
    this.currentModal = null;
    this.backdrop = null;
    this.isInitialized = false;
    
    this.config = {
      closeOnBackdrop: true,
      closeOnEscape: true,
      showBackdrop: true,
      backdropClass: 'modal-backdrop',
      modalClass: 'modal-container',
      animationDuration: 300
    };
  }

  /**
   * Inicializa o gerenciador de modal
   */
  inicializar() {
    if (this.isInitialized) return;

    this.criarBackdrop();
    this.configurarEventos();
    this.isInitialized = true;
    
    Logger.info('ModalManager inicializado');
  }

  /**
   * Cria backdrop para modais
   */
  criarBackdrop() {
    this.backdrop = document.createElement('div');
    this.backdrop.className = `fixed inset-0 z-50 ${this.config.backdropClass}`;
    this.backdrop.style.display = 'none';
    this.backdrop.innerHTML = `
      <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity backdrop-blur-sm"></div>
      <div class="flex items-center justify-center min-h-screen px-4 py-8">
        <div class="modal-content relative bg-white rounded-xl shadow-2xl max-w-4xl w-full max-h-[85vh] overflow-hidden animate-slide-up">
          <!-- Conteúdo será inserido aqui -->
        </div>
      </div>
    `;
    
    document.body.appendChild(this.backdrop);
  }

  /**
   * Configura eventos globais
   */
  configurarEventos() {
    // Fechar modal com ESC
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && this.currentModal && this.config.closeOnEscape) {
        this.fecharModal();
      }
    });

    // Fechar modal clicando no backdrop
    this.backdrop.addEventListener('click', (e) => {
      if (e.target === this.backdrop || e.target.classList.contains('modal-backdrop')) {
        if (this.config.closeOnBackdrop) {
          this.fecharModal();
        }
      }
    });

    // Prevenir scroll do body quando modal estiver aberto
    this.backdrop.addEventListener('wheel', (e) => {
      e.preventDefault();
    }, { passive: false });
  }

  /**
   * Abre modal de detalhes do imóvel
   * @param {Object} imovel - Dados do imóvel
   */
  abrirModalImovel(imovel) {
    if (!imovel) {
      Logger.warn('Tentativa de abrir modal sem dados do imóvel');
      return;
    }

    Logger.info('Abrindo modal para imóvel:', imovel.codigo);

    const conteudo = this.criarConteudoModalImovel(imovel);
    this.abrirModal('imovel', conteudo, imovel);
  }

  /**
   * Cria conteúdo do modal de imóvel
   * @param {Object} imovel - Dados do imóvel
   * @returns {string} HTML do conteúdo
   */
  criarConteudoModalImovel(imovel) {
    const preco = AppUtils.formatarMoeda(imovel.valor);
    const endereco = AppUtils.formatarEndereco(imovel);
    const descricao = AppUtils.limparDescricao(imovel.descricao);
    const especificacoes = this.obterEspecificacoes(imovel);
    const galeria = this.criarGaleriaImagens(imovel.imagens || []);

    return `
      <!-- Header do Modal -->
      <div class="bg-primary text-white p-4 flex justify-between items-center">
        <h3 class="text-xl font-bold">${imovel.tipo} - ${imovel.bairro}</h3>
        <button onclick="modalManager.fecharModal()" class="text-white hover:text-gray-200 transition">
          <i class="fas fa-times text-xl"></i>
        </button>
      </div>
      
      <!-- Corpo do Modal -->
      <div class="p-4 overflow-y-auto" style="max-height: calc(85vh - 140px);">
        <!-- Preço Principal -->
        <div class="mb-4">
          <h4 class="text-3xl font-bold text-primary mb-2">${preco}</h4>
          <div class="flex items-center gap-2">
            <span class="px-3 py-1 rounded-full text-sm font-medium ${imovel.finalidade === 'Venda' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'}">
              ${imovel.finalidade}
            </span>
            <span class="text-gray-500 text-sm">Ref: ${imovel.referencia}</span>
          </div>
        </div>
        
        <!-- Endereço -->
        <div class="mb-6">
          <p class="text-gray-600 flex items-center">
            <i class="fas fa-map-marker-alt mr-2 text-primary"></i>
            ${endereco}
          </p>
        </div>
        
        <!-- Galeria de Imagens -->
        <div class="mb-6">
          <h5 class="text-lg font-semibold mb-3 flex items-center">
            <i class="fas fa-images mr-2 text-primary"></i>
            Fotos do Imóvel
          </h5>
          ${galeria}
        </div>
        
        <!-- Especificações -->
        <div class="mb-6">
          <h5 class="text-lg font-semibold mb-3 flex items-center">
            <i class="fas fa-list mr-2 text-primary"></i>
            Especificações
          </h5>
          <div class="bg-gray-50 rounded-lg p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              ${especificacoes.map(spec => `
                <div class="flex items-center">
                  <i class="${spec.icon} text-primary mr-3" style="width: 20px;"></i>
                  <span class="text-sm">
                    <strong>${spec.label}:</strong> 
                    <span class="text-gray-700">${spec.value}</span>
                  </span>
                </div>
              `).join('')}
            </div>
          </div>
        </div>
        
        <!-- Descrição -->
        <div class="mb-6">
          <h5 class="text-lg font-semibold mb-3 flex items-center">
            <i class="fas fa-file-alt mr-2 text-primary"></i>
            Descrição
          </h5>
          <div class="bg-gray-50 rounded-lg p-4">
            <p class="text-gray-700 leading-relaxed">${descricao}</p>
          </div>
        </div>

        <!-- Mapa de Localização -->
        ${this.criarMapaLocalizacao(imovel)}
      </div>
      
      <!-- Footer do Modal -->
      <div class="bg-gray-50 px-4 py-3 flex flex-col sm:flex-row justify-between items-center gap-3 border-t">
        <div class="flex items-center gap-3">
          <button onclick="modalManager.compartilharImovel(${imovel.codigo})" 
                  class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition flex items-center">
            <i class="fas fa-share-alt mr-2"></i>
            Compartilhar
          </button>
          <button onclick="modalManager.favoritarImovel(${imovel.codigo})" 
                  class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg transition flex items-center">
            <i class="fas fa-heart mr-2"></i>
            Favoritar
          </button>
        </div>
        <div class="flex items-center gap-3">
          <button onclick="modalManager.fecharModal()" 
                  class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 rounded-lg transition">
            Fechar
          </button>
          <button onclick="modalManager.entrarEmContato(${imovel.codigo})" 
                  class="px-4 py-2 bg-primary hover:bg-blue-700 text-white rounded-lg transition flex items-center">
            <i class="fas fa-phone mr-2"></i>
            Entrar em Contato
          </button>
        </div>
      </div>
    `;
  }

  /**
   * Obtém especificações formatadas do imóvel
   * @param {Object} imovel - Dados do imóvel
   * @returns {Array} Lista de especificações
   */
  obterEspecificacoes(imovel) {
    const specs = [
      { icon: 'fas fa-home', label: 'Tipo', value: imovel.tipo || 'N/A' },
      { icon: 'fas fa-calendar-alt', label: 'Finalidade', value: imovel.finalidade || 'N/A' },
      { icon: 'fas fa-bed', label: 'Dormitórios', value: imovel.dormitorios || '0' },
      { icon: 'fas fa-bath', label: 'Banheiros', value: imovel.banheiros || '0' },
      { icon: 'fas fa-car', label: 'Garagem', value: imovel.garagem || '0' },
      { icon: 'fas fa-couch', label: 'Salas', value: imovel.salas || '0' }
    ];

    if (imovel.suites) {
      specs.push({ icon: 'fas fa-bed', label: 'Suítes', value: imovel.suites });
    }

    if (imovel.area_total) {
      specs.push({ icon: 'fas fa-ruler-combined', label: 'Área Total', value: `${imovel.area_total} m²` });
    }

    if (imovel.terreno) {
      specs.push({ icon: 'fas fa-map', label: 'Terreno', value: `${imovel.terreno} m²` });
    }

    if (imovel.ano_construcao) {
      specs.push({ icon: 'fas fa-calendar', label: 'Ano de Construção', value: imovel.ano_construcao });
    }

    return specs.filter(spec => spec.value && spec.value !== 'N/A' && spec.value !== '0');
  }

  /**
   * Cria galeria de imagens
   * @param {Array} imagens - Lista de URLs das imagens
   * @returns {string} HTML da galeria
   */
  criarGaleriaImagens(imagens) {
    if (!imagens || imagens.length === 0) {
      return `
        <div class="text-center py-8 text-gray-500 bg-gray-50 rounded-lg">
          <i class="fas fa-image text-4xl mb-2"></i>
          <p>Nenhuma imagem disponível</p>
        </div>
      `;
    }

    const imagensOtimizadas = imagens.slice(0, 12).map((img, index) => {
      const imagemUrl = AppUtils.otimizarImagemUrl(img, 200, 150);
      return `
        <div class="relative group cursor-pointer" onclick="modalManager.abrirGaleriaCompleta(${index}, ${JSON.stringify(imagens).replace(/"/g, '&quot;')})">
          <img src="${imagemUrl}" 
               class="w-full h-24 object-cover rounded-lg transition-transform duration-300 group-hover:scale-105" 
               onerror="this.parentElement.style.display='none'"
               loading="lazy">
          <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all duration-300 rounded-lg flex items-center justify-center">
            <i class="fas fa-expand text-white opacity-0 group-hover:opacity-100 transition-opacity duration-300"></i>
          </div>
        </div>
      `;
    });

    return `
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
        ${imagensOtimizadas.join('')}
        ${imagens.length > 12 ? `
          <div class="bg-gray-100 rounded-lg h-24 flex items-center justify-center text-gray-500">
            <span class="text-sm">+${imagens.length - 12} fotos</span>
          </div>
        ` : ''}
      </div>
    `;
  }

  /**
   * Cria mapa de localização do imóvel
   * @param {Object} imovel - Dados do imóvel
   * @returns {string} HTML do mapa
   */
  criarMapaLocalizacao(imovel) {
    if (!AppUtils.validarCoordenadas(imovel.lat, imovel.lng)) {
      return '';
    }

    return `
      <div class="mb-6">
        <h5 class="text-lg font-semibold mb-3 flex items-center">
          <i class="fas fa-map-marker-alt mr-2 text-primary"></i>
          Localização
        </h5>
        <div class="bg-gray-50 rounded-lg p-4">
          <div id="modal-map-${imovel.codigo}" class="h-64 rounded-lg"></div>
          <div class="mt-3 flex items-center justify-between">
            <span class="text-sm text-gray-600">
              Coordenadas: ${parseFloat(imovel.lat).toFixed(6)}, ${parseFloat(imovel.lng).toFixed(6)}
            </span>
            <button onclick="modalManager.abrirMapaCompleto(${imovel.lat}, ${imovel.lng})" 
                    class="text-primary hover:text-blue-700 text-sm">
              Ver mapa completo
            </button>
          </div>
        </div>
      </div>
    `;
  }

  /**
   * Abre modal genérico
   * @param {string} id - ID do modal
   * @param {string} conteudo - Conteúdo HTML
   * @param {Object} dados - Dados associados
   */
  abrirModal(id, conteudo, dados = null) {
    if (!this.isInitialized) {
      this.inicializar();
    }

    // Fecha modal atual se existir
    if (this.currentModal) {
      this.fecharModal();
    }

    // Armazena dados do modal
    this.modals.set(id, {
      conteudo,
      dados,
      timestamp: Date.now()
    });

    // Insere conteúdo
    const modalContent = this.backdrop.querySelector('.modal-content');
    modalContent.innerHTML = conteudo;

    // Mostra modal
    this.backdrop.style.display = 'block';
    document.body.classList.add('overflow-hidden');
    
    // Força reflow para animação
    this.backdrop.offsetHeight;
    
    this.currentModal = id;

    // Inicializa componentes específicos após mostrar modal
    setTimeout(() => {
      this.inicializarComponentesModal(dados);
    }, 100);

    Logger.info(`Modal ${id} aberto`);
  }

  /**
   * Inicializa componentes específicos do modal
   * @param {Object} dados - Dados do modal
   */
  inicializarComponentesModal(dados) {
    if (dados && dados.lat && dados.lng) {
      this.inicializarMapaModal(dados);
    }
  }

  /**
   * Inicializa mapa dentro do modal
   * @param {Object} imovel - Dados do imóvel
   */
  inicializarMapaModal(imovel) {
    const mapId = `modal-map-${imovel.codigo}`;
    const mapContainer = document.getElementById(mapId);
    
    if (!mapContainer) return;

    try {
      const lat = parseFloat(imovel.lat);
      const lng = parseFloat(imovel.lng);
      
      const modalMap = L.map(mapId, {
        center: [lat, lng],
        zoom: 16,
        zoomControl: true,
        attributionControl: false
      });

      const config = ConfigUtils.getMapConfig();
      L.tileLayer(config.TILE_LAYER, {
        attribution: config.ATTRIBUTION,
        maxZoom: config.MAX_ZOOM
      }).addTo(modalMap);

      // Adiciona marker do imóvel
      const marker = L.marker([lat, lng]).addTo(modalMap);
      marker.bindPopup(`
        <div class="text-center">
          <strong>${imovel.tipo}</strong><br>
          ${imovel.bairro}, ${imovel.cidade}
        </div>
      `).openPopup();

      Logger.info('Mapa do modal inicializado');

    } catch (error) {
      Logger.error('Erro ao inicializar mapa do modal:', error);
      mapContainer.innerHTML = '<p class="text-center text-gray-500 py-8">Erro ao carregar mapa</p>';
    }
  }

  /**
   * Fecha modal atual
   */
  fecharModal() {
    if (!this.currentModal) return;

    Logger.info(`Fechando modal ${this.currentModal}`);

    // Esconde modal
    this.backdrop.style.display = 'none';
    document.body.classList.remove('overflow-hidden');

    // Limpa conteúdo
    const modalContent = this.backdrop.querySelector('.modal-content');
    modalContent.innerHTML = '';

    // Remove dados do modal
    this.modals.delete(this.currentModal);
    this.currentModal = null;
  }

  /**
   * Abre galeria completa de imagens
   * @param {number} indiceInicial - Índice da imagem inicial
   * @param {Array} imagens - Lista de imagens
   */
  abrirGaleriaCompleta(indiceInicial, imagens) {
    // Implementação da galeria completa
    Logger.info('Abrindo galeria completa:', { indiceInicial, total: imagens.length });
    
    // Por enquanto, abre a imagem em nova aba
    if (imagens[indiceInicial]) {
      window.open(imagens[indiceInicial], '_blank');
    }
  }

  /**
   * Abre mapa completo
   * @param {number} lat - Latitude
   * @param {number} lng - Longitude
   */
  abrirMapaCompleto(lat, lng) {
    const url = `https://www.openstreetmap.org/?mlat=${lat}&mlon=${lng}&zoom=16`;
    window.open(url, '_blank');
  }

  /**
   * Compartilha imóvel
   * @param {number} codigo - Código do imóvel
   */
  async compartilharImovel(codigo) {
    const url = `${window.location.origin}${window.location.pathname}?imovel=${codigo}`;
    const dados = {
      title: 'Confira este imóvel!',
      text: 'Encontrei este imóvel interessante na Exclusiva Imóveis',
      url: url
    };

    const sucesso = await AppUtils.compartilhar(dados);
    
    if (sucesso) {
      this.mostrarToast('Link compartilhado com sucesso!', 'success');
    } else {
      // Fallback: copiar para clipboard
      const copiado = await AppUtils.copiarTexto(url);
      if (copiado) {
        this.mostrarToast('Link copiado para a área de transferência!', 'success');
      } else {
        this.mostrarToast('Erro ao compartilhar. Tente novamente.', 'error');
      }
    }
  }

  /**
   * Favorita imóvel
   * @param {number} codigo - Código do imóvel
   */
  favoritarImovel(codigo) {
    // Implementar lógica de favoritos
    Logger.info('Favoritando imóvel:', codigo);
    this.mostrarToast('Imóvel adicionado aos favoritos!', 'success');
  }

  /**
   * Entra em contato sobre imóvel
   * @param {number} codigo - Código do imóvel
   */
  entrarEmContato(codigo) {
    // Implementar lógica de contato
    Logger.info('Entrando em contato sobre imóvel:', codigo);
    
    const telefone = '31999999999'; // Número da imobiliária
    const mensagem = `Olá! Tenho interesse no imóvel código ${codigo}. Poderia me dar mais informações?`;
    const url = `https://wa.me/${telefone}?text=${encodeURIComponent(mensagem)}`;
    
    window.open(url, '_blank');
  }

  /**
   * Mostra toast notification
   * @param {string} mensagem - Mensagem
   * @param {string} tipo - Tipo (success, error, info)
   */
  mostrarToast(mensagem, tipo = 'info') {
    window.dispatchEvent(new CustomEvent('showToast', {
      detail: { message: mensagem, type: tipo }
    }));
  }

  /**
   * Obtém dados do modal atual
   * @returns {Object|null} Dados do modal
   */
  obterDadosModalAtual() {
    if (!this.currentModal) return null;
    return this.modals.get(this.currentModal)?.dados || null;
  }

  /**
   * Verifica se há modal aberto
   * @returns {boolean} True se há modal aberto
   */
  temModalAberto() {
    return this.currentModal !== null;
  }
}

// Instância global do gerenciador de modal
const modalManager = new ModalManager();

// Exportar globalmente
window.ModalManager = ModalManager;
window.modalManager = modalManager;

// Função global para compatibilidade com popup do mapa
window.abrirModalGlobal = function(codigo) {
  // Busca o imóvel nos dados da aplicação Vue
  if (window.vueApp && window.vueApp.todosImoveis) {
    const imovel = window.vueApp.todosImoveis.find(i => i.codigo == codigo);
    if (imovel) {
      modalManager.abrirModalImovel(imovel);
    }
  }
};