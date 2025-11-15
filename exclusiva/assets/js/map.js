/**
 * Gerenciador do Mapa
 * Centraliza toda a lógica relacionada ao mapa Leaflet
 */

class MapManager {
  constructor() {
    this.map = null;
    this.markersLayer = null;
    this.userMarker = null;
    this.config = ConfigUtils.getMapConfig();
    this.isInitialized = false;
  }

  /**
   * Inicializa o mapa
   * @param {string} containerId - ID do container do mapa
   * @returns {Promise<void>}
   */
  async inicializar(containerId = 'map') {
    try {
      Logger.info('Inicializando mapa...');
      
      // Verifica se o container existe
      const container = document.getElementById(containerId);
      if (!container) {
        throw new Error(`Container do mapa não encontrado: ${containerId}`);
      }

      // Cria o mapa
      this.map = L.map(containerId, {
        center: this.config.DEFAULT_CENTER,
        zoom: this.config.DEFAULT_ZOOM,
        zoomControl: true,
        attributionControl: true,
        preferCanvas: true // Melhor performance
      });

      // Adiciona camada de tiles
      L.tileLayer(this.config.TILE_LAYER, {
        attribution: this.config.ATTRIBUTION,
        maxZoom: this.config.MAX_ZOOM,
        tileSize: 256,
        zoomOffset: 0
      }).addTo(this.map);

      // Cria layer para markers
      this.markersLayer = L.layerGroup().addTo(this.map);

      // Configura eventos do mapa
      this.configurarEventos();

      // Adiciona controles customizados
      this.adicionarControles();

      this.isInitialized = true;
      Logger.info('Mapa inicializado com sucesso');

    } catch (error) {
      Logger.error('Erro ao inicializar mapa:', error);
      throw error;
    }
  }

  /**
   * Configura eventos do mapa
   */
  configurarEventos() {
    if (!this.map) return;

    // Evento de clique no mapa
    this.map.on('click', (e) => {
      Logger.info('Clique no mapa:', e.latlng);
      this.onMapClick(e);
    });

    // Evento de mudança de zoom
    this.map.on('zoomend', () => {
      const zoom = this.map.getZoom();
      Logger.info('Zoom alterado para:', zoom);
      this.onZoomChange(zoom);
    });

    // Evento de movimento do mapa
    this.map.on('moveend', () => {
      const center = this.map.getCenter();
      Logger.info('Centro do mapa:', center);
      this.onMapMove(center);
    });
  }

  /**
   * Adiciona controles customizados ao mapa
   */
  adicionarControles() {
    if (!this.map) return;

    // Controle de localização
    const localizacaoControl = L.Control.extend({
      options: { position: 'topright' },
      
      onAdd: () => {
        const container = L.DomUtil.create('div', 'leaflet-bar leaflet-control leaflet-control-custom');
        container.style.backgroundColor = 'white';
        container.style.width = '40px';
        container.style.height = '40px';
        container.style.cursor = 'pointer';
        container.innerHTML = '<i class="fas fa-location-arrow" style="line-height: 40px; text-align: center; display: block; color: #1e40af;"></i>';
        container.title = 'Minha localização';
        
        container.onclick = () => this.obterLocalizacaoUsuario();
        
        return container;
      }
    });

    this.map.addControl(new localizacaoControl());

    // Controle de tela cheia
    const fullscreenControl = L.Control.extend({
      options: { position: 'topright' },
      
      onAdd: () => {
        const container = L.DomUtil.create('div', 'leaflet-bar leaflet-control leaflet-control-custom');
        container.style.backgroundColor = 'white';
        container.style.width = '40px';
        container.style.height = '40px';
        container.style.cursor = 'pointer';
        container.innerHTML = '<i class="fas fa-expand" style="line-height: 40px; text-align: center; display: block; color: #1e40af;"></i>';
        container.title = 'Tela cheia';
        
        container.onclick = () => this.toggleFullscreen();
        
        return container;
      }
    });

    this.map.addControl(new fullscreenControl());
  }

  /**
   * Atualiza markers no mapa
   * @param {Array} imoveis - Lista de imóveis
   */
  atualizarMarkers(imoveis) {
    if (!this.map || !this.markersLayer) return;

    try {
      Logger.info(`Atualizando ${imoveis.length} markers no mapa...`);
      
      // Limpa markers existentes
      this.markersLayer.clearLayers();

      const bounds = [];
      const markersValidos = [];

      imoveis.forEach(imovel => {
        if (!AppUtils.validarCoordenadas(imovel.lat, imovel.lng)) {
          Logger.warn('Coordenadas inválidas para imóvel:', imovel.codigo);
          return;
        }

        const lat = parseFloat(imovel.lat);
        const lng = parseFloat(imovel.lng);

        // Cria marker customizado
        const marker = this.criarMarker(lat, lng, imovel);
        marker.addTo(this.markersLayer);

        bounds.push([lat, lng]);
        markersValidos.push(imovel);
      });

      // Ajusta visualização para mostrar todos os markers
      if (bounds.length > 0) {
        if (bounds.length === 1) {
          this.map.setView(bounds[0], 15);
        } else {
          this.map.fitBounds(bounds, { 
            padding: [20, 20],
            maxZoom: 16
          });
        }
      }

      Logger.info(`${markersValidos.length} markers adicionados com sucesso`);

    } catch (error) {
      Logger.error('Erro ao atualizar markers:', error);
    }
  }

  /**
   * Cria marker customizado para imóvel
   * @param {number} lat - Latitude
   * @param {number} lng - Longitude
   * @param {Object} imovel - Dados do imóvel
   * @returns {L.Marker} Marker do Leaflet
   */
  criarMarker(lat, lng, imovel) {
    // Ícone customizado baseado na finalidade
    const iconColor = imovel.finalidade === 'Venda' ? '#10b981' : '#3b82f6';
    const iconHtml = `
      <div style="
        background-color: ${iconColor};
        width: 30px;
        height: 30px;
        border-radius: 50%;
        border: 3px solid white;
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 12px;
      ">
        ${imovel.finalidade === 'Venda' ? 'V' : 'L'}
      </div>
    `;

    const customIcon = L.divIcon({
      html: iconHtml,
      className: 'custom-marker',
      iconSize: [30, 30],
      iconAnchor: [15, 15],
      popupAnchor: [0, -15]
    });

    const marker = L.marker([lat, lng], { icon: customIcon });

    // Cria popup
    const popupContent = this.criarPopupContent(imovel);
    marker.bindPopup(popupContent, {
      maxWidth: 280,
      className: 'custom-popup'
    });

    // Eventos do marker
    marker.on('mouseover', () => {
      marker.openPopup();
    });

    return marker;
  }

  /**
   * Cria conteúdo do popup
   * @param {Object} imovel - Dados do imóvel
   * @returns {string} HTML do popup
   */
  criarPopupContent(imovel) {
    const preco = AppUtils.formatarMoeda(imovel.valor);
    const endereco = AppUtils.formatarEndereco(imovel);
    const imagemUrl = AppUtils.otimizarImagemUrl(imovel.thumb_url, 250, 150);

    return `
      <div class="popup-content" style="text-align: center; font-family: inherit;">
        <div style="position: relative; margin-bottom: 12px;">
          <img src="${imagemUrl}" 
               style="width: 100%; height: 120px; object-fit: cover; border-radius: 8px;" 
               onerror="this.style.display='none'">
          <div style="
            position: absolute;
            top: 8px;
            left: 8px;
            background: ${imovel.finalidade === 'Venda' ? '#10b981' : '#3b82f6'};
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
          ">
            ${imovel.finalidade}
          </div>
        </div>
        
        <h6 style="font-size: 18px; font-weight: bold; color: #1e40af; margin: 0 0 8px 0;">
          ${preco}
        </h6>
        
        <p style="font-weight: bold; margin: 0 0 4px 0; color: #374151;">
          ${imovel.tipo}
        </p>
        
        <p style="color: #6b7280; font-size: 13px; margin: 0 0 8px 0;">
          ${imovel.bairro}, ${imovel.cidade}
        </p>
        
        <div style="display: flex; justify-content: center; gap: 12px; margin-bottom: 12px; font-size: 12px; color: #6b7280;">
          ${imovel.dormitorios ? `<span><i class="fas fa-bed"></i> ${imovel.dormitorios}</span>` : ''}
          ${imovel.banheiros ? `<span><i class="fas fa-bath"></i> ${imovel.banheiros}</span>` : ''}
          ${imovel.garagem ? `<span><i class="fas fa-car"></i> ${imovel.garagem}</span>` : ''}
        </div>
        
        <button 
          onclick="window.abrirModalGlobal(${imovel.codigo})" 
          style="
            background: #1e40af;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
          "
          onmouseover="this.style.backgroundColor='#1d4ed8'"
          onmouseout="this.style.backgroundColor='#1e40af'">
          <i class="fas fa-eye"></i> Ver Detalhes
        </button>
      </div>
    `;
  }

  /**
   * Obtém localização do usuário
   */
  async obterLocalizacaoUsuario() {
    if (!navigator.geolocation) {
      Logger.warn('Geolocalização não suportada');
      return;
    }

    try {
      const position = await new Promise((resolve, reject) => {
        navigator.geolocation.getCurrentPosition(resolve, reject, {
          enableHighAccuracy: true,
          timeout: 10000,
          maximumAge: 300000 // 5 minutos
        });
      });

      const { latitude, longitude } = position.coords;
      Logger.info('Localização obtida:', { latitude, longitude });

      // Remove marker anterior do usuário
      if (this.userMarker) {
        this.map.removeLayer(this.userMarker);
      }

      // Adiciona marker do usuário
      const userIcon = L.divIcon({
        html: '<div style="background: #ef4444; width: 20px; height: 20px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3);"></div>',
        className: 'user-marker',
        iconSize: [20, 20],
        iconAnchor: [10, 10]
      });

      this.userMarker = L.marker([latitude, longitude], { icon: userIcon })
        .addTo(this.map)
        .bindPopup('Sua localização')
        .openPopup();

      // Centraliza mapa na localização do usuário
      this.map.setView([latitude, longitude], 15);

    } catch (error) {
      Logger.error('Erro ao obter localização:', error);
      
      let mensagem = 'Não foi possível obter sua localização.';
      if (error.code === 1) {
        mensagem = 'Permissão de localização negada.';
      } else if (error.code === 2) {
        mensagem = 'Localização indisponível.';
      } else if (error.code === 3) {
        mensagem = 'Timeout ao obter localização.';
      }
      
      // Emite evento para mostrar toast de erro
      window.dispatchEvent(new CustomEvent('showToast', {
        detail: { type: 'error', message: mensagem }
      }));
    }
  }

  /**
   * Toggle tela cheia
   */
  toggleFullscreen() {
    const mapContainer = this.map.getContainer();
    
    if (!document.fullscreenElement) {
      mapContainer.requestFullscreen().then(() => {
        mapContainer.style.position = 'fixed';
        mapContainer.style.top = '0';
        mapContainer.style.left = '0';
        mapContainer.style.width = '100vw';
        mapContainer.style.height = '100vh';
        mapContainer.style.zIndex = '9999';
        
        setTimeout(() => this.map.invalidateSize(), 100);
      });
    } else {
      document.exitFullscreen().then(() => {
        mapContainer.style.position = '';
        mapContainer.style.top = '';
        mapContainer.style.left = '';
        mapContainer.style.width = '';
        mapContainer.style.height = '';
        mapContainer.style.zIndex = '';
        
        setTimeout(() => this.map.invalidateSize(), 100);
      });
    }
  }

  /**
   * Centraliza mapa em coordenadas específicas
   * @param {number} lat - Latitude
   * @param {number} lng - Longitude
   * @param {number} zoom - Nível de zoom
   */
  centralizarEm(lat, lng, zoom = 15) {
    if (!this.map) return;
    
    if (!AppUtils.validarCoordenadas(lat, lng)) {
      Logger.warn('Coordenadas inválidas para centralizar:', { lat, lng });
      return;
    }

    this.map.setView([lat, lng], zoom);
  }

  /**
   * Redimensiona o mapa
   */
  redimensionar() {
    if (this.map) {
      setTimeout(() => {
        this.map.invalidateSize();
      }, 100);
    }
  }

  /**
   * Destrói o mapa
   */
  destruir() {
    if (this.map) {
      this.map.remove();
      this.map = null;
      this.markersLayer = null;
      this.userMarker = null;
      this.isInitialized = false;
      Logger.info('Mapa destruído');
    }
  }

  // Eventos customizados (podem ser sobrescritos)
  onMapClick(e) {
    // Override em implementações específicas
  }

  onZoomChange(zoom) {
    // Override em implementações específicas
  }

  onMapMove(center) {
    // Override em implementações específicas
  }
}

// Instância global do gerenciador de mapa
const mapManager = new MapManager();

// Exportar globalmente
window.MapManager = MapManager;
window.mapManager = mapManager;