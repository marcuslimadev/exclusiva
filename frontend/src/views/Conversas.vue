<template>
  <div class="min-h-screen bg-gray-50">
    <Navbar />
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Conversas WhatsApp</h1>
        <p class="text-gray-600 mt-1">Gerencie os atendimentos em tempo real</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Lista de Conversas -->
        <div class="lg:col-span-1 bg-white rounded-lg shadow">
          <div class="p-4 border-b">
            <h2 class="font-semibold text-gray-900">Conversas Ativas</h2>
          </div>
          
          <div class="overflow-y-auto" style="max-height: calc(100vh - 300px)">
            <div
              v-for="conversa in conversas"
              :key="conversa.id"
              @click="selecionarConversa(conversa.id)"
              class="p-4 border-b hover:bg-gray-50 cursor-pointer transition"
              :class="{ 'bg-blue-50': conversaAtiva?.id === conversa.id }"
            >
              <div class="flex items-center justify-between mb-2">
                <span class="font-medium text-gray-900">{{ conversa.telefone }}</span>
                <span class="text-xs text-gray-500">{{ formatarHora(conversa.updated_at) }}</span>
              </div>
              <p class="text-sm text-gray-600 truncate">{{ conversa.ultima_mensagem }}</p>
            </div>
          </div>
        </div>

        <!-- Área de Chat -->
        <div class="lg:col-span-2 bg-white rounded-lg shadow flex flex-col" style="height: calc(100vh - 200px)">
          <div v-if="!conversaAtiva" class="flex-1 flex items-center justify-center text-gray-500">
            Selecione uma conversa para começar
          </div>

          <template v-else>
            <!-- Header da Conversa -->
            <div class="p-4 border-b flex items-center justify-between">
              <div>
                <h3 class="font-semibold text-gray-900">{{ conversaAtiva.telefone }}</h3>
                <p class="text-sm text-gray-500">{{ conversaAtiva.status }}</p>
              </div>
            </div>

            <!-- Mensagens -->
            <div class="flex-1 overflow-y-auto p-4 space-y-4" ref="mensagensContainer">
              <div
                v-for="mensagem in mensagens"
                :key="mensagem.id"
                class="flex"
                :class="mensagem.direction === 'outgoing' ? 'justify-end' : 'justify-start'"
              >
                <div
                  class="max-w-xs lg:max-w-md px-4 py-2 rounded-lg"
                  :class="mensagem.direction === 'outgoing' 
                    ? 'bg-blue-600 text-white' 
                    : 'bg-gray-200 text-gray-900'"
                >
                  <p class="text-sm">{{ mensagem.body }}</p>
                  <span class="text-xs opacity-75 mt-1 block">
                    {{ formatarHora(mensagem.created_at) }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Input de Mensagem -->
            <div class="p-4 border-t">
              <form @submit.prevent="enviarMensagem" class="flex space-x-2">
                <input
                  v-model="novaMensagem"
                  type="text"
                  placeholder="Digite sua mensagem..."
                  class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
                <button
                  type="submit"
                  :disabled="!novaMensagem.trim()"
                  class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  Enviar
                </button>
              </form>
            </div>
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { useConversasStore } from '../stores/conversas'
import Navbar from '../components/Navbar.vue'

const conversasStore = useConversasStore()
const novaMensagem = ref('')
const mensagensContainer = ref(null)

const conversas = computed(() => conversasStore.conversas)
const conversaAtiva = computed(() => conversasStore.conversaAtiva)
const mensagens = computed(() => conversasStore.mensagens)

onMounted(() => {
  conversasStore.fetchConversas()
  
  // Atualizar conversas a cada 10 segundos
  setInterval(() => {
    conversasStore.fetchConversas()
    if (conversaAtiva.value) {
      conversasStore.selecionarConversa(conversaAtiva.value.id)
    }
  }, 10000)
})

const selecionarConversa = async (id) => {
  await conversasStore.selecionarConversa(id)
  await nextTick()
  scrollToBottom()
}

const enviarMensagem = async () => {
  if (!novaMensagem.value.trim()) return
  
  const success = await conversasStore.enviarMensagem(
    conversaAtiva.value.id,
    novaMensagem.value
  )
  
  if (success) {
    novaMensagem.value = ''
    await nextTick()
    scrollToBottom()
  }
}

const scrollToBottom = () => {
  if (mensagensContainer.value) {
    mensagensContainer.value.scrollTop = mensagensContainer.value.scrollHeight
  }
}

const formatarHora = (data) => {
  return new Date(data).toLocaleTimeString('pt-BR', { 
    hour: '2-digit', 
    minute: '2-digit' 
  })
}
</script>
