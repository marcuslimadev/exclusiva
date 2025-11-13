<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Hero Section -->
    <section class="gradient-bg text-white py-20 relative overflow-hidden">
      <div class="absolute inset-0 opacity-10">
        <div class="absolute top-0 left-0 w-96 h-96 bg-white rounded-full filter blur-3xl"></div>
        <div class="absolute bottom-0 right-0 w-96 h-96 bg-white rounded-full filter blur-3xl"></div>
      </div>
      
      <div class="container mx-auto px-4 relative z-10">
        <div class="text-center animate__animated animate__fadeInDown">
          <h1 class="text-5xl md:text-7xl font-bold mb-6">
            🏡 Exclusiva Lar Imóveis
          </h1>
          <p class="text-xl md:text-2xl mb-8 opacity-90">
            Encontre o imóvel dos seus sonhos em Belo Horizonte
          </p>
          <div class="flex flex-wrap justify-center gap-4 mb-8">
            <div class="bg-white/20 backdrop-blur-lg rounded-full px-6 py-3">
              <i class="fas fa-home mr-2"></i>
              <span class="font-semibold">{{ totalImoveis }}</span> Imóveis Disponíveis
            </div>
            <div class="bg-white/20 backdrop-blur-lg rounded-full px-6 py-3">
              <i class="fas fa-map-marker-alt mr-2"></i>
              Belo Horizonte e Região
            </div>
            <div class="bg-white/20 backdrop-blur-lg rounded-full px-6 py-3">
              <i class="fas fa-star mr-2"></i>
              Atendimento 24/7
            </div>
          </div>
        </div>
        
        <!-- Filters -->
        <div class="max-w-5xl mx-auto mt-12 animate__animated animate__fadeInUp">
          <div class="bg-white rounded-2xl shadow-2xl p-6">
            <h3 class="text-gray-800 text-lg font-semibold mb-4">
              <i class="fas fa-filter mr-2"></i>Filtrar Imóveis
            </h3>
            
            <div class="grid md:grid-cols-4 gap-4 mb-4">
              <input 
                v-model="filters.search" 
                type="text" 
                placeholder="🔍 Buscar por bairro, cidade..."
                class="px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:outline-none text-gray-700 transition"
              >
              
              <select v-model="filters.tipo" class="px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:outline-none text-gray-700 transition">
                <option value="">Todos os Tipos</option>
                <option value="apartamento">Apartamento</option>
                <option value="casa">Casa</option>
                <option value="cobertura">Cobertura</option>
                <option value="lote">Lote/Terreno</option>
              </select>
              
              <select v-model="filters.quartos" class="px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:outline-none text-gray-700 transition">
                <option value="">Quartos</option>
                <option value="1">1+ Quarto</option>
                <option value="2">2+ Quartos</option>
                <option value="3">3+ Quartos</option>
                <option value="4">4+ Quartos</option>
              </select>
              
              <select v-model="filters.faixaPreco" class="px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:outline-none text-gray-700 transition">
                <option value="">Faixa de Preço</option>
                <option value="0-300000">Até R$ 300 mil</option>
                <option value="300000-500000">R$ 300-500 mil</option>
                <option value="500000-800000">R$ 500-800 mil</option>
                <option value="800000-999999999">Acima de R$ 800 mil</option>
              </select>
            </div>
            
            <div class="flex flex-wrap gap-2">
              <button 
                @click="limparFiltros"
                class="filter-pill px-4 py-2 bg-gray-200 rounded-full text-sm text-gray-700 hover:bg-gray-300"
              >
                <i class="fas fa-times mr-1"></i>Limpar Filtros
              </button>
              <div class="filter-pill px-4 py-2 bg-purple-100 rounded-full text-sm text-purple-700">
                <i class="fas fa-check-circle mr-1"></i>{{ imoveisFiltrados.length }} resultados
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    
    <!-- Loading State -->
    <div v-if="loading" class="container mx-auto px-4 py-16">
      <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
        <div v-for="n in 6" :key="n" class="bg-white rounded-2xl overflow-hidden shadow-lg">
          <div class="shimmer h-64"></div>
          <div class="p-6">
            <div class="shimmer h-6 w-3/4 mb-4 rounded"></div>
            <div class="shimmer h-4 w-1/2 mb-2 rounded"></div>
            <div class="shimmer h-4 w-2/3 rounded"></div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Properties Grid -->
    <section v-else class="container mx-auto px-4 py-16">
      <div v-if="imoveisFiltrados.length === 0" class="text-center py-20">
        <i class="fas fa-search text-gray-300 text-6xl mb-4"></i>
        <h3 class="text-2xl font-semibold text-gray-700 mb-2">Nenhum imóvel encontrado</h3>
        <p class="text-gray-500">Tente ajustar os filtros ou entre em contato conosco!</p>
        <button @click="abrirWhatsApp()" class="mt-6 whatsapp-button text-white px-8 py-4 rounded-full font-semibold inline-flex items-center gap-2">
          <i class="fab fa-whatsapp text-2xl"></i>
          Falar com um Corretor
        </button>
      </div>
      
      <div v-else class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
        <div 
          v-for="imovel in imoveisFiltrados" 
          :key="imovel.codigo_imovel"
          class="card-hover bg-white rounded-2xl overflow-hidden shadow-lg cursor-pointer"
          @click="abrirModal(imovel)"
        >
          <!-- Image -->
          <div class="relative h-64 overflow-hidden bg-gray-200">
            <img 
              :src="imovel.imagem_destaque || 'https://via.placeholder.com/400x300?text=Imóvel'" 
              :alt="imovel.tipo_imovel"
              class="image-hover w-full h-full object-cover"
            >
            
            <!-- Badges -->
            <div class="absolute top-4 left-4 flex flex-col gap-2">
              <span v-if="imovel.exclusividade" class="badge-pulse bg-yellow-400 text-yellow-900 px-3 py-1 rounded-full text-xs font-bold">
                ⭐ EXCLUSIVO
              </span>
              <span class="bg-white/90 backdrop-blur-sm text-gray-800 px-3 py-1 rounded-full text-xs font-semibold">
                {{ imovel.tipo_imovel }}
              </span>
            </div>
            
            <!-- Price Tag -->
            <div class="absolute bottom-4 right-4">
              <div class="price-tag text-white px-4 py-2 rounded-full font-bold text-lg shadow-lg">
                {{ formatarPreco(imovel.valor_venda) }}
              </div>
            </div>
          </div>
          
          <!-- Content -->
          <div class="p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-2 line-clamp-1">
              {{ imovel.tipo_imovel }} - {{ imovel.bairro }}
            </h3>
            
            <p class="text-gray-600 text-sm mb-4 flex items-center gap-1">
              <i class="fas fa-map-marker-alt text-purple-500"></i>
              {{ imovel.bairro }}, {{ imovel.cidade }} - {{ imovel.estado }}
            </p>
            
            <!-- Features -->
            <div class="flex flex-wrap gap-3 mb-4 text-sm text-gray-700">
              <div v-if="imovel.dormitorios" class="flex items-center gap-1">
                <i class="fas fa-bed text-purple-500"></i>
                <span>{{ imovel.dormitorios }} quartos</span>
              </div>
              <div v-if="imovel.suites" class="flex items-center gap-1">
                <i class="fas fa-bath text-purple-500"></i>
                <span>{{ imovel.suites }} suítes</span>
              </div>
              <div v-if="imovel.garagem" class="flex items-center gap-1">
                <i class="fas fa-car text-purple-500"></i>
                <span>{{ imovel.garagem }} vagas</span>
              </div>
              <div v-if="imovel.area_total" class="flex items-center gap-1">
                <i class="fas fa-ruler-combined text-purple-500"></i>
                <span>{{ imovel.area_total }}m²</span>
              </div>
            </div>
            
            <!-- CTA -->
            <button 
              @click.stop="abrirWhatsApp(imovel)"
              class="whatsapp-button w-full text-white py-3 rounded-xl font-semibold flex items-center justify-center gap-2"
            >
              <i class="fab fa-whatsapp text-xl"></i>
              Tenho Interesse
            </button>
          </div>
        </div>
      </div>
    </section>
    
    <!-- Modal de Detalhes -->
    <transition name="fade">
      <div 
        v-if="modalAberto" 
        class="modal-backdrop fixed inset-0 z-50 flex items-center justify-center p-4"
        @click.self="fecharModal"
      >
        <div class="zoom-in bg-white rounded-3xl max-w-5xl w-full max-h-[90vh] overflow-y-auto shadow-2xl">
          <div class="relative">
            <!-- Close Button -->
            <button 
              @click="fecharModal"
              class="absolute top-4 right-4 z-10 bg-white/90 backdrop-blur-sm w-12 h-12 rounded-full flex items-center justify-center hover:bg-white transition shadow-lg"
            >
              <i class="fas fa-times text-2xl text-gray-700"></i>
            </button>
            
            <!-- Image -->
            <div class="relative h-96 bg-gray-200">
              <img 
                :src="imovelSelecionado.imagem_destaque || 'https://via.placeholder.com/800x600?text=Imóvel'" 
                :alt="imovelSelecionado.tipo_imovel"
                class="w-full h-full object-cover"
              >
              
              <div class="absolute top-4 left-4 flex flex-wrap gap-2">
                <span v-if="imovelSelecionado.exclusividade" class="bg-yellow-400 text-yellow-900 px-4 py-2 rounded-full text-sm font-bold">
                  ⭐ EXCLUSIVO
                </span>
                <span class="bg-white/90 backdrop-blur-sm text-gray-800 px-4 py-2 rounded-full text-sm font-semibold">
                  {{ imovelSelecionado.tipo_imovel }}
                </span>
              </div>
            </div>
            
            <!-- Content -->
            <div class="p-8">
              <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
                <div>
                  <h2 class="text-3xl font-bold text-gray-800 mb-2">
                    {{ imovelSelecionado.tipo_imovel }}
                  </h2>
                  <p class="text-gray-600 flex items-center gap-2">
                    <i class="fas fa-map-marker-alt text-purple-500"></i>
                    {{ imovelSelecionado.bairro }}, {{ imovelSelecionado.cidade }} - {{ imovelSelecionado.estado }}
                  </p>
                </div>
                <div class="price-tag text-white px-6 py-3 rounded-full font-bold text-2xl shadow-lg mt-4 md:mt-0">
                  {{ formatarPreco(imovelSelecionado.valor_venda) }}
                </div>
              </div>
              
              <!-- Features Grid -->
              <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div v-if="imovelSelecionado.dormitorios" class="bg-purple-50 rounded-xl p-4 text-center">
                  <i class="fas fa-bed text-3xl text-purple-500 mb-2"></i>
                  <p class="text-2xl font-bold text-gray-800">{{ imovelSelecionado.dormitorios }}</p>
                  <p class="text-sm text-gray-600">Quartos</p>
                </div>
                <div v-if="imovelSelecionado.suites" class="bg-purple-50 rounded-xl p-4 text-center">
                  <i class="fas fa-bath text-3xl text-purple-500 mb-2"></i>
                  <p class="text-2xl font-bold text-gray-800">{{ imovelSelecionado.suites }}</p>
                  <p class="text-sm text-gray-600">Suítes</p>
                </div>
                <div v-if="imovelSelecionado.garagem" class="bg-purple-50 rounded-xl p-4 text-center">
                  <i class="fas fa-car text-3xl text-purple-500 mb-2"></i>
                  <p class="text-2xl font-bold text-gray-800">{{ imovelSelecionado.garagem }}</p>
                  <p class="text-sm text-gray-600">Vagas</p>
                </div>
                <div v-if="imovelSelecionado.area_total" class="bg-purple-50 rounded-xl p-4 text-center">
                  <i class="fas fa-ruler-combined text-3xl text-purple-500 mb-2"></i>
                  <p class="text-2xl font-bold text-gray-800">{{ imovelSelecionado.area_total }}</p>
                  <p class="text-sm text-gray-600">m²</p>
                </div>
              </div>
              
              <!-- Description -->
              <div v-if="imovelSelecionado.descricao" class="mb-6">
                <h3 class="text-xl font-bold text-gray-800 mb-3">
                  <i class="fas fa-info-circle mr-2"></i>Descrição
                </h3>
                <p class="text-gray-700 leading-relaxed">
                  {{ imovelSelecionado.descricao }}
                </p>
              </div>
              
              <!-- Additional Info -->
              <div class="grid md:grid-cols-2 gap-4 mb-6">
                <div v-if="imovelSelecionado.valor_condominio" class="bg-gray-50 rounded-xl p-4">
                  <p class="text-sm text-gray-600 mb-1">Condomínio</p>
                  <p class="text-lg font-bold text-gray-800">{{ formatarPreco(imovelSelecionado.valor_condominio) }}</p>
                </div>
                <div v-if="imovelSelecionado.valor_iptu" class="bg-gray-50 rounded-xl p-4">
                  <p class="text-sm text-gray-600 mb-1">IPTU</p>
                  <p class="text-lg font-bold text-gray-800">{{ formatarPreco(imovelSelecionado.valor_iptu) }}</p>
                </div>
              </div>
              
              <!-- WhatsApp CTA -->
              <button 
                @click="abrirWhatsApp(imovelSelecionado)"
                class="whatsapp-button w-full text-white py-4 rounded-xl font-bold text-lg flex items-center justify-center gap-3 shadow-lg"
              >
                <i class="fab fa-whatsapp text-3xl"></i>
                <span>Falar com um Corretor sobre este Imóvel</span>
              </button>
              
              <p class="text-center text-sm text-gray-500 mt-4">
                Ref: {{ imovelSelecionado.referencia_imovel || imovelSelecionado.codigo_imovel }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </transition>
    
    <!-- Floating WhatsApp Button -->
    <a 
      href="https://wa.me/553173341150?text=Olá!%20Vim%20do%20site%20e%20gostaria%20de%20mais%20informações" 
      target="_blank"
      class="fixed bottom-8 right-8 whatsapp-button w-16 h-16 rounded-full flex items-center justify-center shadow-2xl z-40"
    >
      <i class="fab fa-whatsapp text-3xl text-white"></i>
    </a>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../services/api'

const imoveis = ref([])
const loading = ref(true)
const modalAberto = ref(false)
const imovelSelecionado = ref({})
const filters = ref({
  search: '',
  tipo: '',
  quartos: '',
  faixaPreco: ''
})

const totalImoveis = computed(() => imoveis.value.length)

const imoveisFiltrados = computed(() => {
  let result = imoveis.value
  
  // Filtro de busca
  if (filters.value.search) {
    const search = filters.value.search.toLowerCase()
    result = result.filter(i => 
      (i.bairro?.toLowerCase().includes(search)) ||
      (i.cidade?.toLowerCase().includes(search)) ||
      (i.tipo_imovel?.toLowerCase().includes(search))
    )
  }
  
  // Filtro de tipo
  if (filters.value.tipo) {
    result = result.filter(i => 
      i.tipo_imovel?.toLowerCase().includes(filters.value.tipo.toLowerCase())
    )
  }
  
  // Filtro de quartos
  if (filters.value.quartos) {
    const minQuartos = parseInt(filters.value.quartos)
    result = result.filter(i => i.dormitorios >= minQuartos)
  }
  
  // Filtro de preço
  if (filters.value.faixaPreco) {
    const [min, max] = filters.value.faixaPreco.split('-').map(Number)
    result = result.filter(i => {
      const preco = parseFloat(i.valor_venda)
      return preco >= min && preco <= max
    })
  }
  
  return result
})

const carregarImoveis = async () => {
  try {
    loading.value = true
    const response = await api.get('/api/properties')
    imoveis.value = response.data.data || []
  } catch (error) {
    console.error('Erro ao carregar imóveis:', error)
  } finally {
    loading.value = false
  }
}

const formatarPreco = (valor) => {
  if (!valor) return 'Consulte'
  return new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(valor)
}

const abrirModal = (imovel) => {
  imovelSelecionado.value = imovel
  modalAberto.value = true
  document.body.style.overflow = 'hidden'
}

const fecharModal = () => {
  modalAberto.value = false
  document.body.style.overflow = 'auto'
}

const abrirWhatsApp = (imovel = null) => {
  const telefone = '553173341150'
  let mensagem = 'Olá! Vim do site e gostaria de mais informações'
  
  if (imovel) {
    mensagem = `Olá! Vi no site o imóvel *${imovel.tipo_imovel}* no bairro *${imovel.bairro}* (Ref: ${imovel.referencia_imovel || imovel.codigo_imovel}) e gostaria de mais informações. 🏡`
  }
  
  const url = `https://wa.me/${telefone}?text=${encodeURIComponent(mensagem)}`
  window.open(url, '_blank')
}

const limparFiltros = () => {
  filters.value = {
    search: '',
    tipo: '',
    quartos: '',
    faixaPreco: ''
  }
}

onMounted(() => {
  carregarImoveis()
})
</script>

<style scoped>
.gradient-bg {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.card-hover {
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.card-hover:hover {
  transform: translateY(-12px);
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

.image-hover {
  transition: transform 0.6s ease;
}

.card-hover:hover .image-hover {
  transform: scale(1.1);
}

.badge-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: .7; }
}

.shimmer {
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

.modal-backdrop {
  backdrop-filter: blur(8px);
  background-color: rgba(0, 0, 0, 0.7);
}

.zoom-in {
  animation: zoomIn 0.3s ease-out;
}

@keyframes zoomIn {
  from {
    transform: scale(0.9);
    opacity: 0;
  }
  to {
    transform: scale(1);
    opacity: 1;
  }
}

.price-tag {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.whatsapp-button {
  background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
  transition: all 0.3s ease;
}

.whatsapp-button:hover {
  transform: scale(1.05);
  box-shadow: 0 10px 25px rgba(37, 211, 102, 0.4);
}

.filter-pill {
  transition: all 0.3s ease;
}

.filter-pill:hover {
  transform: translateY(-2px);
}

.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s;
}

.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>
