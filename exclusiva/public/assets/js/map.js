/**
 * Gerenciador do Mapa
 * Centraliza toda a lógica relacionada ao mapa Leaflet com filtro por zoom
 */

class MapManager {
  constructor() {
    this.map = null;
    this.markersLayer = null;
    this.userMarker = null;
    this.config = ConfigUtils.getMapConfig();
    this.isInitialized = false;
    this.currentLayer = 'SATELLITE';
    this.tileLayers = {};
    this.zoomFilterEnabled = true;
    this.lastBounds = null;
    this.onBoundsChangeCallback = null;
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
        minZoom: this.config.MIN_ZOOM,
        maxZoom: this.config.MAX_ZOOM,
        zoomControl: true,
        attributionControl: true,
        preferCanvas: true
      });

      // Inicializa camadas de tiles
      this.inicializarCamadas();

      // Adiciona camada padrão (satélite)
      this.adicionarCamadaPadrao();

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
   * Inicializa todas as camadas de tiles disponíveis
   */
  inicializarCamadas() {
    const layers = this.config.TILE_LAYERS;
    
    Object.keys(layers).forEach(key => {
      const layerConfig = layers[key];
      this.tileLayers[key] = L.tileLayer(layerConfig.url, {
        attribution: layerConfig.attribution,
        maxZoom: this.config.MAX_ZOOM,
        tileSize: 256,
        zoomOffset: 0
      });
    });

    Logger.info('Camadas de tiles inicializadas:', Object.keys(this.tileLayers));
  }

  /**
   * Adiciona camada padrão ao mapa
   */
  adicionarCamadaPadrao() {
    const defaultLayer = this.config.DEFAULT_LAYER;
    if (this.tileLayers[defaultLayer]) {
      this.tileLayers[defaultLayer].addTo(this.map);
      this.currentLayer = defaultLayer;
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
      this.verificarFiltroZoom();
    });

    // Evento de movimento do mapa (para filtro por área visível)
    this.map.on('moveend', () => {
      const center = this.map.getCenter();
      const bounds = this.map.getBounds();
      Logger.info('Centro do mapa:', center);
      this.onMapMove(center, bounds);
      this.verificarFiltroZoom();
    });

    // Evento de início de movimento (para feedback visual)
    this.map.on('movestart', () => {
      this.onMoveStart();
    });
  }

  /**
   * Verifica se deve aplicar filtro por zoom/área
   */
  verificarFiltroZoom() {
    if (!this.zoomFilterEnabled || !this.config.ZOOM_FILTER.ENABLED) return;

    const zoom = this.map.getZoom();
    const minZoom = this.config.ZOOM_FILTER.MIN_ZOOM_FOR_FILTER;

    if (zoom >= minZoom) {
      const bounds = this.map.getBounds();
      
      // Verifica se os bounds mudaram significativamente
      if (!this.lastBounds || !this.boundsEqual(bounds, this.lastBounds)) {
        this.lastBounds = bounds;
        
        // Chama callback se definido
        if (this.onBoundsChangeCallback) {
          this.onBoundsChangeCallback(bounds, zoom);
        }

        // Mostra informação sobre filtro por área
        this.mostrarInfoFiltroArea(true);
      }
    } else {
      this.mostrarInfoFiltroArea(false);
      
      // Remove filtro por área
      if (this.onBoundsChangeCallback) {
        this.onBoundsChangeCallback(null, zoom);
      }
    }
  }

  /**
   * Compara se dois bounds são iguais (com tolerância)
   */
  boundsEqual(bounds1, bounds2) {
    const tolerance = 0.001;
    return Math.abs(bounds1.getNorth() - bounds2.getNorth()) < tolerance &&
           Math.abs(bounds1.getSouth() - bounds2.getSouth()) < tolerance &&
           Math.abs(bounds1.getEast() - bounds2.getEast()) < tolerance &&
           Math.abs(bounds1.getWest() - bounds2.getWest()) < tolerance;
  }

  /**
   * Mostra/esconde informação sobre filtro por área
   */
  mostrarInfoFiltroArea(mostrar) {
    let infoElement = document.querySelector('.zoom-filter-info');
    
    if (mostrar && !infoElement) {
      infoElement = document.createElement('div');
      infoElement.className = 'zoom-filter-info';
      infoElement.innerHTML = `
        <i class="fas fa-info-circle mr-2"></i>
        Filtrando imóveis na área visível do mapa
      `;
      
      const mapContainer = this.map.getContainer().parentElement;
      mapContainer.insertBefore(infoElement, mapContainer.firstChild);
      
    } else if (!mostrar && infoElement) {
      infoElement.remove();
    }
  }

  /**
   * Adiciona controles customizados ao mapa
   */
  adicionarControles() {
    if (!this.map) return;

    // Controle de camadas
    this.adicionarControleCamadas();

    // Controle de localização
    this.adicionarControleLocalizacao();

    // Controle de tela cheia
    this.adicionarControleTelacheia();

    // Controle de filtro por zoom
    this.adicionarControleFiltroZoom();
  }

  /**
   * Adiciona controle de seleção de camadas
   */
  adicionarControleCamadas() {
    const layerControl = L.Control.extend({
      options: { position: 'topright' },
      
      onAdd: (map) => {
        const container = L.DomUtil.create('div', 'map-layer-control');
        
        Object.keys(this.tileLayers).forEach(key => {
          const layerConfig = this.config.TILE_LAYERS[key];
          const button = L.DomUtil.create('button', 'layer-button', container);
          button.innerHTML = layerConfig.name;
          button.title = `Trocar para ${layerConfig.name}`;
          
          if (key === this.currentLayer) {
            button.classList.add('active');
          }
          
          L.DomEvent.on(button, 'click', (e) => {
            L.DomEvent.stopPropagation(e);
            this.trocarCamada(key);
            
            // Atualiza botões ativos
            container.querySelectorAll('.layer-button').forEach(btn => {
              btn.classList.remove('active');
            });
            button.classList.add('active');
          });
        });
        
        return container;
      }
    });

    this.map.addControl(new layerControl());
  }

  /**
   * Adiciona controle de localização
   */
  adicionarControleLocalizacao() {
    const localizacaoControl = L.Control.extend({
      options: { position: 'topright' },
      
      onAdd: () => {
        const container = L.DomUtil.create('div', 'leaflet-bar leaflet-control leaflet-control-custom');
        container.style.backgroundColor = 'rgba(255, 255, 255, 0.95)';
        container.style.backdropFilter = 'blur(10px)';
        container.style.width = '40px';
        container.style.height = '40px';
        container.style.cursor = 'pointer';
        container.style.borderRadius = '8px';
        container.style.border = '1px solid rgba(255, 255, 255, 0.2)';
        container.innerHTML = '<i class="fas fa-location-arrow" style="line-height: 40px; text-align: center; display: block; color: #6366f1; font-size: 16px;"></i>';
        container.title = 'Minha localização';
        
        container.onclick = () => this.obterLocalizacaoUsuario();
        
        return container;
      }
    });

    this.map.addControl(new localizacaoControl());
  }

  /**
   * Adiciona controle de tela cheia
   */
  adicionarControleTelacheia() {
    const fullscreenControl = L.Control.extend({
      options: { position: 'topright' },
      
      onAdd: () => {
        const container = L.DomUtil.create('div', 'leaflet-bar leaflet-control leaflet-control-custom');
        container.style.backgroundColor = 'rgba(255, 255, 255, 0.95)';
        container.style.backdropFilter = 'blur(10px)';
        container.style.width = '40px';
        container.style.height = '40px';
        container.style.cursor = 'pointer';
        container.style.borderRadius = '8px';
        container.style.border = '1px solid rgba(255, 255, 255, 0.2)';
        container.innerHTML = '<i class="fas fa-expand" style="line-height: 40px; text-align: center; display: block; color: #6366f1; font-size: 16px;"></i>';
        container.title = 'Tela cheia';
        
        container.onclick = () => this.toggleFullscreen();
        
        return container;
      }
    });

    this.map.addControl(new fullscreenControl());
  }

  /**
   * Adiciona controle de toggle do filtro por zoom
   */
  adicionarControleFiltroZoom() {
    const zoomFilterControl = L.Control.extend({
      options: { position: 'topleft' },
      
      onAdd: () => {
        const container = L.DomUtil.create('div', 'leaflet-bar leaflet-control leaflet-control-custom');
        container.style.backgroundColor = 'rgba(255, 255, 255, 0.95)';
        container.style.backdropFilter = 'blur(10px)';
        container.style.width = '40px';
        container.style.height = '40px';
        container.style.cursor = 'pointer';
        container.style.borderRadius = '8px';
        container.style.border = '1px solid rgba(255, 255, 255, 0.2)';
        
        const updateIcon = () => {
          container.innerHTML = `<i class="fas fa-${this.zoomFilterEnabled ? 'filter' : 'filter'}" style="line-height: 40px; text-align: center; display: block; color: ${this.zoomFilterEnabled ? '#10b981' : '#6b7280'}; font-size: 16px;"></i>`;
          container.title = this.zoomFilterEnabled ? 'Desativar filtro por área' : 'Ativar filtro por área';
        };
        
        updateIcon();
        
        container.onclick = () => {
          this.zoomFilterEnabled = !this.zoomFilterEnabled;
          updateIcon();
          this.verificarFiltroZoom();
          
          // Emite evento para notificar mudança
          window.dispatchEvent(new CustomEvent('zoomFilterToggle', {
            detail: { enabled: this.zoomFilterEnabled }
          }));
        };
        
        return container;
      }
    });

    this.map.addControl(new zoomFilterControl());
  }

  /**
   * Troca a camada do mapa
   */
  trocarCamada(novaLayer) {
    if (!this.tileLayers[novaLayer] || novaLayer === this.currentLayer) return;

    // Remove camada atual
    if (this.tileLayers[this.currentLayer]) {
      this.map.removeLayer(this.tileLayers[this.currentLayer]);
    }

    // Adiciona nova camada
    this.tileLayers[novaLayer].addTo(this.map);
    this.currentLayer = novaLayer;

    Logger.info(`Camada alterada para: ${novaLayer}`);

    // Emite evento para notificar mudança
    window.dispatchEvent(new CustomEvent('layerChange', {
      detail: { layer: novaLayer, name: this.config.TILE_LAYERS[novaLayer].name }
    }));
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
      if (bounds.length > 0 && !this.zoomFilterEnabled) {
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
    // Ícone customizado baseado na finalidade com gradientes
    const isVenda = imovel.finalidade === 'Venda';
    const gradient = isVenda ? 
      'linear-gradient(135deg, #10b981 0%, #059669 100%)' : 
      'linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)';
    
    const iconHtml = `
      <div style="
        background: ${gradient};
        width: 36px;
        height: 36px;
        border-radius: 50%;
        border: 3px solid white;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 14px;
        position: relative;
        animation: float 3s ease-in-out infinite;
      ">
        <i class="fas fa-${isVenda ? 'tag' : 'key'}" style="font-size: 16px;"></i>
        <div style="
          position: absolute;
          top: -8px;
          right: -8px;
          background: ${isVenda ? '#f59e0b' : '#ec4899'};
          width: 16px;
          height: 16px;
          border-radius: 50%;
          border: 2px solid white;
          font-size: 10px;
          display: flex;
          align-items: center;
          justify-content: center;
        ">
          ${isVenda ? 'V' : 'L'}
        </div>
      </div>
    `;

    const customIcon = L.divIcon({
      html: iconHtml,
      className: 'custom-marker',
      iconSize: [36, 36],
      iconAnchor: [18, 18],
      popupAnchor: [0, -18]
    });

    const marker = L.marker([lat, lng], { icon: customIcon });

    // Cria popup
    const popupContent = this.criarPopupContent(imovel);
    marker.bindPopup(popupContent, {
      maxWidth: 300,
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
    const imagemUrl = AppUtils.otimizarImagemUrl(imovel.thumb_url, 280, 160);

    return `
      <div class="popup-content" style="text-align: center; font-family: inherit;">
        <div style="position: relative; margin-bottom: 16px;">
          <img src="${imagemUrl}" 
               style="width: 100%; height: 140px; object-fit: cover; border-radius: 12px;" 
               onerror="this.style.display='none'">
          <div style="
            position: absolute;
            top: 12px;
            left: 12px;
            background: ${imovel.finalidade === 'Venda' ? 'linear-gradient(135deg, #10b981 0%, #059669 100%)' : 'linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)'};
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
          ">
            ${imovel.finalidade}
          </div>
        </div>
        
        <h6 style="font-size: 20px; font-weight: bold; color: #6366f1; margin: 0 0 8px 0;">
          ${preco}
        </h6>
        
        <p style="font-weight: bold; margin: 0 0 4px 0; color: #374151; font-size: 16px;">
          ${imovel.tipo}
        </p>
        
        <p style="color: #6b7280; font-size: 14px; margin: 0 0 12px 0;">
          <i class="fas fa-map-marker-alt mr-1"></i>
          ${imovel.bairro}, ${imovel.cidade}
        </p>
        
        <div style="display: flex; justify-content: center; gap: 16px; margin-bottom: 16px; font-size: 13px; color: #6b7280;">
          ${imovel.dormitorios ? `<span><i class="fas fa-bed" style="color: #6366f1;"></i> ${imovel.dormitorios}</span>` : ''}
          ${imovel.banheiros ? `<span><i class="fas fa-bath" style="color: #6366f1;"></i> ${imovel.banheiros}</span>` : ''}
          ${imovel.garagem ? `<span><i class="fas fa-car" style="color: #6366f1;"></i> ${imovel.garagem}</span>` : ''}
        </div>
        
        <button 
          onclick="window.abrirModalGlobal(${imovel.codigo})" 
          style="
            background: linear-gradient(135deg, #6366f1 0%, #ec4899 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
          "
          onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(99, 102, 241, 0.4)'"
          onmouseout="this.style.transform='translateY(0px)'; this.style.boxShadow='0 4px 12px rgba(99, 102, 241, 0.3)'">
          <i class="fas fa-eye mr-2"></i> Ver Detalhes
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
          maximumAge: 300000
        });
      });

      const { latitude, longitude } = position.coords;
      Logger.info('Localização obtida:', { latitude, longitude });

      // Remove marker anterior do usuário
      if (this.userMarker) {
        this.map.removeLayer(this.userMarker);
      }

      // Adiciona marker do usuário com design moderno
      const userIcon = L.divIcon({
        html: `
          <div style="
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 3px solid white;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse 2s infinite;
          ">
            <i class="fas fa-user" style="color: white; font-size: 10px;"></i>
          </div>
        `,
        className: 'user-marker',
        iconSize: [24, 24],
        iconAnchor: [12, 12]
      });

      this.userMarker = L.marker([latitude, longitude], { icon: userIcon })
        .addTo(this.map)
        .bindPopup('📍 Sua localização')
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
   * Define callback para mudanças de bounds
   */
  setOnBoundsChangeCallback(callback) {
    this.onBoundsChangeCallback = callback;
  }

  /**
   * Obtém bounds atuais do mapa
   */
  getCurrentBounds() {
    return this.map ? this.map.getBounds() : null;
  }

  /**
   * Obtém zoom atual do mapa
   */
  getCurrentZoom() {
    return this.map ? this.map.getZoom() : 0;
  }

  /**
   * Verifica se um ponto está dentro dos bounds atuais
   */
  isPointInBounds(lat, lng) {
    if (!this.map) return true;
    
    const bounds = this.map.getBounds();
    return bounds.contains([lat, lng]);
  }

  /**
   * Centraliza mapa em coordenadas específicas
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

  onMapMove(center, bounds) {
    // Override em implementações específicas
  }

  onMoveStart() {
    // Adiciona feedback visual durante movimento
    if (this.map) {
      this.map.getContainer().style.cursor = 'grabbing';
    }
  }
}

// Instância global do gerenciador de mapa
const mapManager = new MapManager();

// Exportar globalmente
window.MapManager = MapManager;
window.mapManager = mapManager;