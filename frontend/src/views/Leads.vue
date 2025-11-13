<template>
  <div class="min-h-screen bg-gray-50">
    <Navbar />
    
    <div class="px-4 sm:px-6 lg:px-8 py-8">
      <div class="mb-6 flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">📍 Leads por Estado</h1>
          <p class="text-gray-600 mt-1">Board Kanban - Arraste os cards entre estados</p>
        </div>
        
        <!-- Filtros -->
        <div class="flex items-center space-x-4">
          <select
            v-model="filtros.status"
            class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white"
          >
            <option value="todos">📊 Todos os Status</option>
            <option value="novo">🆕 Novo</option>
            <option value="em_atendimento">⏳ Em Atendimento</option>
            <option value="qualificado">✅ Qualificado</option>
            <option value="convertido">🎉 Convertido</option>
          </select>

          <button
            @click="toggleView"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2"
          >
            <svg v-if="viewMode === 'kanban'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
            </svg>
            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
            </svg>
            {{ viewMode === 'kanban' ? 'Lista' : 'Kanban' }}
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="text-center py-12">
        <div class="animate-spin rounded-full h-16 w-16 border-b-4 border-blue-600 mx-auto"></div>
        <p class="text-gray-600 mt-4">Carregando leads...</p>
      </div>

      <!-- Kanban Board View -->
      <div v-else-if="viewMode === 'kanban'" class="overflow-x-auto pb-4">
        <div class="flex gap-4 min-w-max">
          <KanbanColumn
            v-for="estado in estadosComLeads"
            :key="estado"
            :state="estado"
            :leads="getLeadsByState(estado)"
            @dragstart="handleDragStart"
            @drop="handleDrop($event, estado)"
            @view="verDetalhes"
            @edit="editarLead"
          />
          
          <!-- Empty State -->
          <div v-if="estadosComLeads.length === 0" class="w-full text-center py-12">
            <svg class="w-24 h-24 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Nenhum lead encontrado</h3>
            <p class="text-gray-600">Os leads capturados via WhatsApp aparecerão aqui</p>
          </div>
        </div>
      </div>

      <!-- Table View (Original) -->
      <div v-else class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Nome
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Telefone
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Estado
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Orçamento
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Status
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Ações
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="lead in leadsFiltrados" :key="lead.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ lead.nome || 'Sem nome' }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">{{ lead.telefone }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ lead.state || '-' }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">
                  R$ {{ formatarMoeda(lead.budget_min) }} - R$ {{ formatarMoeda(lead.budget_max) }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                  :class="getStatusClass(lead.status)"
                >
                  {{ lead.status }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <button
                  @click="verDetalhes(lead)"
                  class="text-blue-600 hover:text-blue-900 mr-4"
                >
                  Ver
                </button>
                <button
                  @click="editarLead(lead)"
                  class="text-green-600 hover:text-green-900"
                >
                  Editar
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-if="!loading && leadsFiltrados.length === 0" class="text-center py-8 text-gray-500">
          Nenhum lead encontrado
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useLeadsStore } from '../stores/leads'
import Navbar from '../components/Navbar.vue'
import KanbanColumn from '../components/KanbanColumn.vue'

const leadsStore = useLeadsStore()

const leadsFiltrados = computed(() => leadsStore.leadsFiltrados)
const loading = computed(() => leadsStore.loading)
const filtros = computed(() => leadsStore.filtros)

const viewMode = ref('kanban') // 'kanban' ou 'table'
const draggedLead = ref(null)

onMounted(() => {
  leadsStore.fetchLeads()
})

// Agrupar leads por estado
const estadosComLeads = computed(() => {
  // Pegar estados únicos (incluindo null/undefined)
  const todosEstados = leadsFiltrados.value.map(lead => lead.state || 'SEM_ESTADO')
  const estadosUnicos = [...new Set(todosEstados)]
  
  // Ordenar: estados com sigla primeiro (alfabético), depois "SEM_ESTADO"
  return estadosUnicos.sort((a, b) => {
    if (a === 'SEM_ESTADO') return 1
    if (b === 'SEM_ESTADO') return -1
    return a.localeCompare(b)
  })
})

const getLeadsByState = (state) => {
  if (state === 'SEM_ESTADO') {
    return leadsFiltrados.value.filter(lead => !lead.state)
  }
  return leadsFiltrados.value.filter(lead => lead.state === state)
}

// Drag and Drop handlers
const handleDragStart = (lead) => {
  draggedLead.value = lead
}

const handleDrop = async (event, targetState) => {
  event.preventDefault()
  
  if (!draggedLead.value) return
  
  const leadId = draggedLead.value.id
  const oldState = draggedLead.value.state
  
  if (oldState === targetState) {
    draggedLead.value = null
    return
  }

  try {
    // Atualizar no backend
    await leadsStore.updateLeadState(leadId, targetState)
    
    // Recarregar leads para atualizar o board
    await leadsStore.fetchLeads()
    
    console.log(`✅ Lead movido: ${oldState} → ${targetState}`)
  } catch (error) {
    console.error('❌ Erro ao mover lead:', error)
    alert('Erro ao mover o lead. Tente novamente.')
  } finally {
    draggedLead.value = null
  }
}

// View toggle
const toggleView = () => {
  viewMode.value = viewMode.value === 'kanban' ? 'table' : 'kanban'
}

// Formatação
const formatarMoeda = (valor) => {
  if (!valor) return '0'
  return new Intl.NumberFormat('pt-BR').format(valor)
}

const getStatusClass = (status) => {
  const classes = {
    'novo': 'bg-blue-100 text-blue-800',
    'em_atendimento': 'bg-yellow-100 text-yellow-800',
    'qualificado': 'bg-green-100 text-green-800',
    'convertido': 'bg-purple-100 text-purple-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

// Ações
const verDetalhes = (lead) => {
  alert(`Ver detalhes do lead: ${lead.nome || lead.telefone}`)
}

const editarLead = (lead) => {
  alert(`Editar lead: ${lead.nome || lead.telefone}`)
}
</script>
