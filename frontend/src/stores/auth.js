import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authService } from '@/services/auth'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('token') || null)
  const user = ref(null)

  const isAuthenticated = computed(() => !!token.value)

  async function login(email, password) {
    try {
      const data = await authService.login(email, password)
      token.value = data.token
      localStorage.setItem('token', data.token)
      await fetchUser()
      return true
    } catch (error) {
      console.error('Login error:', error)
      throw error
    }
  }

  async function register(name, email, password) {
    try {
      await authService.register(name, email, password)
      return await login(email, password)
    } catch (error) {
      console.error('Register error:', error)
      throw error
    }
  }

  async function fetchUser() {
    try {
      user.value = await authService.me()
    } catch (error) {
      console.error('Fetch user error:', error)
      logout()
    }
  }

  function logout() {
    token.value = null
    user.value = null
    localStorage.removeItem('token')
  }

  // Initialize user if token exists
  if (token.value) {
    fetchUser()
  }

  return {
    token,
    user,
    isAuthenticated,
    login,
    register,
    logout,
    fetchUser
  }
})
