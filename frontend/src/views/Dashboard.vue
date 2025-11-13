<template>
  <div class="min-h-screen bg-gray-50">
    <Navbar />
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
        <p class="text-gray-600 mt-1">Visão geral do atendimento WhatsApp</p>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <StatCard
          title="Total de Leads"
          :value="stats.totalLeads"
          icon="users"
          color="blue"
        />
        <StatCard
          title="Conversas Ativas"
          :value="stats.conversasAtivas"
          icon="chat"
          color="green"
        />
        <StatCard
          title="Leads Hoje"
          :value="stats.leadsHoje"
          icon="calendar"
          color="purple"
        />
        <StatCard
          title="Taxa de Conversão"
          :value="`${stats.taxaConversao}%`"
          icon="chart"
          color="orange"
        />
      </div>

      <!-- Atividades Recentes -->
      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold mb-4">Atividades Recentes</h2>
        
        <div v-if="loading" class="text-center py-8">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
        </div>

        <div v-else class="space-y-4">
          <div
            v-for="atividade in atividadesRecentes"
            :key="atividade.id"
            class="flex items-start space-x-4 p-4 hover:bg-gray-50 rounded-lg transition"
          >
            <div class="flex-shrink-0">
              <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                <span class="text-blue-600 font-semibold">
                  {{ atividade.tipo === 'novo_lead' ? '👤' : '💬' }}
                </span>
              </div>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900">
                {{ atividade.descricao }}
              </p>
              <p class="text-sm text-gray-500 mt-1">
                {{ formatarData(atividade.created_at) }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, computed } from 'vue'
import { useDashboardStore } from '../stores/dashboard'
import Navbar from '../components/Navbar.vue'
import StatCard from '../components/StatCard.vue'

const dashboardStore = useDashboardStore()

const stats = computed(() => dashboardStore.stats)
const atividadesRecentes = computed(() => dashboardStore.atividadesRecentes)
const loading = computed(() => dashboardStore.loading)

onMounted(async () => {
  await dashboardStore.fetchStats()
  await dashboardStore.fetchAtividades()
  
  // Atualizar stats a cada 30 segundos
  setInterval(() => {
    dashboardStore.fetchStats()
    dashboardStore.fetchAtividades()
  }, 30000)
})

const formatarData = (data) => {
  return new Date(data).toLocaleString('pt-BR')
}
</script>
