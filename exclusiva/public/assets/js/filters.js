/**
 * Gerenciador de Filtros
 * Centraliza toda a lógica de filtragem de imóveis
 */

class FilterManager {
  constructor() {
    this.filtros = {
      bairro: '',
      cidade: '',
      precoMin: '',
      precoMax: '',
      tipo: '',
      finalidade: '',
      dormitorios: '',
      banheiros: '',
      garagem: ''
    };
    
    this.filtrosAvancados = {
      areaMin: '',
      areaMax: '',
      suites: '',
      salas: '',
      anoMin: '',
      anoMax: ''
    };
    
    this.ordenacao = {
      campo: 'valor',
      direcao: 'asc' // asc ou desc
    };
    
    this.historico = [];
    this.maxHistorico = 10;
  }

  /**
   * Aplica filtros aos imóveis
   * @param {Array} imoveis - Lista de imóveis
   * @returns {Array} Imóveis filtrados
   */
  aplicarFiltros(imoveis) {
    if (!Array.isArray(imoveis)) return [];

    Logger.info('Aplicando filtros:', this.filtros);
    
    let imoveisFiltrados = [...imoveis];

    // Filtro por bairro
    if (this.filtros.bairro) {
      const bairroNormalizado = AppUtils.normalizarString(this.filtros.bairro);
      imoveisFiltrados = imoveisFiltrados.filter(imovel => {
        const bairroImovel = AppUtils.normalizarString(imovel.bairro || '');
        return bairroImovel.includes(bairroNormalizado);
      });
    }

    // Filtro por cidade
    if (this.filtros.cidade) {
      const cidadeNormalizada = AppUtils.normalizarString(this.filtros.cidade);
      imoveisFiltrados = imoveisFiltrados.filter(imovel => {
        const cidadeImovel = AppUtils.normalizarString(imovel.cidade || '');
        return cidadeImovel.includes(cidadeNormalizada);
      });
    }

    // Filtro por preço
    if (this.filtros.precoMin || this.filtros.precoMax) {
      const precoMin = parseFloat(this.filtros.precoMin) || 0;
      const precoMax = parseFloat(this.filtros.precoMax) || Infinity;
      
      imoveisFiltrados = imoveisFiltrados.filter(imovel => {
        const valor = parseFloat(imovel.valor) || 0;
        return valor >= precoMin && valor <= precoMax;
      });
    }

    // Filtro por tipo
    if (this.filtros.tipo) {
      imoveisFiltrados = imoveisFiltrados.filter(imovel => 
        imovel.tipo && imovel.tipo.toLowerCase() === this.filtros.tipo.toLowerCase()
      );
    }

    // Filtro por finalidade
    if (this.filtros.finalidade) {
      imoveisFiltrados = imoveisFiltrados.filter(imovel => 
        imovel.finalidade && imovel.finalidade.toLowerCase() === this.filtros.finalidade.toLowerCase()
      );
    }

    // Filtro por dormitórios
    if (this.filtros.dormitorios) {
      const dormitorios = parseInt(this.filtros.dormitorios);
      imoveisFiltrados = imoveisFiltrados.filter(imovel => 
        parseInt(imovel.dormitorios) >= dormitorios
      );
    }

    // Filtro por banheiros
    if (this.filtros.banheiros) {
      const banheiros = parseInt(this.filtros.banheiros);
      imoveisFiltrados = imoveisFiltrados.filter(imovel => 
        parseInt(imovel.banheiros) >= banheiros
      );
    }

    // Filtro por garagem
    if (this.filtros.garagem) {
      const garagem = parseInt(this.filtros.garagem);
      imoveisFiltrados = imoveisFiltrados.filter(imovel => 
        parseInt(imovel.garagem) >= garagem
      );
    }

    // Aplicar filtros avançados
    imoveisFiltrados = this.aplicarFiltrosAvancados(imoveisFiltrados);

    // Aplicar ordenação
    imoveisFiltrados = this.aplicarOrdenacao(imoveisFiltrados);

    // Salvar no histórico
    this.salvarNoHistorico();

    Logger.info(`${imoveisFiltrados.length} imóveis após filtragem`);
    return imoveisFiltrados;
  }

  /**
   * Aplica filtros avançados
   * @param {Array} imoveis - Lista de imóveis
   * @returns {Array} Imóveis filtrados
   */
  aplicarFiltrosAvancados(imoveis) {
    let imoveisFiltrados = [...imoveis];

    // Filtro por área
    if (this.filtrosAvancados.areaMin || this.filtrosAvancados.areaMax) {
      const areaMin = parseFloat(this.filtrosAvancados.areaMin) || 0;
      const areaMax = parseFloat(this.filtrosAvancados.areaMax) || Infinity;
      
      imoveisFiltrados = imoveisFiltrados.filter(imovel => {
        const area = parseFloat(imovel.area_total) || 0;
        return area >= areaMin && area <= areaMax;
      });
    }

    // Filtro por suítes
    if (this.filtrosAvancados.suites) {
      const suites = parseInt(this.filtrosAvancados.suites);
      imoveisFiltrados = imoveisFiltrados.filter(imovel => 
        parseInt(imovel.suites) >= suites
      );
    }

    // Filtro por salas
    if (this.filtrosAvancados.salas) {
      const salas = parseInt(this.filtrosAvancados.salas);
      imoveisFiltrados = imoveisFiltrados.filter(imovel => 
        parseInt(imovel.salas) >= salas
      );
    }

    // Filtro por ano de construção
    if (this.filtrosAvancados.anoMin || this.filtrosAvancados.anoMax) {
      const anoMin = parseInt(this.filtrosAvancados.anoMin) || 1900;
      const anoMax = parseInt(this.filtrosAvancados.anoMax) || new Date().getFullYear();
      
      imoveisFiltrados = imoveisFiltrados.filter(imovel => {
        const ano = parseInt(imovel.ano_construcao) || 0;
        return ano === 0 || (ano >= anoMin && ano <= anoMax);
      });
    }

    return imoveisFiltrados;
  }

  /**
   * Aplica ordenação aos imóveis
   * @param {Array} imoveis - Lista de imóveis
   * @returns {Array} Imóveis ordenados
   */
  aplicarOrdenacao(imoveis) {
    if (!Array.isArray(imoveis) || imoveis.length === 0) return imoveis;

    const { campo, direcao } = this.ordenacao;

    return imoveis.sort((a, b) => {
      let valorA, valorB;

      switch (campo) {
        case 'valor':
          valorA = parseFloat(a.valor) || 0;
          valorB = parseFloat(b.valor) || 0;
          break;
        
        case 'area':
          valorA = parseFloat(a.area_total) || 0;
          valorB = parseFloat(b.area_total) || 0;
          break;
        
        case 'dormitorios':
          valorA = parseInt(a.dormitorios) || 0;
          valorB = parseInt(b.dormitorios) || 0;
          break;
        
        case 'cidade':
          valorA = (a.cidade || '').toLowerCase();
          valorB = (b.cidade || '').toLowerCase();
          break;
        
        case 'bairro':
          valorA = (a.bairro || '').toLowerCase();
          valorB = (b.bairro || '').toLowerCase();
          break;
        
        case 'tipo':
          valorA = (a.tipo || '').toLowerCase();
          valorB = (b.tipo || '').toLowerCase();
          break;
        
        default:
          return 0;
      }

      if (valorA < valorB) return direcao === 'asc' ? -1 : 1;
      if (valorA > valorB) return direcao === 'asc' ? 1 : -1;
      return 0;
    });
  }

  /**
   * Define filtro específico
   * @param {string} campo - Nome do campo
   * @param {any} valor - Valor do filtro
   */
  setFiltro(campo, valor) {
    if (this.filtros.hasOwnProperty(campo)) {
      this.filtros[campo] = valor;
    } else if (this.filtrosAvancados.hasOwnProperty(campo)) {
      this.filtrosAvancados[campo] = valor;
    }
    
    Logger.info(`Filtro ${campo} definido como:`, valor);
  }

  /**
   * Define múltiplos filtros
   * @param {Object} filtros - Objeto com filtros
   */
  setFiltros(filtros) {
    Object.entries(filtros).forEach(([campo, valor]) => {
      this.setFiltro(campo, valor);
    });
  }

  /**
   * Obtém filtro específico
   * @param {string} campo - Nome do campo
   * @returns {any} Valor do filtro
   */
  getFiltro(campo) {
    return this.filtros[campo] || this.filtrosAvancados[campo] || '';
  }

  /**
   * Obtém todos os filtros
   * @returns {Object} Todos os filtros
   */
  getFiltros() {
    return {
      ...this.filtros,
      ...this.filtrosAvancados
    };
  }

  /**
   * Limpa todos os filtros
   */
  limparFiltros() {
    Object.keys(this.filtros).forEach(key => {
      this.filtros[key] = '';
    });
    
    Object.keys(this.filtrosAvancados).forEach(key => {
      this.filtrosAvancados[key] = '';
    });
    
    Logger.info('Filtros limpos');
  }

  /**
   * Define ordenação
   * @param {string} campo - Campo para ordenar
   * @param {string} direcao - Direção da ordenação (asc/desc)
   */
  setOrdenacao(campo, direcao = 'asc') {
    this.ordenacao = { campo, direcao };
    Logger.info('Ordenação definida:', this.ordenacao);
  }

  /**
   * Alterna direção da ordenação
   * @param {string} campo - Campo para ordenar
   */
  toggleOrdenacao(campo) {
    if (this.ordenacao.campo === campo) {
      this.ordenacao.direcao = this.ordenacao.direcao === 'asc' ? 'desc' : 'asc';
    } else {
      this.ordenacao = { campo, direcao: 'asc' };
    }
    
    Logger.info('Ordenação alternada:', this.ordenacao);
  }

  /**
   * Obtém sugestões baseadas nos dados
   * @param {Array} imoveis - Lista de imóveis
   * @param {string} campo - Campo para sugestões
   * @param {string} termo - Termo de busca
   * @returns {Array} Lista de sugestões
   */
  obterSugestoes(imoveis, campo, termo) {
    if (!Array.isArray(imoveis) || !termo || termo.length < 2) return [];

    const termoNormalizado = AppUtils.normalizarString(termo);
    const sugestoes = new Set();

    imoveis.forEach(imovel => {
      const valor = imovel[campo];
      if (valor) {
        const valorNormalizado = AppUtils.normalizarString(valor);
        if (valorNormalizado.includes(termoNormalizado)) {
          sugestoes.add(valor);
        }
      }
    });

    return Array.from(sugestoes).slice(0, 10);
  }

  /**
   * Obtém estatísticas dos filtros aplicados
   * @param {Array} imoveisOriginais - Lista original
   * @param {Array} imoveisFiltrados - Lista filtrada
   * @returns {Object} Estatísticas
   */
  obterEstatisticas(imoveisOriginais, imoveisFiltrados) {
    const total = imoveisOriginais.length;
    const filtrados = imoveisFiltrados.length;
    const percentual = total > 0 ? Math.round((filtrados / total) * 100) : 0;

    // Estatísticas por tipo
    const tiposOriginais = {};
    const tiposFiltrados = {};

    imoveisOriginais.forEach(imovel => {
      const tipo = imovel.tipo || 'Outros';
      tiposOriginais[tipo] = (tiposOriginais[tipo] || 0) + 1;
    });

    imoveisFiltrados.forEach(imovel => {
      const tipo = imovel.tipo || 'Outros';
      tiposFiltrados[tipo] = (tiposFiltrados[tipo] || 0) + 1;
    });

    // Faixa de preços
    const precos = imoveisFiltrados.map(i => parseFloat(i.valor) || 0).filter(p => p > 0);
    const precoMin = precos.length > 0 ? Math.min(...precos) : 0;
    const precoMax = precos.length > 0 ? Math.max(...precos) : 0;
    const precoMedio = precos.length > 0 ? precos.reduce((a, b) => a + b, 0) / precos.length : 0;

    return {
      total,
      filtrados,
      percentual,
      tiposOriginais,
      tiposFiltrados,
      precos: {
        min: precoMin,
        max: precoMax,
        medio: precoMedio
      }
    };
  }

  /**
   * Salva estado atual no histórico
   */
  salvarNoHistorico() {
    const estado = {
      filtros: { ...this.filtros },
      filtrosAvancados: { ...this.filtrosAvancados },
      ordenacao: { ...this.ordenacao },
      timestamp: Date.now()
    };

    this.historico.unshift(estado);
    
    // Limita tamanho do histórico
    if (this.historico.length > this.maxHistorico) {
      this.historico = this.historico.slice(0, this.maxHistorico);
    }
  }

  /**
   * Restaura estado do histórico
   * @param {number} index - Índice no histórico
   */
  restaurarDoHistorico(index) {
    if (index >= 0 && index < this.historico.length) {
      const estado = this.historico[index];
      this.filtros = { ...estado.filtros };
      this.filtrosAvancados = { ...estado.filtrosAvancados };
      this.ordenacao = { ...estado.ordenacao };
      
      Logger.info('Estado restaurado do histórico:', estado);
    }
  }

  /**
   * Obtém histórico de filtros
   * @returns {Array} Histórico
   */
  obterHistorico() {
    return this.historico.map((estado, index) => ({
      index,
      timestamp: estado.timestamp,
      filtrosAtivos: Object.values(estado.filtros).filter(v => v).length +
                     Object.values(estado.filtrosAvancados).filter(v => v).length
    }));
  }

  /**
   * Exporta configuração atual
   * @returns {Object} Configuração
   */
  exportarConfiguracao() {
    return {
      filtros: { ...this.filtros },
      filtrosAvancados: { ...this.filtrosAvancados },
      ordenacao: { ...this.ordenacao }
    };
  }

  /**
   * Importa configuração
   * @param {Object} config - Configuração a importar
   */
  importarConfiguracao(config) {
    if (config.filtros) {
      this.filtros = { ...this.filtros, ...config.filtros };
    }
    
    if (config.filtrosAvancados) {
      this.filtrosAvancados = { ...this.filtrosAvancados, ...config.filtrosAvancados };
    }
    
    if (config.ordenacao) {
      this.ordenacao = { ...config.ordenacao };
    }
    
    Logger.info('Configuração importada:', config);
  }
}

// Instância global do gerenciador de filtros
const filterManager = new FilterManager();

// Exportar globalmente
window.FilterManager = FilterManager;
window.filterManager = filterManager;