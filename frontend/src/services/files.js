import api from './api'

export const fileService = {
  async upload(campaignId, file) {
    const formData = new FormData()
    formData.append('file', file)

    const response = await api.post(`/files/upload/${campaignId}`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })

    return response.data
  },

  async preview(campaignId) {
    const response = await api.get(`/files/preview/${campaignId}`)
    return response.data
  }
}
