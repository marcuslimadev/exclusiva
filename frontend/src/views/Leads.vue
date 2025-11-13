<template>
  <div class="min-h-screen bg-gray-50">
    <Navbar />
    
    <div class="px-4 sm:px-6 lg:px-8 py-8">
      <div class="mb-6 flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">🎯 Funil de Vendas</h1>
          <p class="text-gray-600 mt-1">Kanban Board - Arraste os cards entre as etapas do funil</p>
        </div>
        
        <!-- Filtros -->
        <div class="flex items-center space-x-4">
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
          <FunilColumn
            v-for="statusFunil in statusDoFunil"
            :key="statusFunil.key"
            :status="statusFunil"
            :leads="getLeadsByStatus(statusFunil.key)"
            @dragstart="handleDragStart"
            @drop="handleDrop($event, statusFunil.key)"
            @view="verDetalhes"
            @edit="editarLead"
          />
          
          <!-- Empty State -->
          <div v-if="leads.length === 0" class="w-full text-center py-12">
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
                Orçamento
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Status Funil
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Ações
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="lead in leads" :key="lead.id" class="hover:bg-gray-50">
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
                  {{ formatStatus(lead.status) }}
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

        <div v-if="!loading && leads.length === 0" class="text-center py-8 text-gray-500">
          Nenhum lead encontrado
        </div>
      </div>
    </div>

    <!-- Modals -->
    <LeadDetailsModal
      :is-open="showDetailsModal"
      :lead-id="selectedLeadId"
      @close="showDetailsModal = false"
      @edit="handleEditFromDetails"
    />

    <LeadEditModal
      :is-open="showEditModal"
      :lead="selectedLead"
      @close="showEditModal = false"
      @saved="handleLeadSaved"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useLeadsStore } from '../stores/leads'
import Navbar from '../components/Navbar.vue'
import FunilColumn from '../components/FunilColumn.vue'
import LeadDetailsModal from '../components/LeadDetailsModal.vue'
import LeadEditModal from '../components/LeadEditModal.vue'

const leadsStore = useLeadsStore()

const leads = computed(() => leadsStore.leads)
const loading = computed(() => leadsStore.loading)

const viewMode = ref('kanban') // 'kanban' ou 'table'
const draggedLead = ref(null)

// Modals
const showDetailsModal = ref(false)
const showEditModal = ref(false)
const selectedLeadId = ref(null)
const selectedLead = ref(null)

// Status do Funil de Vendas (ordem correta do processo)
const statusDoFunil = [
  { key: 'novo', label: 'Novo Lead', icon: '🆕', color: 'blue' },
  { key: 'em_atendimento', label: 'Em Atendimento', icon: '💬', color: 'yellow' },
  { key: 'qualificado', label: 'Qualificado', icon: '✅', color: 'green' },
  { key: 'proposta', label: 'Proposta', icon: '📋', color: 'purple' },
  { key: 'fechado', label: 'Fechado', icon: '🎉', color: 'emerald' },
  { key: 'perdido', label: 'Perdido', icon: '❌', color: 'red' }
]

onMounted(() => {
  leadsStore.fetchLeads()
})

// Agrupar leads por status do funil
const getLeadsByStatus = (status) => {
  return leads.value.filter(lead => lead.status === status)
}

// Drag and Drop handlers
const handleDragStart = (lead) => {
  draggedLead.value = lead
}

const handleDrop = async (event, targetStatus) => {
  event.preventDefault()
  
  if (!draggedLead.value) return
  
  const leadId = draggedLead.value.id
  const oldStatus = draggedLead.value.status
  
  if (oldStatus === targetStatus) {
    draggedLead.value = null
    return
  }

  try {
    // Atualizar no backend
    await leadsStore.updateLeadStatus(leadId, targetStatus)
    
    // Recarregar leads para atualizar o board
    await leadsStore.fetchLeads()
    
    console.log(`✅ Lead movido: ${oldStatus} → ${targetStatus}`)
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
    'proposta': 'bg-purple-100 text-purple-800',
    'fechado': 'bg-emerald-100 text-emerald-800',
    'perdido': 'bg-red-100 text-red-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const formatStatus = (status) => {
  const labels = {
    'novo': 'Novo',
    'em_atendimento': 'Em Atendimento',
    'qualificado': 'Qualificado',
    'proposta': 'Proposta',
    'fechado': 'Fechado',
    'perdido': 'Perdido'
  }
  return labels[status] || status
}

// Ações
const verDetalhes = (lead) => {
  selectedLeadId.value = lead.id
  showDetailsModal.value = true
}

const editarLead = (lead) => {
  selectedLead.value = lead
  showEditModal.value = true
}

const handleEditFromDetails = (lead) => {
  showDetailsModal.value = false
  setTimeout(() => {
    selectedLead.value = lead
    showEditModal.value = true
  }, 300)
}

const handleLeadSaved = async () => {
  await leadsStore.fetchLeads()
}
</script>
