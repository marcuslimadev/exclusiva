import { defineStore } from 'pinia'
import api from '../services/api'

export const useDashboardStore = defineStore('dashboard', {
  state: () => ({
    stats: {
      totalLeads: 0,
      conversasAtivas: 0,
      leadsHoje: 0,
      taxaConversao: 0
    },
    atividadesRecentes: [],
    loading: false
  }),

  actions: {
    async fetchStats() {
      this.loading = true
      try {
        const response = await api.get('/api/dashboard/stats')
        this.stats = response.data
      } catch (error) {
        console.error('Erro ao buscar estatísticas', error)
      } finally {
        this.loading = false
      }
    },

    async fetchAtividades() {
      try {
        const response = await api.get('/api/dashboard/atividades')
        this.atividadesRecentes = response.data
      } catch (error) {
        console.error('Erro ao buscar atividades', error)
      }
    }
  }
})
