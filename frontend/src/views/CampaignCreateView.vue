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
          <h2 class="text-xl font-semibold text-gray-900">Google Sheets</h2>

          <div>
            <label for="sheetId" class="block text-sm font-medium text-gray-700 mb-2">
              ID da Planilha do Google Sheets
            </label>
            <input
              id="sheetId"
              v-model="form.googleSheetId"
              type="text"
              required
              class="input"
              placeholder="1AbC2dEfG3hIjK4lMnO5pQr6sTu7vWxY8zA"
              @blur="loadSheetInfo"
            />
            <p class="mt-1 text-sm text-gray-500">
              Encontre o ID na URL da planilha: docs.google.com/spreadsheets/d/<strong>ID_AQUI</strong>/edit
            </p>
          </div>

          <div v-if="loadingSheet" class="text-center py-4">
            <p class="text-gray-500">Carregando informações da planilha...</p>
          </div>

          <div v-if="sheetInfo">
            <label for="sheetName" class="block text-sm font-medium text-gray-700 mb-2">
              Selecione a Aba
            </label>
            <select
              id="sheetName"
              v-model="form.sheetName"
              required
              class="input"
              @change="loadPreview"
            >
              <option value="">Selecione uma aba</option>
              <option v-for="sheet in sheetInfo.sheets" :key="sheet.id" :value="sheet.title">
                {{ sheet.title }}
              </option>
            </select>
          </div>

          <div v-if="preview">
            <h3 class="text-sm font-medium text-gray-700 mb-2">Preview dos Dados</h3>
            <div class="bg-gray-50 rounded-lg p-4 overflow-x-auto">
              <p class="text-sm text-gray-600 mb-2">
                Total de contatos: <strong>{{ preview.totalContacts }}</strong>
              </p>
              <table class="min-w-full text-sm">
                <thead>
                  <tr class="border-b border-gray-300">
                    <th
                      v-for="(value, key) in preview.contacts[0]"
                      :key="key"
                      class="px-2 py-2 text-left font-medium text-gray-700"
                    >
                      {{ key }}
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="(contact, index) in preview.contacts.slice(0, 3)"
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

        <div class="card space-y-6">
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
              :max="preview?.totalContacts"
              class="input"
              placeholder="Ex: 100"
            />
            <p v-if="preview" class="mt-1 text-sm text-gray-500">
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
import { googleSheetsService } from '@/services/googleSheets'

const router = useRouter()
const campaignsStore = useCampaignsStore()

const form = reactive({
  name: '',
  description: '',
  googleSheetId: '',
  sheetName: '',
  dispatchLimit: null
})

const loading = ref(false)
const loadingSheet = ref(false)
const error = ref('')
const sheetInfo = ref(null)
const preview = ref(null)

const loadSheetInfo = async () => {
  if (!form.googleSheetId) return

  loadingSheet.value = true
  error.value = ''

  try {
    sheetInfo.value = await googleSheetsService.getInfo(form.googleSheetId)
    if (sheetInfo.value.sheets.length > 0) {
      form.sheetName = sheetInfo.value.sheets[0].title
      await loadPreview()
    }
  } catch (err) {
    error.value = err.response?.data?.error || 'Erro ao carregar planilha. Verifique o ID e as permissões.'
    sheetInfo.value = null
    preview.value = null
  } finally {
    loadingSheet.value = false
  }
}

const loadPreview = async () => {
  if (!form.googleSheetId || !form.sheetName) return

  loadingSheet.value = true
  error.value = ''

  try {
    preview.value = await googleSheetsService.preview(form.googleSheetId, form.sheetName)
    if (!form.dispatchLimit) {
      form.dispatchLimit = preview.value.totalContacts
    }
  } catch (err) {
    error.value = err.response?.data?.error || 'Erro ao carregar preview'
    preview.value = null
  } finally {
    loadingSheet.value = false
  }
}

const handleSubmit = async () => {
  loading.value = true
  error.value = ''

  try {
    const campaign = await campaignsStore.createCampaign(form)
    router.push(`/campaigns/${campaign.id}`)
  } catch (err) {
    error.value = err.response?.data?.error || 'Erro ao criar campanha'
  } finally {
    loading.value = false
  }
}
</script>
