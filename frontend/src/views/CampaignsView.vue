<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900">Campanhas</h1>
        <router-link to="/campaigns/create" class="btn btn-primary">
          Nova Campanha
        </router-link>
      </div>

      <div v-if="loading" class="text-center py-12">
        <p class="text-gray-500">Carregando campanhas...</p>
      </div>

      <div v-else-if="campaigns.length === 0" class="card text-center py-12">
        <p class="text-gray-500 mb-4">Nenhuma campanha criada ainda.</p>
        <router-link to="/campaigns/create" class="btn btn-primary">
          Criar primeira campanha
        </router-link>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="campaign in campaigns"
          :key="campaign.id"
          class="card hover:shadow-lg transition-shadow"
        >
          <div class="flex justify-between items-start mb-3">
            <h3 class="text-lg font-semibold text-gray-900">{{ campaign.name }}</h3>
            <span :class="`badge badge-${campaign.status}`">{{ statusText(campaign.status) }}</span>
          </div>

          <p class="text-sm text-gray-500 mb-4">{{ campaign.description || 'Sem descrição' }}</p>

          <div class="space-y-2 mb-4">
            <div class="flex justify-between text-sm">
              <span class="text-gray-600">Progresso:</span>
              <span class="font-medium">{{ campaign.dispatchedCount }} / {{ campaign.dispatchLimit }}</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
              <div
                class="bg-blue-600 h-2 rounded-full"
                :style="{ width: `${(campaign.dispatchedCount / campaign.dispatchLimit) * 100}%` }"
              ></div>
            </div>
          </div>

          <div class="text-xs text-gray-500 mb-4">
            Criada em {{ formatDate(campaign.createdAt) }}
          </div>

          <router-link
            :to="`/campaigns/${campaign.id}`"
            class="btn btn-primary w-full"
          >
            Ver detalhes
          </router-link>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import AppLayout from '@/components/AppLayout.vue'
import { useCampaignsStore } from '@/stores/campaigns'

const campaignsStore = useCampaignsStore()

const campaigns = computed(() => campaignsStore.campaigns)
const loading = computed(() => campaignsStore.loading)

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

onMounted(() => {
  campaignsStore.fetchCampaigns()
})
</script>
