/**
 * Utilitários gerais da aplicação
 * Funções auxiliares reutilizáveis
 */

const AppUtils = {
  
  /**
   * Formatação de moeda brasileira
   * @param {number|string} valor - Valor a ser formatado
   * @returns {string} Valor formatado em Real brasileiro
   */
  formatarMoeda(valor) {
    const numero = parseFloat(valor) || 0;
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: 'BRL',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(numero);
  },

  /**
   * Formatação de números
   * @param {number|string} numero - Número a ser formatado
   * @returns {string} Número formatado
   */
  formatarNumero(numero) {
    const num = parseFloat(numero) || 0;
    return new Intl.NumberFormat('pt-BR').format(num);
  },

  /**
   * Limpa e formata descrições de imóveis
   * @param {string} descricao - Descrição original
   * @returns {string} Descrição limpa
   */
  limparDescricao(descricao) {
    if (!descricao) return 'Descrição não disponível.';
    
    return descricao
      .replace(/&#\d+;/g, '') // Remove HTML entities numéricas
      .replace(/&[a-zA-Z]+;/g, '') // Remove HTML entities nomeadas
      .replace(/<[^>]*>/g, '') // Remove tags HTML
      .replace(/\s+/g, ' ') // Remove espaços extras
      .trim();
  },

  /**
   * Valida dados de imóvel
   * @param {Object} imovel - Objeto do imóvel
   * @returns {boolean} True se válido
   */
  validarImovel(imovel) {
    if (!imovel || typeof imovel !== 'object') return false;
    
    const camposObrigatorios = ['codigo', 'tipo', 'valor', 'cidade', 'bairro'];
    return camposObrigatorios.every(campo => imovel[campo]);
  },

  /**
   * Normaliza string para busca (remove acentos, converte para minúscula)
   * @param {string} str - String a ser normalizada
   * @returns {string} String normalizada
   */
  normalizarString(str) {
    if (!str) return '';
    return str
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .trim();
  },

  /**
   * Debounce para otimizar chamadas de função
   * @param {Function} func - Função a ser executada
   * @param {number} delay - Delay em millisegundos
   * @returns {Function} Função com debounce
   */
  debounce(func, delay) {
    let timeoutId;
    return function (...args) {
      clearTimeout(timeoutId);
      timeoutId = setTimeout(() => func.apply(this, args), delay);
    };
  },

  /**
   * Throttle para limitar execuções de função
   * @param {Function} func - Função a ser executada
   * @param {number} limit - Limite em millisegundos
   * @returns {Function} Função com throttle
   */
  throttle(func, limit) {
    let inThrottle;
    return function (...args) {
      if (!inThrottle) {
        func.apply(this, args);
        inThrottle = true;
        setTimeout(() => inThrottle = false, limit);
      }
    };
  },

  /**
   * Gera ID único
   * @returns {string} ID único
   */
  gerarId() {
    return Date.now().toString(36) + Math.random().toString(36).substr(2);
  },

  /**
   * Calcula distância entre duas coordenadas (Haversine)
   * @param {number} lat1 - Latitude 1
   * @param {number} lon1 - Longitude 1
   * @param {number} lat2 - Latitude 2
   * @param {number} lon2 - Longitude 2
   * @returns {number} Distância em quilômetros
   */
  calcularDistancia(lat1, lon1, lat2, lon2) {
    const R = 6371; // Raio da Terra em km
    const dLat = this.toRad(lat2 - lat1);
    const dLon = this.toRad(lon2 - lon1);
    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
              Math.cos(this.toRad(lat1)) * Math.cos(this.toRad(lat2)) *
              Math.sin(dLon / 2) * Math.sin(dLon / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
  },

  /**
   * Converte graus para radianos
   * @param {number} value - Valor em graus
   * @returns {number} Valor em radianos
   */
  toRad(value) {
    return value * Math.PI / 180;
  },

  /**
   * Valida coordenadas geográficas
   * @param {number} lat - Latitude
   * @param {number} lng - Longitude
   * @returns {boolean} True se válidas
   */
  validarCoordenadas(lat, lng) {
    const latitude = parseFloat(lat);
    const longitude = parseFloat(lng);
    
    return !isNaN(latitude) && !isNaN(longitude) &&
           latitude >= -90 && latitude <= 90 &&
           longitude >= -180 && longitude <= 180;
  },

  /**
   * Formata endereço completo
   * @param {Object} imovel - Objeto do imóvel
   * @returns {string} Endereço formatado
   */
  formatarEndereco(imovel) {
    if (!imovel) return '';
    
    const partes = [];
    
    if (imovel.logradouro) partes.push(imovel.logradouro);
    if (imovel.numero) partes.push(imovel.numero);
    if (imovel.bairro) partes.push(imovel.bairro);
    if (imovel.cidade) partes.push(imovel.cidade);
    if (imovel.estado) partes.push(imovel.estado);
    
    return partes.join(', ');
  },

  /**
   * Detecta dispositivo móvel
   * @returns {boolean} True se for dispositivo móvel
   */
  isMobile() {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
  },

  /**
   * Detecta suporte a WebP
   * @returns {Promise<boolean>} Promise que resolve com suporte a WebP
   */
  async supportsWebP() {
    return new Promise((resolve) => {
      const webP = new Image();
      webP.onload = webP.onerror = () => {
        resolve(webP.height === 2);
      };
      webP.src = 'data:image/webp;base64,UklGRjoAAABXRUJQVlA4IC4AAACyAgCdASoCAAIALmk0mk0iIiIiIgBoSygABc6WWgAA/veff/0PP8bA//LwYAAA';
    });
  },

  /**
   * Otimiza URL de imagem
   * @param {string} url - URL original
   * @param {number} width - Largura desejada
   * @param {number} height - Altura desejada
   * @returns {string} URL otimizada
   */
  otimizarImagemUrl(url, width = 400, height = 300) {
    if (!url) return `https://via.placeholder.com/${width}x${height}?text=Sem+Imagem`;
    
    // Se for placeholder, retornar como está
    if (url.includes('placeholder.com')) return url;
    
    // Para URLs do CDN, adicionar parâmetros de redimensionamento se suportado
    if (url.includes('cdn-imobibrasil.com.br')) {
      return `${url}?w=${width}&h=${height}&fit=crop&auto=format`;
    }
    
    return url;
  },

  /**
   * Cria elemento de loading skeleton
   * @param {string} type - Tipo do skeleton (text, image, card)
   * @returns {string} HTML do skeleton
   */
  criarSkeleton(type = 'text') {
    const skeletons = {
      text: '<div class="skeleton skeleton-text"></div>',
      image: '<div class="skeleton skeleton-image"></div>',
      card: `
        <div class="bg-white rounded-xl shadow-sm p-4">
          <div class="skeleton skeleton-image mb-4"></div>
          <div class="skeleton skeleton-text mb-2"></div>
          <div class="skeleton skeleton-text mb-2" style="width: 70%;"></div>
          <div class="skeleton skeleton-text" style="width: 50%;"></div>
        </div>
      `
    };
    
    return skeletons[type] || skeletons.text;
  },

  /**
   * Gerencia localStorage com fallback
   * @param {string} key - Chave
   * @param {any} value - Valor (opcional, para set)
   * @returns {any} Valor armazenado ou null
   */
  storage(key, value = undefined) {
    try {
      if (value !== undefined) {
        localStorage.setItem(key, JSON.stringify(value));
        return value;
      } else {
        const item = localStorage.getItem(key);
        return item ? JSON.parse(item) : null;
      }
    } catch (error) {
      Logger.warn('Erro ao acessar localStorage:', error);
      return null;
    }
  },

  /**
   * Copia texto para clipboard
   * @param {string} text - Texto a ser copiado
   * @returns {Promise<boolean>} Sucesso da operação
   */
  async copiarTexto(text) {
    try {
      await navigator.clipboard.writeText(text);
      return true;
    } catch (error) {
      Logger.warn('Erro ao copiar texto:', error);
      return false;
    }
  },

  /**
   * Compartilha conteúdo usando Web Share API
   * @param {Object} data - Dados para compartilhar
   * @returns {Promise<boolean>} Sucesso da operação
   */
  async compartilhar(data) {
    try {
      if (navigator.share) {
        await navigator.share(data);
        return true;
      } else {
        // Fallback para copiar URL
        if (data.url) {
          return await this.copiarTexto(data.url);
        }
      }
    } catch (error) {
      Logger.warn('Erro ao compartilhar:', error);
    }
    return false;
  }
};

// Exportar globalmente
window.AppUtils = AppUtils;