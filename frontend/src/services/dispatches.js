import api from './api'

export const dispatchService = {
  async getByCampaign(campaignId) {
    const response = await api.get(`/dispatches/campaign/${campaignId}`)
    return response.data
  },

  async process() {
    const response = await api.post('/dispatches/process')
    return response.data
  }
}
