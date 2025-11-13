<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="isOpen"
        class="fixed inset-0 z-50 overflow-y-auto"
        @click.self="close"
      >
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>
        
        <!-- Modal -->
        <div class="flex min-h-screen items-center justify-center p-4">
          <div class="relative bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4 flex items-center justify-between">
              <div class="flex items-center space-x-3">
                <div class="bg-white/20 p-2 rounded-full">
                  <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                  </svg>
                </div>
                <div>
                  <h2 class="text-xl font-bold text-white">Detalhes do Lead</h2>
                  <p class="text-blue-100 text-sm">Informações completas</p>
                </div>
              </div>
              <button
                @click="close"
                class="text-white hover:bg-white/20 rounded-full p-2 transition"
              >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>

            <!-- Content -->
            <div v-if="loading" class="p-8 text-center">
              <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
              <p class="text-gray-600 mt-4">Carregando detalhes...</p>
            </div>

            <div v-else-if="lead" class="overflow-y-auto max-h-[calc(90vh-80px)]">
              <!-- Informações Principais -->
              <div class="p-6 border-b">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <!-- Nome -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
                    <div class="flex items-center space-x-2">
                      <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                      </svg>
                      <span class="text-gray-900 font-semibold">{{ lead.nome || 'Sem nome' }}</span>
                    </div>
                  </div>

                  <!-- Telefone -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                    <div class="flex items-center space-x-2">
                      <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                      </svg>
                      <span class="text-gray-900">{{ formatPhone(lead.telefone) }}</span>
                    </div>
                  </div>

                  <!-- Email -->
                  <div v-if="lead.email">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <div class="flex items-center space-x-2">
                      <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                        <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                      </svg>
                      <span class="text-gray-900">{{ lead.email }}</span>
                    </div>
                  </div>

                  <!-- Status -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <span
                      class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold"
                      :class="getStatusClass(lead.status)"
                    >
                      {{ formatStatus(lead.status) }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Localização -->
              <div v-if="lead.city || lead.state" class="p-6 bg-gray-50 border-b">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                  <svg class="w-5 h-5 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                  </svg>
                  Localização
                </h3>
                <div class="grid grid-cols-2 gap-4">
                  <div v-if="lead.city">
                    <span class="text-sm text-gray-600">Cidade:</span>
                    <p class="font-semibold text-gray-900">{{ lead.city }}</p>
                  </div>
                  <div v-if="lead.state">
                    <span class="text-sm text-gray-600">Estado:</span>
                    <p class="font-semibold text-gray-900">{{ lead.state }}</p>
                  </div>
                  <div v-if="lead.localizacao" class="col-span-2">
                    <span class="text-sm text-gray-600">Endereço completo:</span>
                    <p class="text-gray-900">{{ lead.localizacao }}</p>
                  </div>
                </div>
              </div>

              <!-- Preferências de Imóvel -->
              <div class="p-6 border-b">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                  <svg class="w-5 h-5 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                  </svg>
                  Preferências de Imóvel
                </h3>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                  <!-- Orçamento -->
                  <div class="col-span-2 bg-green-50 rounded-lg p-4">
                    <span class="text-sm text-green-700 font-medium">💰 Orçamento</span>
                    <p class="text-xl font-bold text-green-900 mt-1">
                      R$ {{ formatMoney(lead.budget_min) }} - R$ {{ formatMoney(lead.budget_max) }}
                    </p>
                  </div>

                  <!-- Quartos -->
                  <div v-if="lead.quartos" class="bg-blue-50 rounded-lg p-4 text-center">
                    <span class="text-sm text-blue-700">🛏️ Quartos</span>
                    <p class="text-2xl font-bold text-blue-900">{{ lead.quartos }}</p>
                  </div>

                  <!-- Suítes -->
                  <div v-if="lead.suites" class="bg-purple-50 rounded-lg p-4 text-center">
                    <span class="text-sm text-purple-700">🚿 Suítes</span>
                    <p class="text-2xl font-bold text-purple-900">{{ lead.suites }}</p>
                  </div>

                  <!-- Garagem -->
                  <div v-if="lead.garagem" class="bg-orange-50 rounded-lg p-4 text-center">
                    <span class="text-sm text-orange-700">🚗 Garagem</span>
                    <p class="text-2xl font-bold text-orange-900">{{ lead.garagem }}</p>
                  </div>
                </div>

                <!-- Características Desejadas -->
                <div v-if="lead.caracteristicas_desejadas">
                  <label class="block text-sm font-medium text-gray-700 mb-2">Características Desejadas</label>
                  <p class="text-gray-900 bg-gray-50 p-3 rounded-lg">{{ lead.caracteristicas_desejadas }}</p>
                </div>
              </div>

              <!-- Origem e Datas -->
              <div class="p-6 bg-gray-50">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Informações Adicionais</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                  <div>
                    <span class="text-sm text-gray-600">Origem:</span>
                    <p class="font-semibold text-gray-900 capitalize flex items-center">
                      <svg v-if="lead.origem === 'whatsapp'" class="w-4 h-4 mr-1 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                      </svg>
                      {{ lead.origem || 'Manual' }}
                    </p>
                  </div>
                  <div v-if="lead.score">
                    <span class="text-sm text-gray-600">Score:</span>
                    <p class="font-semibold text-gray-900">{{ lead.score }}/100</p>
                  </div>
                  <div>
                    <span class="text-sm text-gray-600">Criado em:</span>
                    <p class="text-gray-900">{{ formatDate(lead.created_at) }}</p>
                  </div>
                  <div v-if="lead.primeira_interacao">
                    <span class="text-sm text-gray-600">Primeira Interação:</span>
                    <p class="text-gray-900">{{ formatDate(lead.primeira_interacao) }}</p>
                  </div>
                  <div v-if="lead.ultima_interacao">
                    <span class="text-sm text-gray-600">Última Interação:</span>
                    <p class="text-gray-900">{{ formatDate(lead.ultima_interacao) }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Footer -->
            <div class="bg-gray-50 px-6 py-4 flex justify-end space-x-3 border-t">
              <button
                @click="close"
                class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition font-medium"
              >
                Fechar
              </button>
              <button
                @click="editLead"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium flex items-center"
              >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Editar Lead
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, watch } from 'vue'
import api from '../services/api'

const props = defineProps({
  isOpen: Boolean,
  leadId: [Number, String]
})

const emit = defineEmits(['close', 'edit'])

const lead = ref(null)
const loading = ref(false)

watch(() => props.isOpen, async (newVal) => {
  if (newVal && props.leadId) {
    await fetchLeadDetails()
  }
})

const fetchLeadDetails = async () => {
  loading.value = true
  try {
    const response = await api.get(`/api/leads/${props.leadId}`)
    lead.value = response.data.data || response.data
  } catch (error) {
    console.error('Erro ao carregar detalhes do lead:', error)
    alert('Erro ao carregar detalhes do lead')
  } finally {
    loading.value = false
  }
}

const close = () => {
  emit('close')
}

const editLead = () => {
  emit('edit', lead.value)
}

const formatPhone = (phone) => {
  if (!phone) return ''
  const cleaned = phone.replace(/\D/g, '')
  if (cleaned.length === 13) {
    return `+${cleaned.slice(0, 2)} (${cleaned.slice(2, 4)}) ${cleaned.slice(4, 9)}-${cleaned.slice(9)}`
  }
  return phone
}

const formatMoney = (value) => {
  if (!value) return '0'
  return new Intl.NumberFormat('pt-BR', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(value)
}

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('pt-BR', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatStatus = (status) => {
  const labels = {
    'novo': 'Novo',
    'em_atendimento': 'Em Atendimento',
    'qualificado': 'Qualificado',
    'convertido': 'Convertido',
    'perdido': 'Perdido',
    'fechado': 'Fechado'
  }
  return labels[status] || status
}

const getStatusClass = (status) => {
  const classes = {
    'novo': 'bg-blue-100 text-blue-700',
    'em_atendimento': 'bg-yellow-100 text-yellow-700',
    'qualificado': 'bg-green-100 text-green-700',
    'convertido': 'bg-purple-100 text-purple-700',
    'perdido': 'bg-red-100 text-red-700',
    'fechado': 'bg-gray-100 text-gray-700'
  }
  return classes[status] || 'bg-gray-100 text-gray-700'
}
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-active .relative,
.modal-leave-active .relative {
  transition: transform 0.3s ease;
}

.modal-enter-from .relative,
.modal-leave-to .relative {
  transform: scale(0.9);
}
</style>
