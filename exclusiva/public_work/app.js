const { createApp, ref, computed, onMounted, watch, nextTick } = Vue;

// Dados completos dos imóveis
const IMOVEIS_DATA = [
  {
    codigo: 3900163,
    referencia: "013AP",
    finalidade: "Locação",
    tipo: "Apartamento",
    dormitorios: 2,
    suites: 0,
    banheiros: 1,
    salas: 1,
    garagem: 1,
    acomodacoes: 3,
    ano_construcao: 1999,
    valor: "1800.00",
    cidade: "Belo Horizonte",
    estado: "MG",
    bairro: "Itapoã",
    logradouro: "Avenida Portugal",
    numero: "5425",
    cep: "31710-400",
    area_privativa: null,
    area_total: null,
    terreno: null,
    descricao: "APARTAMENTO PARA LOCAÇÃO – BAIRRO ITAPOÃ. Valor R$ 1.800,00. Imóvel com excelente localização e fácil acesso às principais vias da região! Características do apartamento: 02 quartos com armários planejados, 01 sala aconchegante com rebaixamento em gesso, 01 cozinha com revestimentos de qualidade e armários, 01 banheiro amplo com armário e box de vidro. Localização privilegiada: Bairro Itapoã, próximo a comércios, escolas, supermercados e farmácias, fácil acesso às Avenidas Cristiano Machado e Antônio Carlos.",
    status_ativo: 1,
    atualizado_em: "2025-08-22 16:15:04",
    cadastrado_em: "2025-08-08",
    lat: "-19.84200940",
    lng: "-43.96582740",
    imagens: [
      "https://imgs1.cdn-imobibrasil.com.br/imagens/imoveis/202508221604142998.jpeg",
      "https://imgs1.cdn-imobibrasil.com.br/imagens/imoveis/202508221604207451.jpeg",
      "https://imgs1.cdn-imobibrasil.com.br/imagens/imoveis/202508221604202039.jpeg",
      "https://imgs1.cdn-imobibrasil.com.br/imagens/imoveis/202508221604198763.jpeg"
    ],
    thumb_url: "https://imgs1.cdn-imobibrasil.com.br/imagens/imoveis/202508221604142998.jpeg"
  },
  {
    codigo: 3897176,
    referencia: "066CA",
    finalidade: "Venda",
    tipo: "Casa",
    dormitorios: 3,
    suites: 3,
    banheiros: 4,
    salas: 0,
    garagem: 2,
    acomodacoes: 0,
    ano_construcao: null,
    valor: "1980000.00",
    cidade: "Nova Lima",
    estado: "MG",
    bairro: "Jardim Canadá",
    logradouro: "Holanda",
    numero: "36",
    cep: "34007-768",
    area_privativa: null,
    area_total: "370.00",
    terreno: "531.00",
    descricao: "Casa a venda no condomínio Ville Des Lacs R$ 1.980.000,00. Casa acabamento luxo no condomínio Ville des Lacs. Lote de 531m² com 370m² de área construída. 3 quartos sendo 1 suíte com amplo closet e 2 semi-suites. Suíte com acabamento em Limestone Saint-Remy até o teto, 2 cubas e 2 chuveiros e aquecimento solar. Semi-suite com acabamento em mármore branco e pastilhas de vidro. Cozinha gourmet com bancadas em granito preto absoluto texturizado, integrada a churrasqueira e SPA revestido em pedra Hijau Palimanan e Deck de madeira.",
    status_ativo: 1,
    atualizado_em: "2025-08-21 09:55:10",
    cadastrado_em: "2025-08-21",
    lat: "-20.06036970",
    lng: "-43.97891510",
    imagens: [
      "https://imgs1.cdn-imobibrasil.com.br/imagens/imoveis/202508210947339801.jpeg",
      "https://imgs1.cdn-imobibrasil.com.br/imagens/imoveis/20250821094739967.jpeg",
      "https://imgs1.cdn-imobibrasil.com.br/imagens/imoveis/202508210947397768.jpeg",
      "https://imgs1.cdn-imobibrasil.com.br/imagens/imoveis/20250821094738476.jpeg"
    ],
    thumb_url: "https://imgs1.cdn-imobibrasil.com.br/imagens/imoveis/202508210947339801.jpeg"
  },
  {
    codigo: 3891313,
    referencia: "065AP",
    finalidade: "Venda",
    tipo: "Área Privativa",
    dormitorios: 3,
    suites: 0,
    banheiros: 1,
    salas: 1,
    garagem: 1,
    acomodacoes: 5,
    ano_construcao: null,
    valor: "445000.00",
    cidade: "Belo Horizonte",
    estado: "MG",
    bairro: "Santa Amélia",
    logradouro: "Rua Professor Clóvis de Faria",
    numero: "131",
    cep: "31560-120",
    area_privativa: null,
    area_total: "113.00",
    terreno: null,
    descricao: "Oportunidade Imperdível no Bairro Santa Amélia. Imóvel com 3 quartos, localizado no 1º pavimento, perfeito para quem busca conforto e praticidade: 113m² de área total, 90m² de área construída. Localização estratégica, próximo às principais avenidas, área comercial completa e com fácil acesso a toda a cidade! Ideal para morar com qualidade de vida ou investir em uma região de grande valorização!",
    status_ativo: 1,
    atualizado_em: "2025-08-19 10:21:15",
    cadastrado_em: "2025-08-18",
    lat: "-19.84414770",
    lng: "-43.97394230",
    imagens: [
      "https://imgs1.cdn-imobibrasil.com.br/imagens/imoveis/202508181006316576.jpeg",
      "https://imgs1.cdn-imobibrasil.com.br/imagens/imoveis/202508181006339633.jpeg",
      "https://imgs1.cdn-imobibrasil.com.br/imagens/imoveis/20250818100633805.jpeg"
    ],
    thumb_url: "https://imgs1.cdn-imobibrasil.com.br/imagens/imoveis/202508181006316576.jpeg"
  },
  {
    codigo: 3887169,
    referencia: "064TE",
    finalidade: "Venda",
    tipo: "Área Privativa",
    dormitorios: 3,
    suites: 1,
    banheiros: 3,
    salas: 1,
    garagem: 2,
    acomodacoes: 0,
    ano_construcao: 2025,
    valor: "600000.00",
    cidade: "Belo Horizonte",
    estado: "MG",
    bairro: "Jardim Atlântico",
    logradouro: "Rua Augusto Clementino",
    numero: "1219",
    cep: "31550-300",
    area_privativa: null,
    area_total: null,
    terreno: null,
    descricao: "Sala, cozinha, 3 quartos, closet, 2 banheiros, área privativa, frente, lado e fundos, banheiro externo, churrasqueira e fogão. Sendo 1 banheiro com banheira. Aceita troca por outro de menor valor, 01 vagas de garagem.",
    status_ativo: 1,
    atualizado_em: "2025-08-15 13:36:33",
    cadastrado_em: "2025-08-14",
    lat: "-19.84374170",
    lng: "-43.98441290",
    imagens: [
      "https://imgs1.cdn-imobibrasil.com.br/imagens/imoveis/20250814094947380.jpeg",
      "https://imgs1.cdn-imobibrasil.com.br/imagens/imoveis/202508140949502439.jpeg"
    ],
    thumb_url: "https://imgs1.cdn-imobibrasil.com.br/imagens/imoveis/20250814094947380.jpeg"
  },
  {
    codigo: 3879487,
    referencia: "061CA",
    finalidade: "Venda",
    tipo: "Casa",
    dormitorios: 2,
    suites: 0,
    banheiros: 1,
    salas: 1,
    garagem: 1,
    acomodacoes: 3,
    ano_construcao: 1997,
    valor: "358000.00",
    cidade: "Ribeirão das Neves",
    estado: "MG",
    bairro: "Sevilha (1ª Seção)",
    logradouro: "Rua Almenara",
    numero: "133",
    cep: "33855-190",
    area_privativa: null,
    area_total: "50.63",
    terreno: "360.00",
    descricao: "Casa que combina aconchego e amplitude: este refúgio de 50,63 m² construídos repousa em um terreno generoso de 360 m², com frente e fundos de 12 metros e laterais de 30 metros, oferecendo um extenso espaço externo para momentos de lazer. Situada no Sevilha, esta residência destaca-se pelo bom aproveitamento de área e pela versatilidade de ambientes.",
    status_ativo: 1,
    atualizado_em: "2025-08-13 08:53:36",
    cadastrado_em: "2025-08-08",
    lat: "-19.75673790",
    lng: "-44.07279650",
    imagens: [
      "https://imgs1.cdn-imobibrasil.com.br/imagens/imoveis/202508081357021825.jpeg",
      "https://imgs1.cdn-imobibrasil.com.br/imagens/imoveis/202508081357116301.jpeg"
    ],
    thumb_url: "https://imgs1.cdn-imobibrasil.com.br/imagens/imoveis/202508081357021825.jpeg"
  }
];

// Utilitários
const debounce = (func, wait) => {
  let timeout;
  return (...args) => {
    clearTimeout(timeout);
    timeout = setTimeout(() => func(...args), wait);
  };
};

const formatarMoeda = (valor) => {
  return new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(parseFloat(valor));
};

const limparHTML = (texto) => {
  if (!texto) return '';
  return texto
    .replace(/&#\d+;/g, '')
    .replace(/&[a-zA-Z]+;/g, '')
    .replace(/\s+/g, ' ')
    .trim();
};

// Aplicação Vue
createApp({
  setup() {
    // Estado reativo
    const loading = ref(true);
    const modalAberto = ref(false);
    const visualizacao = ref('lista');
    const mostrarFiltrosAvancados = ref(false);
    const ordenacao = ref('relevancia');
    const paginaAtual = ref(1);
    const itensPorPagina = ref(9);
    
    // Dados
    const todosImoveis = ref([]);
    const imovelSelecionado = ref(null);
    const map = ref(null);
    const markersLayer = ref(null);
    
    // Filtros
    const filtros = ref({
      busca: '',
      tipo: '',
      finalidade: '',
      precoMin: '',
      precoMax: '',
      quartos: '',
      banheiros: '',
      vagas: '',
      cidade: ''
    });
    
    // Computadas
    const cidadesDisponiveis = computed(() => {
      const cidades = [...new Set(todosImoveis.value.map(i => i.cidade))];
      return cidades.sort();
    });
    
    const imoveisFiltrados = computed(() => {
      let resultado = [...todosImoveis.value];
      
      // Filtro por busca
      if (filtros.value.busca) {
        const termo = filtros.value.busca.toLowerCase();
        resultado = resultado.filter(imovel => 
          imovel.descricao?.toLowerCase().includes(termo) ||
          imovel.bairro?.toLowerCase().includes(termo) ||
          imovel.cidade?.toLowerCase().includes(termo) ||
          imovel.logradouro?.toLowerCase().includes(termo) ||
          imovel.tipo?.toLowerCase().includes(termo)
        );
      }
      
      // Filtros específicos
      if (filtros.value.tipo) {
        resultado = resultado.filter(i => i.tipo === filtros.value.tipo);
      }
      
      if (filtros.value.finalidade) {
        resultado = resultado.filter(i => i.finalidade === filtros.value.finalidade);
      }
      
      if (filtros.value.cidade) {
        resultado = resultado.filter(i => i.cidade === filtros.value.cidade);
      }
      
      // Filtros numéricos
      if (filtros.value.precoMin) {
        resultado = resultado.filter(i => parseFloat(i.valor) >= parseFloat(filtros.value.precoMin));
      }
      
      if (filtros.value.precoMax) {
        resultado = resultado.filter(i => parseFloat(i.valor) <= parseFloat(filtros.value.precoMax));
      }
      
      if (filtros.value.quartos) {
        resultado = resultado.filter(i => parseInt(i.dormitorios) >= parseInt(filtros.value.quartos));
      }
      
      if (filtros.value.banheiros) {
        resultado = resultado.filter(i => parseInt(i.banheiros) >= parseInt(filtros.value.banheiros));
      }
      
      if (filtros.value.vagas) {
        resultado = resultado.filter(i => parseInt(i.garagem) >= parseInt(filtros.value.vagas));
      }
      
      return resultado;
    });
    
    const imoveisOrdenados = computed(() => {
      const lista = [...imoveisFiltrados.value];
      
      switch (ordenacao.value) {
        case 'preco_menor':
          return lista.sort((a, b) => parseFloat(a.valor) - parseFloat(b.valor));
        case 'preco_maior':
          return lista.sort((a, b) => parseFloat(b.valor) - parseFloat(a.valor));
        case 'mais_recente':
          return lista.sort((a, b) => new Date(b.cadastrado_em) - new Date(a.cadastrado_em));
        default:
          return lista;
      }
    });
    
    const totalPaginas = computed(() => 
      Math.ceil(imoveisOrdenados.value.length / itensPorPagina.value)
    );
    
    const imoveisPaginados = computed(() => {
      const inicio = (paginaAtual.value - 1) * itensPorPagina.value;
      const fim = inicio + itensPorPagina.value;
      return imoveisOrdenados.value.slice(inicio, fim);
    });
    
    const paginasVisiveis = computed(() => {
      const total = totalPaginas.value;
      const atual = paginaAtual.value;
      const paginas = [];
      
      if (total <= 7) {
        for (let i = 1; i <= total; i++) {
          paginas.push(i);
        }
      } else {
        paginas.push(1);
        
        if (atual <= 4) {
          for (let i = 2; i <= 5; i++) {
            paginas.push(i);
          }
          paginas.push('...');
        } else if (atual >= total - 3) {
          paginas.push('...');
          for (let i = total - 4; i <= total - 1; i++) {
            paginas.push(i);
          }
        } else {
          paginas.push('...');
          for (let i = atual - 1; i <= atual + 1; i++) {
            paginas.push(i);
          }
          paginas.push('...');
        }
        
        paginas.push(total);
      }
      
      return paginas;
    });
    
    const contadorResultados = computed(() => {
      const total = imoveisFiltrados.value.length;
      if (total === 0) return 'Nenhum resultado';
      if (total === 1) return '1 imóvel encontrado';
      return `${total} imóveis encontrados`;
    });
    
    const especificacoes = computed(() => {
      if (!imovelSelecionado.value) return [];
      
      const specs = [];
      const imovel = imovelSelecionado.value;
      
      if (imovel.tipo) specs.push({ icon: 'fas fa-home', label: 'Tipo', value: imovel.tipo });
      if (imovel.finalidade) specs.push({ icon: 'fas fa-tag', label: 'Finalidade', value: imovel.finalidade });
      if (imovel.dormitorios) specs.push({ icon: 'fas fa-bed', label: 'Dormitórios', value: imovel.dormitorios });
      if (imovel.suites) specs.push({ icon: 'fas fa-bed-pulse', label: 'Suítes', value: imovel.suites });
      if (imovel.banheiros) specs.push({ icon: 'fas fa-bath', label: 'Banheiros', value: imovel.banheiros });
      if (imovel.salas) specs.push({ icon: 'fas fa-couch', label: 'Salas', value: imovel.salas });
      if (imovel.garagem) specs.push({ icon: 'fas fa-car', label: 'Vagas', value: imovel.garagem });
      if (imovel.area_total) specs.push({ icon: 'fas fa-ruler-combined', label: 'Área Total', value: `${imovel.area_total}m²` });
      if (imovel.area_privativa) specs.push({ icon: 'fas fa-home-user', label: 'Área Privativa', value: `${imovel.area_privativa}m²` });
      if (imovel.terreno) specs.push({ icon: 'fas fa-map', label: 'Terreno', value: `${imovel.terreno}m²` });
      if (imovel.ano_construcao) specs.push({ icon: 'fas fa-calendar', label: 'Ano', value: imovel.ano_construcao });
      
      return specs;
    });
    
    const enderecoCompleto = computed(() => {
      if (!imovelSelecionado.value) return '';
      const i = imovelSelecionado.value;
      return `${i.logradouro}, ${i.numero} - ${i.bairro}, ${i.cidade}/${i.estado}`;
    });
    
    const descricaoFormatada = computed(() => {
      if (!imovelSelecionado.value?.descricao) return '';
      return limparHTML(imovelSelecionado.value.descricao)
        .replace(/\n/g, '<br>')
        .replace(/\. /g, '.<br><br>');
    });
    
    // Métodos
    const inicializarMapa = async () => {
      await nextTick();
      
      if (map.value) {
        map.value.remove();
      }
      
      map.value = L.map('map').setView([-19.9, -43.9], 11);
      
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 18
      }).addTo(map.value);
      
      markersLayer.value = L.layerGroup().addTo(map.value);
      
      atualizarMapa();
    };
    
    const atualizarMapa = () => {
      if (!markersLayer.value) return;
      
      markersLayer.value.clearLayers();
      const bounds = [];
      
      imoveisFiltrados.value.forEach(imovel => {
        if (!imovel.lat || !imovel.lng) return;
        
        const lat = parseFloat(imovel.lat);
        const lng = parseFloat(imovel.lng);
        
        if (isNaN(lat) || isNaN(lng)) return;
        
        const marker = L.marker([lat, lng]).addTo(markersLayer.value);
        
        const popupContent = `
          <div style="min-width: 200px; text-align: center; font-family: system-ui;">
            <img src="${imovel.thumb_url}" 
                 style="width: 100%; height: 120px; object-fit: cover; border-radius: 6px; margin-bottom: 8px;">
            <h6 style="margin: 8px 0; color: #2563eb; font-weight: bold; font-size: 16px;">
              ${formatarMoeda(imovel.valor)}
            </h6>
            <p style="margin: 4px 0; font-weight: 600; color: #374151;">
              ${imovel.tipo}
            </p>
            <p style="margin: 4px 0; color: #6b7280; font-size: 14px;">
              ${imovel.bairro}, ${imovel.cidade}
            </p>
            <div style="margin: 6px 0; font-size: 12px; color: #9ca3af;">
              ${imovel.dormitorios ? `🛏️ ${imovel.dormitorios} quartos` : ''}
              ${imovel.banheiros ? ` • 🛁 ${imovel.banheiros} banheiros` : ''}
              ${imovel.garagem ? ` • 🚗 ${imovel.garagem} vagas` : ''}
            </div>
            <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #e5e7eb;">
              <small style="color: #9ca3af;">Ref: ${imovel.referencia}</small>
            </div>
          </div>
        `;
        
        marker.bindPopup(popupContent, { maxWidth: 250 });
        bounds.push([lat, lng]);
      });
      
      if (bounds.length > 0) {
        map.value.fitBounds(bounds, { padding: [20, 20] });
      }
    };
    
    const carregarDados = async () => {
      loading.value = true;
      
      try {
        // Tentar carregar da API primeiro
        const response = await fetch('../api/imoveis.php');
        if (response.ok) {
          const data = await response.json();
          if (data.status && data.data?.length > 0) {
            todosImoveis.value = data.data;
            return;
          }
        }
      } catch (error) {
        console.log('API não disponível, usando dados locais');
      }
      
      // Usar dados locais como fallback
      todosImoveis.value = IMOVEIS_DATA;
    };
    
    const aplicarFiltros = () => {
      paginaAtual.value = 1;
      if (visualizacao.value === 'mapa') {
        atualizarMapa();
      }
    };
    
    const aplicarOrdenacao = () => {
      paginaAtual.value = 1;
    };
    
    const limparFiltros = () => {
      Object.keys(filtros.value).forEach(key => {
        filtros.value[key] = '';
      });
      paginaAtual.value = 1;
    };
    
    const abrirModal = (imovel) => {
      imovelSelecionado.value = imovel;
      modalAberto.value = true;
      document.body.style.overflow = 'hidden';
    };
    
    const fecharModal = () => {
      modalAberto.value = false;
      imovelSelecionado.value = null;
      document.body.style.overflow = '';
    };
    
    const abrirImagemModal = (src) => {
      window.open(src, '_blank');
    };
    
    const resumirDescricao = (descricao) => {
      if (!descricao) return 'Descrição não disponível';
      const limpa = limparHTML(descricao);
      return limpa.length > 150 ? limpa.substring(0, 150) + '...' : limpa;
    };
    
    // Busca com debounce
    const debounceSearch = debounce(() => {
      aplicarFiltros();
    }, 500);
    
    // Watchers
    watch(() => filtros.value, aplicarFiltros, { deep: true });
    
    watch(() => visualizacao.value, async (novoValor) => {
      if (novoValor === 'mapa') {
        await nextTick();
        if (!map.value) {
          await inicializarMapa();
        } else {
          setTimeout(() => {
            map.value.invalidateSize();
            atualizarMapa();
          }, 100);
        }
      }
    });
    
    watch(() => imoveisFiltrados.value, () => {
      if (visualizacao.value === 'mapa') {
        atualizarMapa();
      }
    });
    
    // Lifecycle
    onMounted(async () => {
      await carregarDados();
      loading.value = false;
      
      // Configurar eventos do teclado
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modalAberto.value) {
          fecharModal();
        }
      });
    });
    
    // Retornar estado e métodos para o template
    return {
      // Estado
      loading,
      modalAberto,
      visualizacao,
      mostrarFiltrosAvancados,
      ordenacao,
      paginaAtual,
      todosImoveis,
      imovelSelecionado,
      filtros,
      
      // Computadas
      cidadesDisponiveis,
      imoveisFiltrados,
      imoveisPaginados,
      totalPaginas,
      paginasVisiveis,
      contadorResultados,
      especificacoes,
      enderecoCompleto,
      descricaoFormatada,
      
      // Métodos
      aplicarFiltros,
      aplicarOrdenacao,
      limparFiltros,
      abrirModal,
      fecharModal,
      abrirImagemModal,
      resumirDescricao,
      formatarMoeda,
      debounceSearch
    };
  }
}).mount('#app');