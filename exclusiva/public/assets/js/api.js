/**
 * Gerenciador de API
 * Centraliza todas as chamadas de API e cache
 */

class ApiManager {
  constructor() {
    this.cache = new Map();
    this.baseUrl = ENV_CONFIG.API_BASE_URL;
    this.timeout = ENV_CONFIG.TIMEOUTS.API_REQUEST;
    this.cacheDuration = ENV_CONFIG.CACHE.DURATION;
  }

  /**
   * Realiza requisição HTTP com timeout e retry
   * @param {string} url - URL da requisição
   * @param {Object} options - Opções da requisição
   * @returns {Promise<Response>} Response da requisição
   */
  async fetchWithTimeout(url, options = {}) {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), this.timeout);
    
    try {
      const response = await fetch(url, {
        ...options,
        signal: controller.signal,
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          ...options.headers
        }
      });
      
      clearTimeout(timeoutId);
      return response;
    } catch (error) {
      clearTimeout(timeoutId);
      throw error;
    }
  }

  /**
   * Verifica se dados estão em cache e são válidos
   * @param {string} key - Chave do cache
   * @returns {any|null} Dados do cache ou null
   */
  getFromCache(key) {
    const cached = this.cache.get(key);
    if (!cached) return null;
    
    const now = Date.now();
    if (now - cached.timestamp > this.cacheDuration) {
      this.cache.delete(key);
      return null;
    }
    
    Logger.info(`Cache hit para: ${key}`);
    return cached.data;
  }

  /**
   * Armazena dados no cache
   * @param {string} key - Chave do cache
   * @param {any} data - Dados a serem armazenados
   */
  setCache(key, data) {
    // Limita o tamanho do cache
    if (this.cache.size >= ENV_CONFIG.CACHE.MAX_SIZE) {
      const firstKey = this.cache.keys().next().value;
      this.cache.delete(firstKey);
    }
    
    this.cache.set(key, {
      data,
      timestamp: Date.now()
    });
    
    Logger.info(`Dados armazenados no cache: ${key}`);
  }

  /**
   * Carrega lista de imóveis
   * @param {Object} filtros - Filtros de busca
   * @returns {Promise<Object>} Resposta da API
   */
  async carregarImoveis(filtros = {}) {
    const cacheKey = `imoveis_${JSON.stringify(filtros)}`;
    
    // Verifica cache primeiro
    const cached = this.getFromCache(cacheKey);
    if (cached) return cached;
    
    try {
      Logger.info('Carregando imóveis da API...');
      
      const url = new URL(ConfigUtils.getApiUrl('IMOVEIS'), window.location.origin);
      
      // Adiciona filtros como query parameters
      Object.entries(filtros).forEach(([key, value]) => {
        if (value) url.searchParams.append(key, value);
      });
      
      const response = await this.fetchWithTimeout(url.toString());
      
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
      
      const data = await response.json();
      
      // Valida estrutura da resposta
      if (!this.validarRespostaImoveis(data)) {
        throw new Error('Estrutura de dados inválida recebida da API');
      }
      
      // Processa e valida cada imóvel
      const imoveisValidos = data.data.filter(imovel => {
        const valido = AppUtils.validarImovel(imovel);
        if (!valido) {
          Logger.warn('Imóvel inválido ignorado:', imovel);
        }
        return valido;
      });
      
      const resultado = {
        ...data,
        data: imoveisValidos,
        total: imoveisValidos.length
      };
      
      // Armazena no cache
      this.setCache(cacheKey, resultado);
      
      Logger.info(`${imoveisValidos.length} imóveis carregados com sucesso`);
      return resultado;
      
    } catch (error) {
      Logger.error('Erro ao carregar imóveis da API:', error);
      
      // Fallback para dados locais
      Logger.info('Usando dados de fallback...');
      return this.getFallbackData();
    }
  }

  /**
   * Carrega detalhes de um imóvel específico
   * @param {number} codigo - Código do imóvel
   * @returns {Promise<Object>} Detalhes do imóvel
   */
  async carregarDetalhesImovel(codigo) {
    const cacheKey = `imovel_${codigo}`;
    
    // Verifica cache primeiro
    const cached = this.getFromCache(cacheKey);
    if (cached) return cached;
    
    try {
      Logger.info(`Carregando detalhes do imóvel ${codigo}...`);
      
      const url = `${ConfigUtils.getApiUrl('DETALHES')}?codigo=${codigo}`;
      const response = await this.fetchWithTimeout(url);
      
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
      
      const data = await response.json();
      
      if (!data.success || !data.data) {
        throw new Error('Detalhes do imóvel não encontrados');
      }
      
      // Armazena no cache
      this.setCache(cacheKey, data.data);
      
      Logger.info(`Detalhes do imóvel ${codigo} carregados com sucesso`);
      return data.data;
      
    } catch (error) {
      Logger.error(`Erro ao carregar detalhes do imóvel ${codigo}:`, error);
      throw error;
    }
  }

  /**
   * Valida estrutura da resposta de imóveis
   * @param {Object} data - Dados recebidos
   * @returns {boolean} True se válido
   */
  validarRespostaImoveis(data) {
    return data && 
           typeof data === 'object' && 
           Array.isArray(data.data) &&
           typeof data.status === 'boolean';
  }

  /**
   * Retorna dados de fallback quando API não está disponível
   * @returns {Object} Dados de fallback
   */
  getFallbackData() {
    const dadosFallback = [
      {
        "codigo": 3900163,
        "referencia": "013AP",
        "finalidade": "Locação",
        "tipo": "Apartamento",
        "dormitorios": 2,
        "suites": 0,
        "banheiros": 1,
        "salas": 1,
        "garagem": 1,
        "valor": "1800.00",
        "cidade": "Belo Horizonte",
        "estado": "MG",
        "bairro": "Itapoã",
        "logradouro": "Avenida Portugal",
        "numero": "5425",
        "area_total": null,
        "descricao": "APARTAMENTO PARA LOCAÇÃO – BAIRRO ITAPOÃ. Valor R$ 1.800,00. Imóvel com excelente localização e fácil acesso às principais vias da região! Características: 02 quartos com armários planejados, 01 sala aconchegante com rebaixamento em gesso, 01 cozinha com revestimentos de qualidade e armários, 01 banheiro amplo com armário e box de vidro.",
        "lat": "-19.84200940",
        "lng": "-43.96582740",
        "thumb_url": "https://imgs1.cdn-imobibrasil.com.br/imagens/imoveis/202508221604142998.jpeg",
        "imagens": [
          "https://imgs1.cdn-imobibrasil.com.br/imagens/imoveis/202508221604142998.jpeg",
          "https://imgs1.cdn-imobibrasil.com.br/imagens/imoveis/202508221604207451.jpeg",
          "https://imgs1.cdn-imobibrasil.com.br/imagens/imoveis/202508221604202039.jpeg"
        ]
      },
      {
        "codigo": 3897176,
        "referencia": "066CA",
        "finalidade": "Venda",
        "tipo": "Casa",
        "dormitorios": 3,
        "suites": 3,
        "banheiros": 4,
        "salas": 0,
        "garagem": 2,
        "valor": "1980000.00",
        "cidade": "Nova Lima",
        "estado": "MG",
        "bairro": "Jardim Canadá",
        "logradouro": "Holanda",
        "numero": "36",
        "area_total": "370.00",
        "terreno": "531.00",
        "descricao": "Casa a venda no condomínio Ville Des Lacs R$ 1.980.000,00. Casa acabamento luxo no condomínio Ville des Lacs. Lote de 531m² com 370m² de área construída. 3 quartos sendo 1 suíte com amplo closet e 2 semi-suites.",
        "lat": "-20.06036970",
        "lng": "-43.97891510",
        "thumb_url": "https://imgs1.cdn-imobibrasil.com.br/imagens/imoveis/202508210947339801.jpeg",
        "imagens": [
          "https://imgs1.cdn-imobibrasil.com.br/imagens/imoveis/202508210947339801.jpeg",
          "https://imgs1.cdn-imobibrasil.com.br/imagens/imoveis/20250821094739967.jpeg",
          "https://imgs1.cdn-imobibrasil.com.br/imagens/imoveis/202508210947397768.jpeg"
        ]
      },
      {
        "codigo": 3891313,
        "referencia": "065AP",
        "finalidade": "Venda",
        "tipo": "Área Privativa",
        "dormitorios": 3,
        "suites": 0,
        "banheiros": 1,
        "salas": 1,
        "garagem": 1,
        "valor": "445000.00",
        "cidade": "Belo Horizonte",
        "estado": "MG",
        "bairro": "Santa Amélia",
        "logradouro": "Rua Professor Clóvis de Faria",
        "numero": "131",
        "area_total": "113.00",
        "descricao": "Oportunidade Imperdível no Bairro Santa Amélia. Imóvel com 3 quartos, localizado no 1º pavimento, perfeito para quem busca conforto e praticidade: 113m² de área total, 90m² de área construída.",
        "lat": "-19.84414770",
        "lng": "-43.97394230",
        "thumb_url": "https://imgs1.cdn-imobibrasil.com.br/imagens/imoveis/202508181006316576.jpeg",
        "imagens": [
          "https://imgs1.cdn-imobibrasil.com.br/imagens/imoveis/202508181006316576.jpeg",
          "https://imgs1.cdn-imobibrasil.com.br/imagens/imoveis/202508181006339633.jpeg"
        ]
      }
    ];

    return {
      data: dadosFallback,
      status: true,
      total: dadosFallback.length,
      source: 'fallback'
    };
  }

  /**
   * Busca sugestões de endereço (geocoding)
   * @param {string} query - Termo de busca
   * @returns {Promise<Array>} Lista de sugestões
   */
  async buscarEnderecos(query) {
    if (!query || query.length < 3) return [];
    
    const cacheKey = `geocoding_${query}`;
    const cached = this.getFromCache(cacheKey);
    if (cached) return cached;
    
    try {
      // Usar serviço de geocoding gratuito
      const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=br&limit=5`;
      
      const response = await this.fetchWithTimeout(url);
      if (!response.ok) throw new Error('Erro na busca de endereços');
      
      const data = await response.json();
      const sugestoes = data.map(item => ({
        display_name: item.display_name,
        lat: parseFloat(item.lat),
        lon: parseFloat(item.lon)
      }));
      
      this.setCache(cacheKey, sugestoes);
      return sugestoes;
      
    } catch (error) {
      Logger.error('Erro ao buscar endereços:', error);
      return [];
    }
  }

  /**
   * Limpa cache
   * @param {string} pattern - Padrão para limpar (opcional)
   */
  limparCache(pattern = null) {
    if (pattern) {
      const keys = Array.from(this.cache.keys());
      keys.forEach(key => {
        if (key.includes(pattern)) {
          this.cache.delete(key);
        }
      });
    } else {
      this.cache.clear();
    }
    
    Logger.info('Cache limpo');
  }

  /**
   * Obtém estatísticas do cache
   * @returns {Object} Estatísticas
   */
  getCacheStats() {
    return {
      size: this.cache.size,
      maxSize: ENV_CONFIG.CACHE.MAX_SIZE,
      keys: Array.from(this.cache.keys())
    };
  }
}

// Instância global do gerenciador de API
const apiManager = new ApiManager();

// Exportar globalmente
window.ApiManager = ApiManager;
window.apiManager = apiManager;