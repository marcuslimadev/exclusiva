<template>
  <nav class="bg-white shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between h-16">
        <div class="flex items-center space-x-8">
          <h1 class="text-xl font-bold text-blue-600">Exclusiva Lar</h1>
          
          <div class="hidden md:flex space-x-4">
            <router-link
              to="/dashboard"
              class="px-3 py-2 rounded-md text-sm font-medium transition"
              :class="isActive('/dashboard') ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100'"
            >
              Dashboard
            </router-link>
            <router-link
              to="/conversas"
              class="px-3 py-2 rounded-md text-sm font-medium transition"
              :class="isActive('/conversas') ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100'"
            >
              Conversas
            </router-link>
            <router-link
              to="/leads"
              class="px-3 py-2 rounded-md text-sm font-medium transition"
              :class="isActive('/leads') ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100'"
            >
              Leads
            </router-link>
          </div>
        </div>

        <div class="flex items-center space-x-4">
          <span class="text-sm text-gray-700">{{ user?.nome || user?.email }}</span>
          <button
            @click="handleLogout"
            class="px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 rounded-md transition"
          >
            Sair
          </button>
        </div>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const user = computed(() => authStore.user)

const isActive = (path) => {
  return route.path === path
}

const handleLogout = async () => {
  await authStore.logout()
  router.push('/login')
}
</script>
