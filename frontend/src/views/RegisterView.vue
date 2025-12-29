<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          N8N Dispatch SaaS
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
          Crie sua conta
        </p>
      </div>
      <form class="mt-8 space-y-6" @submit.prevent="handleRegister">
        <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
          {{ error }}
        </div>

        <div class="rounded-md shadow-sm space-y-4">
          <div>
            <label for="name" class="sr-only">Nome</label>
            <input
              id="name"
              v-model="name"
              type="text"
              required
              class="input"
              placeholder="Nome completo"
            />
          </div>
          <div>
            <label for="email" class="sr-only">Email</label>
            <input
              id="email"
              v-model="email"
              type="email"
              required
              class="input"
              placeholder="Email"
            />
          </div>
          <div>
            <label for="password" class="sr-only">Senha</label>
            <input
              id="password"
              v-model="password"
              type="password"
              required
              class="input"
              placeholder="Senha (mínimo 6 caracteres)"
              minlength="6"
            />
          </div>
        </div>

        <div>
          <button
            type="submit"
            :disabled="loading"
            class="w-full btn btn-primary"
          >
            {{ loading ? 'Registrando...' : 'Registrar' }}
          </button>
        </div>

        <div class="text-center">
          <router-link to="/login" class="text-sm text-blue-600 hover:text-blue-500">
            Já tem uma conta? Entre
          </router-link>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const name = ref('')
const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref('')

const handleRegister = async () => {
  loading.value = true
  error.value = ''

  try {
    await authStore.register(name.value, email.value, password.value)
    router.push('/')
  } catch (err) {
    error.value = err.response?.data?.error || 'Erro ao registrar'
  } finally {
    loading.value = false
  }
}
</script>
