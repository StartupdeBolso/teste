<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
        <router-link to="/campaigns/create" class="btn btn-primary">
          Nova Campanha
        </router-link>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="card">
          <h3 class="text-sm font-medium text-gray-500">Total de Campanhas</h3>
          <p class="mt-2 text-3xl font-bold text-gray-900">{{ stats.total }}</p>
        </div>
        <div class="card">
          <h3 class="text-sm font-medium text-gray-500">Ativas</h3>
          <p class="mt-2 text-3xl font-bold text-green-600">{{ stats.active }}</p>
        </div>
        <div class="card">
          <h3 class="text-sm font-medium text-gray-500">Concluídas</h3>
          <p class="mt-2 text-3xl font-bold text-blue-600">{{ stats.completed }}</p>
        </div>
        <div class="card">
          <h3 class="text-sm font-medium text-gray-500">Rascunhos</h3>
          <p class="mt-2 text-3xl font-bold text-gray-600">{{ stats.draft }}</p>
        </div>
      </div>

      <div class="card">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Campanhas Recentes</h2>
        <div v-if="loading" class="text-center py-8">
          <p class="text-gray-500">Carregando...</p>
        </div>
        <div v-else-if="recentCampaigns.length === 0" class="text-center py-8">
          <p class="text-gray-500">Nenhuma campanha criada ainda.</p>
          <router-link to="/campaigns/create" class="btn btn-primary mt-4">
            Criar primeira campanha
          </router-link>
        </div>
        <div v-else class="space-y-4">
          <div
            v-for="campaign in recentCampaigns"
            :key="campaign.id"
            class="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition-colors"
          >
            <div class="flex justify-between items-start">
              <div class="flex-1">
                <div class="flex items-center space-x-3">
                  <h3 class="text-lg font-semibold text-gray-900">{{ campaign.name }}</h3>
                  <span :class="`badge badge-${campaign.status}`">{{ statusText(campaign.status) }}</span>
                </div>
                <p class="text-sm text-gray-500 mt-1">{{ campaign.description }}</p>
                <div class="mt-2 flex space-x-4 text-sm text-gray-600">
                  <span>{{ campaign.dispatchedCount }} / {{ campaign.dispatchLimit }} enviados</span>
                  <span>{{ formatDate(campaign.createdAt) }}</span>
                </div>
              </div>
              <router-link
                :to="`/campaigns/${campaign.id}`"
                class="btn btn-secondary text-sm ml-4"
              >
                Ver detalhes
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import AppLayout from '@/components/AppLayout.vue'
import { useCampaignsStore } from '@/stores/campaigns'

const campaignsStore = useCampaignsStore()
const loading = ref(false)

const recentCampaigns = computed(() => campaignsStore.campaigns.slice(0, 5))

const stats = computed(() => {
  const campaigns = campaignsStore.campaigns
  return {
    total: campaigns.length,
    active: campaigns.filter(c => c.status === 'active').length,
    completed: campaigns.filter(c => c.status === 'completed').length,
    draft: campaigns.filter(c => c.status === 'draft').length
  }
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

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('pt-BR')
}

onMounted(async () => {
  loading.value = true
  try {
    await campaignsStore.fetchCampaigns()
  } finally {
    loading.value = false
  }
})
</script>
