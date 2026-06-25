import axios from 'axios';

const API_URL = 'http://127.0.0.1:8000/api';

export const SavedFilterService = {
    async getAll(token) {
        const response = await axios.get(`${API_URL}/filters`, {
            headers: { Authorization: `Bearer ${token}` }
        });
        return response.data;
    },

    async save(token, name, filters) {
        const response = await axios.post(`${API_URL}/filters`, {
            name,
            filters
        }, {
            headers: { Authorization: `Bearer ${token}` }
        });
        return response.data;
    },

    async update(token, id, name, filters) {
        const response = await axios.put(`${API_URL}/filters/${id}`, {
            name,
            filters
        }, {
            headers: { Authorization: `Bearer ${token}` }
        });
        return response.data;
    },

    async delete(token, id) {
        const response = await axios.delete(`${API_URL}/filters/${id}`, {
            headers: { Authorization: `Bearer ${token}` }
        });
        return response.data;
    }
};
