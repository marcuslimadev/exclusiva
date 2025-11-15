/**
 * Gerenciador de Modal Premium
 * Sistema completo de modal com mapa integrado
 */

class ModalManager {
  constructor() {
    this.modalAtivo = false;
    this.imovelAtual = null;
    this.mapaModal = null;
    this.markerModal = null;
  }

  /**
   * Inicializa o gerenciador de modal
   */
  inicializar() {
    Logger.info('Modal Manager inicializado');
  }

  /**
   * Abre modal do imóvel com mapa corrigido
   */
  abrirModalImovel(imovel) {
    this.modalAtivo = true;
    this.imovelAtual = imovel;
    
    // Aguarda o modal ser renderizado antes de inicializar o mapa
    setTimeout(() => {
      this.inicializarMapaModal(imovel);
    }, 300);
    
    Logger.info('Modal aberto para imóvel:', imovel.codigo);
  }

  /**
   * Inicializa mapa dentro do modal (corrigido)
   */
  inicializarMapaModal(imovel) {
    // Verifica se existe container do mapa no modal
    const mapContainer = document.getElementById('modal-map');
    if (!mapContainer) {
      Logger.warn('Container do mapa modal não encontrado');
      return;
    }

    // Remove mapa anterior se existir
    if (this.mapaModal) {
      this.mapaModal.remove();
      this.mapaModal = null;
      this.markerModal = null;
    }

    // Verifica coordenadas válidas
    if (!AppUtils.validarCoordenadas(imovel.lat, imovel.lng)) {
      mapContainer.innerHTML = `
        <div class="flex items-center justify-center h-64 bg-gray-100 rounded-lg">
          <div class="text-center text-gray-500">
            <i class="fas fa-map-marker-alt text-4xl mb-3"></i>
            <p>Localização não disponível</p>
          </div>
        </div>
      `;
      return;
    }

    try {
      const lat = parseFloat(imovel.lat);
      const lng = parseFloat(imovel.lng);

      // Cria novo mapa
      this.mapaModal = L.map('modal-map', {
        center: [lat, lng],
        zoom: 16,
        zoomControl: true,
        attributionControl: true
      });

      // Adiciona camada de tiles
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 18
      }).addTo(this.mapaModal);

      // Adiciona marker
      this.markerModal = L.marker([lat, lng]).addTo(this.mapaModal);
      
      // Popup do marker
      const popupContent = `
        <div class="text-center">
          <strong>${imovel.tipo}</strong><br>
          <span class="text-sm text-gray-600">${imovel.bairro}, ${imovel.cidade}</span><br>
          <span class="text-lg font-bold text-blue-600">${AppUtils.formatarMoeda(imovel.valor)}</span>
        </div>
      `;
      
      this.markerModal.bindPopup(popupContent).openPopup();

      // Força redimensionamento após criação
      setTimeout(() => {
        if (this.mapaModal) {
          this.mapaModal.invalidateSize();
        }
      }, 100);

      Logger.info('Mapa modal inicializado com sucesso');

    } catch (error) {
      Logger.error('Erro ao inicializar mapa modal:', error);
      
      // Fallback em caso de erro
      mapContainer.innerHTML = `
        <div class="flex items-center justify-center h-64 bg-red-50 rounded-lg border border-red-200">
          <div class="text-center text-red-600">
            <i class="fas fa-exclamation-triangle text-4xl mb-3"></i>
            <p>Erro ao carregar mapa</p>
            <p class="text-sm mt-2">Coordenadas: ${imovel.lat}, ${imovel.lng}</p>
          </div>
        </div>
      `;
    }
  }

  /**
   * Fecha modal e limpa mapa
   */
  fecharModal() {
    this.modalAtivo = false;
    this.imovelAtual = null;
    
    // Remove mapa modal
    if (this.mapaModal) {
      this.mapaModal.remove();
      this.mapaModal = null;
      this.markerModal = null;
    }
    
    Logger.info('Modal fechado e mapa limpo');
  }

  /**
   * Obtém especificações do imóvel
   */
  obterEspecificacoes(imovel) {
    const specs = [];

    if (imovel.dormitorios) {
      specs.push({
        label: 'Dormitórios',
        value: imovel.dormitorios,
        icon: 'fas fa-bed'
      });
    }

    if (imovel.banheiros) {
      specs.push({
        label: 'Banheiros',
        value: imovel.banheiros,
        icon: 'fas fa-bath'
      });
    }

    if (imovel.garagem) {
      specs.push({
        label: 'Vagas de Garagem',
        value: imovel.garagem,
        icon: 'fas fa-car'
      });
    }

    if (imovel.suites) {
      specs.push({
        label: 'Suítes',
        value: imovel.suites,
        icon: 'fas fa-bed'
      });
    }

    if (imovel.area_total) {
      specs.push({
        label: 'Área Total',
        value: `${imovel.area_total} m²`,
        icon: 'fas fa-expand-arrows-alt'
      });
    }

    if (imovel.area_privativa) {
      specs.push({
        label: 'Área Privativa',
        value: `${imovel.area_privativa} m²`,
        icon: 'fas fa-home'
      });
    }

    if (imovel.ano_construcao) {
      specs.push({
        label: 'Ano de Construção',
        value: imovel.ano_construcao,
        icon: 'fas fa-calendar-alt'
      });
    }

    if (imovel.condominio) {
      specs.push({
        label: 'Condomínio',
        value: AppUtils.formatarMoeda(imovel.condominio),
        icon: 'fas fa-building'
      });
    }

    if (imovel.iptu) {
      specs.push({
        label: 'IPTU',
        value: AppUtils.formatarMoeda(imovel.iptu),
        icon: 'fas fa-file-invoice-dollar'
      });
    }

    return specs;
  }

  /**
   * Redimensiona mapa modal
   */
  redimensionarMapaModal() {
    if (this.mapaModal) {
      setTimeout(() => {
        this.mapaModal.invalidateSize();
      }, 100);
    }
  }
}

// Instância global
const modalManager = new ModalManager();

// Exportar globalmente
window.ModalManager = ModalManager;
window.modalManager = modalManager;