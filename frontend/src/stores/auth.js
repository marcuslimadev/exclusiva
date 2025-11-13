import { defineStore } from 'pinia'
import api from '../services/api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('token') || null,
    loading: false,
    error: null
  }),

  getters: {
    isAuthenticated: (state) => !!state.token,
    isAdmin: (state) => state.user?.tipo === 'admin'
  },

  actions: {
    async login(email, senha) {
      this.loading = true
      this.error = null
      
      try {
        const response = await api.post('/api/auth/login', { email, senha })
        
        this.token = response.data.token
        this.user = response.data.user
        
        localStorage.setItem('token', this.token)
        
        return true
      } catch (error) {
        this.error = error.response?.data?.message || 'Erro ao fazer login'
        return false
      } finally {
        this.loading = false
      }
    },

    async logout() {
      try {
        await api.post('/api/auth/logout')
      } catch (error) {
        console.error('Erro ao fazer logout', error)
      } finally {
        this.token = null
        this.user = null
        localStorage.removeItem('token')
      }
    },

    async checkAuth() {
      if (!this.token) return false
      
      try {
        const response = await api.get('/api/auth/me')
        this.user = response.data
        return true
      } catch (error) {
        this.logout()
        return false
      }
    }
  }
})
