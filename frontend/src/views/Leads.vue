<template>
  <div class="min-h-screen bg-gray-50">
    <Navbar />
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="mb-8 flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Leads</h1>
          <p class="text-gray-600 mt-1">Gerencie os leads capturados via WhatsApp</p>
        </div>
        
        <!-- Filtros -->
        <div class="flex space-x-4">
          <select
            v-model="filtros.status"
            class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          >
            <option value="todos">Todos os Status</option>
            <option value="novo">Novo</option>
            <option value="em_atendimento">Em Atendimento</option>
            <option value="qualificado">Qualificado</option>
            <option value="convertido">Convertido</option>
          </select>
        </div>
      </div>

      <!-- Tabela de Leads -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
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
                Localização
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
                <div class="text-sm text-gray-900">
                  R$ {{ formatarMoeda(lead.budget_min) }} - R$ {{ formatarMoeda(lead.budget_max) }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">{{ lead.localizacao || '-' }}</div>
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

        <div v-if="loading" class="text-center py-8">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
        </div>

        <div v-if="!loading && leadsFiltrados.length === 0" class="text-center py-8 text-gray-500">
          Nenhum lead encontrado
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, computed } from 'vue'
import { useLeadsStore } from '../stores/leads'
import Navbar from '../components/Navbar.vue'

const leadsStore = useLeadsStore()

const leadsFiltrados = computed(() => leadsStore.leadsFiltrados)
const loading = computed(() => leadsStore.loading)
const filtros = computed(() => leadsStore.filtros)

onMounted(() => {
  leadsStore.fetchLeads()
})

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

const verDetalhes = (lead) => {
  alert(`Ver detalhes do lead: ${lead.nome || lead.telefone}`)
}

const editarLead = (lead) => {
  alert(`Editar lead: ${lead.nome || lead.telefone}`)
}
</script>
