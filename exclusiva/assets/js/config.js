/**
 * Configurações da aplicação
 * Centraliza todas as configurações e constantes
 */

// Configuração de ambiente
const ENV_CONFIG = {
  // Chaves da API (serão carregadas do .env)
  MAPTILER_KEY: 'xv5eMXX1JbnIYEOwVQb7',
  OPENAI_API_KEY: 'sk-proj-G_cYcgzN_vzAvnpuN9HsOP2htsaAaLUD7XycfPIwFN2CwjCyPHSq1Z71feGENFTQNdymMeCzBeT3BlbkFJe6wJaj1C--1A5mmbo_',
  
  // URLs da API
  API_BASE_URL: '../api',
  API_ENDPOINTS: {
    IMOVEIS: '/imoveis.php',
    DETALHES: '/detalhes.php'
  },
  
  // Configurações do mapa
  MAP_CONFIG: {
    DEFAULT_CENTER: [-19.9, -43.9],
    DEFAULT_ZOOM: 11,
    MAX_ZOOM: 19,
    TILE_LAYER: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    ATTRIBUTION: '© OpenStreetMap contributors'
  },
  
  // Configurações de timeout
  TIMEOUTS: {
    API_REQUEST: 5000,
    IMAGE_LOAD: 3000
  },
  
  // Configurações de paginação
  PAGINATION: {
    ITEMS_PER_PAGE: 12,
    MAX_ITEMS: 1000
  },
  
  // Configurações de cache
  CACHE: {
    DURATION: 5 * 60 * 1000, // 5 minutos
    MAX_SIZE: 100
  }
};

// Constantes da aplicação
const APP_CONSTANTS = {
  // Tipos de imóveis
  TIPOS_IMOVEL: [
    'Apartamento',
    'Casa',
    'Área Privativa',
    'Cobertura',
    'Loft',
    'Studio',
    'Terreno',
    'Comercial'
  ],
  
  // Finalidades
  FINALIDADES: ['Venda', 'Locação', 'Temporada'],
  
  // Estados brasileiros
  ESTADOS: {
    'MG': 'Minas Gerais',
    'SP': 'São Paulo',
    'RJ': 'Rio de Janeiro',
    'ES': 'Espírito Santo'
  },
  
  // Mensagens de erro
  ERROR_MESSAGES: {
    NETWORK_ERROR: 'Erro de conexão. Verifique sua internet.',
    API_ERROR: 'Erro no servidor. Tente novamente mais tarde.',
    INVALID_DATA: 'Dados inválidos recebidos.',
    IMAGE_LOAD_ERROR: 'Erro ao carregar imagem.',
    GEOLOCATION_ERROR: 'Erro ao obter localização.',
    GENERIC_ERROR: 'Ocorreu um erro inesperado.'
  },
  
  // Mensagens de sucesso
  SUCCESS_MESSAGES: {
    DATA_LOADED: 'Dados carregados com sucesso!',
    FILTER_APPLIED: 'Filtros aplicados com sucesso!',
    LOCATION_FOUND: 'Localização encontrada!'
  },
  
  // Configurações de validação
  VALIDATION: {
    MIN_PRICE: 0,
    MAX_PRICE: 50000000,
    MIN_AREA: 1,
    MAX_AREA: 10000,
    MIN_ROOMS: 0,
    MAX_ROOMS: 20
  }
};

// Configurações de desenvolvimento/produção
const DEBUG_MODE = window.location.hostname === 'localhost' || 
                   window.location.hostname === '127.0.0.1' ||
                   window.location.search.includes('debug=true');

// Logger personalizado
const Logger = {
  info: (message, data = null) => {
    if (DEBUG_MODE) {
      console.log(`[INFO] ${message}`, data || '');
    }
  },
  
  warn: (message, data = null) => {
    if (DEBUG_MODE) {
      console.warn(`[WARN] ${message}`, data || '');
    }
  },
  
  error: (message, error = null) => {
    if (DEBUG_MODE) {
      console.error(`[ERROR] ${message}`, error || '');
    }
    // Em produção, enviar para serviço de monitoramento
  }
};

// Utilitários de configuração
const ConfigUtils = {
  // Verifica se está em modo de desenvolvimento
  isDevelopment: () => DEBUG_MODE,
  
  // Obtém configuração do mapa baseada na chave da API
  getMapConfig: () => {
    const config = { ...ENV_CONFIG.MAP_CONFIG };
    
    // Se tiver chave do MapTiler, usar tiles premium
    if (ENV_CONFIG.MAPTILER_KEY) {
      config.TILE_LAYER = `https://api.maptiler.com/maps/streets/{z}/{x}/{y}.png?key=${ENV_CONFIG.MAPTILER_KEY}`;
      config.ATTRIBUTION = '© MapTiler © OpenStreetMap contributors';
    }
    
    return config;
  },
  
  // Obtém URL completa da API
  getApiUrl: (endpoint) => {
    return `${ENV_CONFIG.API_BASE_URL}${ENV_CONFIG.API_ENDPOINTS[endpoint] || endpoint}`;
  },
  
  // Valida configurações essenciais
  validateConfig: () => {
    const errors = [];
    
    if (!ENV_CONFIG.MAP_CONFIG.DEFAULT_CENTER) {
      errors.push('Centro padrão do mapa não configurado');
    }
    
    if (!ENV_CONFIG.API_BASE_URL) {
      errors.push('URL base da API não configurada');
    }
    
    if (errors.length > 0) {
      Logger.error('Erros de configuração encontrados:', errors);
      return false;
    }
    
    return true;
  }
};

// Exportar configurações globalmente
window.ENV_CONFIG = ENV_CONFIG;
window.APP_CONSTANTS = APP_CONSTANTS;
window.Logger = Logger;
window.ConfigUtils = ConfigUtils;

// Validar configurações na inicialização
document.addEventListener('DOMContentLoaded', () => {
  if (!ConfigUtils.validateConfig()) {
    Logger.error('Aplicação pode não funcionar corretamente devido a erros de configuração');
  }
});