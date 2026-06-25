import axios from 'axios';

const API_BASE_URL = '/api';

export const ImportService = {
    async importFile(token, file, fileType) {
        try {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('type', fileType);

            const response = await axios.post(`${API_BASE_URL}/import`, formData, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            return response.data;
        } catch (error) {
            console.error('Import file error:', error);
            throw error;
        }
    },

    async getImportHistory(token) {
        try {
            const response = await axios.get(`${API_BASE_URL}/import/history`, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            return response.data;
        } catch (error) {
            console.error('Get import history error:', error);
            throw error;
        }
    },

    async downloadTemplate(token, fileType) {
        try {
            const response = await axios.get(`${API_BASE_URL}/import/template/${fileType}`, {
                headers: {
                    'Authorization': `Bearer ${token}`
                },
                responseType: 'blob'
            });
            return response.data;
        } catch (error) {
            console.error('Download template error:', error);
            throw error;
        }
    }
};
