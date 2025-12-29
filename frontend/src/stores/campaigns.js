import { defineStore } from 'pinia'
import { ref } from 'vue'
import { campaignService } from '@/services/campaigns'

export const useCampaignsStore = defineStore('campaigns', () => {
  const campaigns = ref([])
  const currentCampaign = ref(null)
  const loading = ref(false)

  async function fetchCampaigns() {
    loading.value = true
    try {
      campaigns.value = await campaignService.list()
    } catch (error) {
      console.error('Error fetching campaigns:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  async function fetchCampaign(id) {
    loading.value = true
    try {
      currentCampaign.value = await campaignService.get(id)
      return currentCampaign.value
    } catch (error) {
      console.error('Error fetching campaign:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  async function createCampaign(data) {
    loading.value = true
    try {
      const campaign = await campaignService.create(data)
      campaigns.value.unshift(campaign)
      return campaign
    } catch (error) {
      console.error('Error creating campaign:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  async function updateCampaign(id, data) {
    loading.value = true
    try {
      const campaign = await campaignService.update(id, data)
      const index = campaigns.value.findIndex(c => c.id === id)
      if (index !== -1) {
        campaigns.value[index] = campaign
      }
      return campaign
    } catch (error) {
      console.error('Error updating campaign:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  async function deleteCampaign(id) {
    loading.value = true
    try {
      await campaignService.delete(id)
      campaigns.value = campaigns.value.filter(c => c.id !== id)
    } catch (error) {
      console.error('Error deleting campaign:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  async function startCampaign(id) {
    loading.value = true
    try {
      const result = await campaignService.start(id)
      await fetchCampaign(id)
      return result
    } catch (error) {
      console.error('Error starting campaign:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  async function pauseCampaign(id) {
    loading.value = true
    try {
      const result = await campaignService.pause(id)
      await fetchCampaign(id)
      return result
    } catch (error) {
      console.error('Error pausing campaign:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  async function resumeCampaign(id) {
    loading.value = true
    try {
      const result = await campaignService.resume(id)
      await fetchCampaign(id)
      return result
    } catch (error) {
      console.error('Error resuming campaign:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  return {
    campaigns,
    currentCampaign,
    loading,
    fetchCampaigns,
    fetchCampaign,
    createCampaign,
    updateCampaign,
    deleteCampaign,
    startCampaign,
    pauseCampaign,
    resumeCampaign
  }
})
