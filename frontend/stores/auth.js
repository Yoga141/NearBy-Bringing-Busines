import { defineStore } from 'pinia'
import api from '@/services/api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('nearby_user') || 'null'),
    token: localStorage.getItem('nearby_token') || null,
  }),
  getters: {
    isLoggedIn: (s) => !!s.token,
    isUmkmOwner: (s) => s.user?.role === 'umkm_owner',
  },
  actions: {
    async login(email, password) {
      const { data } = await api.post('/api/auth/login.php', { email, password })
      this.setSession(data.token, data.user)
    },
    async register(payload) {
      const { data } = await api.post('/api/auth/register.php', payload)
      this.setSession(data.token, data.user)
    },
    setSession(token, user) {
      this.token = token
      this.user = user
      localStorage.setItem('nearby_token', token)
      localStorage.setItem('nearby_user', JSON.stringify(user))
    },
    logout() {
      api.post('/api/auth/logout.php').catch(() => {})
      this.token = null
      this.user = null
      localStorage.removeItem('nearby_token')
      localStorage.removeItem('nearby_user')
    },
  },
})
