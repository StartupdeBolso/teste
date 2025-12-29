<template>
  <AppLayout>
    <div class="max-w-3xl mx-auto space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Nova Campanha</h1>
        <p class="mt-2 text-gray-600">Configure sua campanha de disparos</p>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-6">
        <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
          {{ error }}
        </div>

        <div v-if="success" class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
          {{ success }}
        </div>

        <div class="card space-y-6">
          <h2 class="text-xl font-semibold text-gray-900">Informações Básicas</h2>

          <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
              Nome da Campanha
            </label>
            <input
              id="name"
              v-model="form.name"
              type="text"
              required
              class="input"
              placeholder="Ex: Campanha Black Friday 2024"
            />
          </div>

          <div>
            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
              Descrição (opcional)
            </label>
            <textarea
              id="description"
              v-model="form.description"
              rows="3"
              class="input"
              placeholder="Descreva o objetivo desta campanha..."
            ></textarea>
          </div>
        </div>

        <div class="card space-y-6">
          <h2 class="text-xl font-semibold text-gray-900">Importar Contatos</h2>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Arquivo de Contatos
            </label>
            <div class="flex items-center space-x-4">
              <label class="flex-1 flex flex-col items-center px-4 py-6 bg-white rounded-lg border-2 border-dashed border-gray-300 cursor-pointer hover:border-blue-400 transition-colors">
                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
                <span class="mt-2 text-sm text-gray-600">
                  {{ selectedFile ? selectedFile.name : 'Clique para selecionar um arquivo' }}
                </span>
                <span class="mt-1 text-xs text-gray-500">CSV, XLS ou XLSX</span>
                <input
                  type="file"
                  ref="fileInput"
                  @change="handleFileSelect"
                  accept=".csv,.xlsx,.xls"
                  class="hidden"
                />
              </label>
            </div>
            <p class="mt-2 text-sm text-gray-500">
              A primeira linha do arquivo deve conter os nomes das colunas (cabeçalhos)
            </p>
          </div>

          <div v-if="preview">
            <h3 class="text-sm font-medium text-gray-700 mb-2">Preview dos Dados</h3>
            <div class="bg-gray-50 rounded-lg p-4 overflow-x-auto">
              <p class="text-sm text-gray-600 mb-2">
                Total de contatos: <strong>{{ preview.totalContacts }}</strong>
              </p>
              <div v-if="preview.headers && preview.headers.length > 0">
                <p class="text-xs text-gray-500 mb-2">Colunas encontradas:</p>
                <div class="flex flex-wrap gap-2 mb-4">
                  <span
                    v-for="header in preview.headers"
                    :key="header"
                    class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded"
                  >
                    {{ header }}
                  </span>
                </div>
              </div>
              <table v-if="preview.preview && preview.preview.length > 0" class="min-w-full text-sm">
                <thead>
                  <tr class="border-b border-gray-300">
                    <th
                      v-for="(value, key) in preview.preview[0]"
                      :key="key"
                      class="px-2 py-2 text-left font-medium text-gray-700"
                    >
                      {{ key }}
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="(contact, index) in preview.preview.slice(0, 3)"
                    :key="index"
                    class="border-b border-gray-200"
                  >
                    <td
                      v-for="(value, key) in contact"
                      :key="key"
                      class="px-2 py-2 text-gray-600"
                    >
                      {{ value }}
                    </td>
                  </tr>
                </tbody>
              </table>
              <p class="text-xs text-gray-500 mt-2">Mostrando apenas os primeiros 3 registros</p>
            </div>
          </div>
        </div>

        <div v-if="preview" class="card space-y-6">
          <h2 class="text-xl font-semibold text-gray-900">Configuração de Disparos</h2>

          <div>
            <label for="dispatchLimit" class="block text-sm font-medium text-gray-700 mb-2">
              Quantidade de Disparos
            </label>
            <input
              id="dispatchLimit"
              v-model.number="form.dispatchLimit"
              type="number"
              required
              min="1"
              :max="preview.totalContacts"
              class="input"
              placeholder="Ex: 100"
            />
            <p class="mt-1 text-sm text-gray-500">
              Máximo: {{ preview.totalContacts }} contatos
            </p>
          </div>
        </div>

        <div class="flex space-x-4">
          <button
            type="submit"
            :disabled="loading || !preview"
            class="btn btn-primary flex-1"
          >
            {{ loading ? 'Criando...' : 'Criar Campanha' }}
          </button>
          <router-link to="/campaigns" class="btn btn-secondary flex-1">
            Cancelar
          </router-link>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import AppLayout from '@/components/AppLayout.vue'
import { useCampaignsStore } from '@/stores/campaigns'
import { fileService } from '@/services/files'

const router = useRouter()
const campaignsStore = useCampaignsStore()

const form = reactive({
  name: '',
  description: '',
  dispatchLimit: null
})

const fileInput = ref(null)
const selectedFile = ref(null)
const loading = ref(false)
const uploadingFile = ref(false)
const error = ref('')
const success = ref('')
const preview = ref(null)
const createdCampaignId = ref(null)

const handleFileSelect = async (event) => {
  const file = event.target.files[0]
  if (!file) return

  selectedFile.value = file

  // First, create the campaign if not created yet
  if (!createdCampaignId.value) {
    if (!form.name) {
      error.value = 'Por favor, preencha o nome da campanha primeiro'
      selectedFile.value = null
      if (fileInput.value) {
        fileInput.value.value = ''
      }
      return
    }

    try {
      loading.value = true
      error.value = ''

      const campaign = await campaignsStore.createCampaign({
        name: form.name,
        description: form.description
      })

      createdCampaignId.value = campaign.id
    } catch (err) {
      error.value = err.response?.data?.error || 'Erro ao criar campanha'
      selectedFile.value = null
      if (fileInput.value) {
        fileInput.value.value = ''
      }
      loading.value = false
      return
    } finally {
      loading.value = false
    }
  }

  // Upload file
  uploadingFile.value = true
  error.value = ''
  success.value = ''

  try {
    const result = await fileService.upload(createdCampaignId.value, file)

    preview.value = result
    success.value = 'Arquivo importado com sucesso!'

    if (!form.dispatchLimit) {
      form.dispatchLimit = result.totalContacts
    }

    setTimeout(() => {
      success.value = ''
    }, 3000)
  } catch (err) {
    error.value = err.response?.data?.error || 'Erro ao fazer upload do arquivo'
    preview.value = null
    selectedFile.value = null
    if (fileInput.value) {
      fileInput.value.value = ''
    }
  } finally {
    uploadingFile.value = false
  }
}

const handleSubmit = async () => {
  if (!createdCampaignId.value) {
    error.value = 'Por favor, faça upload de um arquivo primeiro'
    return
  }

  loading.value = true
  error.value = ''

  try {
    // Update dispatch limit if changed
    if (form.dispatchLimit) {
      await campaignsStore.updateCampaign(createdCampaignId.value, {
        dispatchLimit: form.dispatchLimit
      })
    }

    router.push(`/campaigns/${createdCampaignId.value}`)
  } catch (err) {
    error.value = err.response?.data?.error || 'Erro ao finalizar campanha'
  } finally {
    loading.value = false
  }
}
</script>
