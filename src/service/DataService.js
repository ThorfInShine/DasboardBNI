import axios from 'axios';

const API_BASE_URL = '/api';

export const DataService = {
    async getAllData(token, page = 1, perPage = 10, search = '', filters = {}) {
        try {
            const params = new URLSearchParams({
                page,
                per_page: perPage,
                search,
                ...filters
            });

            const response = await axios.get(`${API_BASE_URL}/data?${params}`, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            return response.data;
        } catch (error) {
            console.error('Get all data error:', error);
            throw error;
        }
    },

    async getDataById(token, id) {
        try {
            const response = await axios.get(`${API_BASE_URL}/data/${id}`, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            return response.data;
        } catch (error) {
            console.error('Get data by id error:', error);
            throw error;
        }
    },

    async createData(token, data) {
        try {
            const response = await axios.post(`${API_BASE_URL}/data`, data, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            return response.data;
        } catch (error) {
            console.error('Create data error:', error);
            throw error;
        }
    },

    async updateData(token, id, data) {
        try {
            const response = await axios.put(`${API_BASE_URL}/data/${id}`, data, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            return response.data;
        } catch (error) {
            console.error('Update data error:', error);
            throw error;
        }
    },

    async deleteData(token, id) {
        try {
            const response = await axios.delete(`${API_BASE_URL}/data/${id}`, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            return response.data;
        } catch (error) {
            console.error('Delete data error:', error);
            throw error;
        }
    },

    async batchDelete(token, ids) {
        try {
            const response = await axios.post(`${API_BASE_URL}/data/batch-delete`, { ids }, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            return response.data;
        } catch (error) {
            console.error('Batch delete error:', error);
            throw error;
        }
    },

    async exportData(token, search = '') {
        try {
            const params = new URLSearchParams();
            if (search) params.append('search', search);

            const response = await axios.get(`${API_BASE_URL}/data/export?${params}`, {
                headers: {
                    'Authorization': `Bearer ${token}`
                },
                responseType: 'blob'
            });

            // Create download link
            const url = window.URL.createObjectURL(new Blob([response.data]));
            const link = document.createElement('a');
            link.href = url;

            // Extract filename from Content-Disposition header or use default
            const contentDisposition = response.headers['content-disposition'];
            let filename = `data_management_export_${new Date().toISOString().slice(0, 10)}.csv`;
            if (contentDisposition) {
                const match = contentDisposition.match(/filename="([^"]+)"/);
                if (match) filename = match[1];
            }

            link.setAttribute('download', filename);
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(url);

            return true;
        } catch (error) {
            console.error('Export data error:', error);
            throw error;
        }
    }
};
