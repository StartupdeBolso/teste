import api from './api'

export const googleSheetsService = {
  async getInfo(spreadsheetId) {
    const response = await api.post('/google-sheets/info', { spreadsheetId })
    return response.data
  },

  async preview(spreadsheetId, sheetName) {
    const response = await api.post('/google-sheets/preview', {
      spreadsheetId,
      sheetName
    })
    return response.data
  }
}
