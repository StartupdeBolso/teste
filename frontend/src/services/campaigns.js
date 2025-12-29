import api from './api'

export const campaignService = {
  async list() {
    const response = await api.get('/campaigns')
    return response.data
  },

  async get(id) {
    const response = await api.get(`/campaigns/${id}`)
    return response.data
  },

  async create(data) {
    const response = await api.post('/campaigns', data)
    return response.data
  },

  async update(id, data) {
    const response = await api.put(`/campaigns/${id}`, data)
    return response.data
  },

  async delete(id) {
    const response = await api.delete(`/campaigns/${id}`)
    return response.data
  },

  async start(id) {
    const response = await api.post(`/campaigns/${id}/start`)
    return response.data
  },

  async pause(id) {
    const response = await api.post(`/campaigns/${id}/pause`)
    return response.data
  },

  async resume(id) {
    const response = await api.post(`/campaigns/${id}/resume`)
    return response.data
  }
}
