<template>
  <AppLayout>
    <div v-if="loading && !campaign" class="text-center py-12">
      <p class="text-gray-500">Carregando campanha...</p>
    </div>

    <div v-else-if="campaign" class="space-y-6">
      <div class="flex justify-between items-start">
        <div>
          <div class="flex items-center space-x-3">
            <h1 class="text-3xl font-bold text-gray-900">{{ campaign.name }}</h1>
            <span :class="`badge badge-${campaign.status}`">{{ statusText(campaign.status) }}</span>
          </div>
          <p class="mt-2 text-gray-600">{{ campaign.description }}</p>
        </div>
        <router-link to="/campaigns" class="btn btn-secondary">
          Voltar
        </router-link>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="card">
          <h3 class="text-sm font-medium text-gray-500">Total de Contatos</h3>
          <p class="mt-2 text-3xl font-bold text-gray-900">{{ campaign.totalContacts }}</p>
        </div>
        <div class="card">
          <h3 class="text-sm font-medium text-gray-500">Limite de Disparos</h3>
          <p class="mt-2 text-3xl font-bold text-blue-600">{{ campaign.dispatchLimit }}</p>
        </div>
        <div class="card">
          <h3 class="text-sm font-medium text-gray-500">Disparados</h3>
          <p class="mt-2 text-3xl font-bold text-green-600">{{ campaign.dispatchedCount }}</p>
        </div>
        <div class="card">
          <h3 class="text-sm font-medium text-gray-500">Progresso</h3>
          <p class="mt-2 text-3xl font-bold text-purple-600">{{ progressPercentage }}%</p>
        </div>
      </div>

      <div v-if="stats" class="card">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Estatísticas de Disparos</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div>
            <p class="text-sm text-gray-500">Pendentes</p>
            <p class="text-2xl font-bold text-gray-600">{{ stats.pending }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Processando</p>
            <p class="text-2xl font-bold text-yellow-600">{{ stats.processing }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Enviados</p>
            <p class="text-2xl font-bold text-green-600">{{ stats.sent }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Falhas</p>
            <p class="text-2xl font-bold text-red-600">{{ stats.failed }}</p>
          </div>
        </div>

        <div class="mt-6">
          <div class="flex justify-between text-sm text-gray-600 mb-2">
            <span>Progresso dos disparos</span>
            <span>{{ stats.progress_percentage }}%</span>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-3">
            <div
              class="bg-blue-600 h-3 rounded-full transition-all duration-300"
              :style="{ width: `${stats.progress_percentage}%` }"
            ></div>
          </div>
        </div>
      </div>

      <div class="card">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Ações</h2>
        <div class="flex flex-wrap gap-4">
          <button
            v-if="campaign.status === 'draft'"
            @click="handleStart"
            :disabled="actionLoading"
            class="btn btn-success"
          >
            {{ actionLoading ? 'Iniciando...' : 'Iniciar Campanha' }}
          </button>

          <button
            v-if="campaign.status === 'active'"
            @click="handlePause"
            :disabled="actionLoading"
            class="btn btn-secondary"
          >
            {{ actionLoading ? 'Pausando...' : 'Pausar Campanha' }}
          </button>

          <button
            v-if="campaign.status === 'paused'"
            @click="handleResume"
            :disabled="actionLoading"
            class="btn btn-success"
          >
            {{ actionLoading ? 'Retomando...' : 'Retomar Campanha' }}
          </button>

          <button
            v-if="campaign.status === 'draft'"
            @click="handleDelete"
            :disabled="actionLoading"
            class="btn btn-danger"
          >
            {{ actionLoading ? 'Deletando...' : 'Deletar Campanha' }}
          </button>

          <button
            @click="refreshData"
            :disabled="refreshing"
            class="btn btn-secondary"
          >
            {{ refreshing ? 'Atualizando...' : 'Atualizar Dados' }}
          </button>
        </div>

        <div v-if="actionError" class="mt-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
          {{ actionError }}
        </div>
        <div v-if="actionSuccess" class="mt-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
          {{ actionSuccess }}
        </div>
      </div>

      <div class="card">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Informações</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
          <div>
            <p class="text-gray-500">ID da Planilha</p>
            <p class="font-mono text-gray-900">{{ campaign.googleSheetId }}</p>
          </div>
          <div>
            <p class="text-gray-500">Nome da Aba</p>
            <p class="font-medium text-gray-900">{{ campaign.sheetName }}</p>
          </div>
          <div>
            <p class="text-gray-500">Criada em</p>
            <p class="font-medium text-gray-900">{{ formatDateTime(campaign.createdAt) }}</p>
          </div>
          <div v-if="campaign.startedAt">
            <p class="text-gray-500">Iniciada em</p>
            <p class="font-medium text-gray-900">{{ formatDateTime(campaign.startedAt) }}</p>
          </div>
          <div v-if="campaign.completedAt">
            <p class="text-gray-500">Concluída em</p>
            <p class="font-medium text-gray-900">{{ formatDateTime(campaign.completedAt) }}</p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppLayout from '@/components/AppLayout.vue'
import { useCampaignsStore } from '@/stores/campaigns'

const route = useRoute()
const router = useRouter()
const campaignsStore = useCampaignsStore()

const loading = ref(false)
const actionLoading = ref(false)
const refreshing = ref(false)
const actionError = ref('')
const actionSuccess = ref('')
const stats = ref(null)

const campaign = computed(() => campaignsStore.currentCampaign)

const progressPercentage = computed(() => {
  if (!campaign.value) return 0
  return Math.round((campaign.value.dispatchedCount / campaign.value.dispatchLimit) * 100)
})

const statusText = (status) => {
  const map = {
    draft: 'Rascunho',
    active: 'Ativa',
    paused: 'Pausada',
    completed: 'Concluída',
    failed: 'Falhou'
  }
  return map[status] || status
}

const formatDateTime = (date) => {
  return new Date(date).toLocaleString('pt-BR')
}

const loadCampaign = async () => {
  loading.value = true
  try {
    const data = await campaignsStore.fetchCampaign(route.params.id)
    stats.value = data.stats
  } catch (error) {
    console.error('Error loading campaign:', error)
  } finally {
    loading.value = false
  }
}

const refreshData = async () => {
  refreshing.value = true
  try {
    await loadCampaign()
    actionSuccess.value = 'Dados atualizados!'
    setTimeout(() => {
      actionSuccess.value = ''
    }, 3000)
  } finally {
    refreshing.value = false
  }
}

const handleStart = async () => {
  if (!confirm('Deseja iniciar esta campanha? Os disparos começarão a ser processados.')) return

  actionLoading.value = true
  actionError.value = ''
  actionSuccess.value = ''

  try {
    await campaignsStore.startCampaign(route.params.id)
    actionSuccess.value = 'Campanha iniciada com sucesso!'
    setTimeout(() => {
      actionSuccess.value = ''
    }, 3000)
  } catch (error) {
    actionError.value = error.response?.data?.error || 'Erro ao iniciar campanha'
  } finally {
    actionLoading.value = false
  }
}

const handlePause = async () => {
  actionLoading.value = true
  actionError.value = ''
  actionSuccess.value = ''

  try {
    await campaignsStore.pauseCampaign(route.params.id)
    actionSuccess.value = 'Campanha pausada!'
    setTimeout(() => {
      actionSuccess.value = ''
    }, 3000)
  } catch (error) {
    actionError.value = error.response?.data?.error || 'Erro ao pausar campanha'
  } finally {
    actionLoading.value = false
  }
}

const handleResume = async () => {
  actionLoading.value = true
  actionError.value = ''
  actionSuccess.value = ''

  try {
    await campaignsStore.resumeCampaign(route.params.id)
    actionSuccess.value = 'Campanha retomada!'
    setTimeout(() => {
      actionSuccess.value = ''
    }, 3000)
  } catch (error) {
    actionError.value = error.response?.data?.error || 'Erro ao retomar campanha'
  } finally {
    actionLoading.value = false
  }
}

const handleDelete = async () => {
  if (!confirm('Deseja realmente deletar esta campanha? Esta ação não pode ser desfeita.')) return

  actionLoading.value = true
  actionError.value = ''

  try {
    await campaignsStore.deleteCampaign(route.params.id)
    router.push('/campaigns')
  } catch (error) {
    actionError.value = error.response?.data?.error || 'Erro ao deletar campanha'
  } finally {
    actionLoading.value = false
  }
}

let refreshInterval = null

onMounted(() => {
  loadCampaign()

  // Auto-refresh every 10 seconds if campaign is active
  refreshInterval = setInterval(() => {
    if (campaign.value?.status === 'active') {
      loadCampaign()
    }
  }, 10000)
})

onUnmounted(() => {
  if (refreshInterval) {
    clearInterval(refreshInterval)
  }
})
</script>
