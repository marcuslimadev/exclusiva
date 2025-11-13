import { defineStore } from 'pinia'
import api from '../services/api'

export const useConversasStore = defineStore('conversas', {
  state: () => ({
    conversas: [],
    conversaAtiva: null,
    mensagens: [],
    loading: false
  }),

  actions: {
    async fetchConversas() {
      this.loading = true
      try {
        const response = await api.get('/api/conversas')
        this.conversas = response.data.data || response.data
      } catch (error) {
        console.error('Erro ao buscar conversas', error)
      } finally {
        this.loading = false
      }
    },

    async selecionarConversa(id) {
      this.loading = true
      try {
        const response = await api.get(`/api/conversas/${id}`)
        const data = response.data.data || response.data
        this.conversaAtiva = data
        this.mensagens = data.mensagens || []
        console.log('📩 Conversa carregada:', {
          id: data.id,
          telefone: data.telefone,
          nome: data.lead_nome,
          totalMensagens: this.mensagens.length,
          mensagens: this.mensagens
        })
      } catch (error) {
        console.error('Erro ao selecionar conversa', error)
      } finally {
        this.loading = false
      }
    },

    async enviarMensagem(conversaId, mensagem) {
      try {
        const response = await api.post(`/api/conversas/${conversaId}/mensagens`, {
          mensagem
        })
        
        // Adicionar mensagem à lista
        this.mensagens.push(response.data)
        
        return true
      } catch (error) {
        console.error('Erro ao enviar mensagem', error)
        return false
      }
    }
  }
})
