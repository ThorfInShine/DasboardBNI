import axios from 'axios';

const API_BASE_URL = '/api';

export const UserService = {
    async getAllUsers(token) {
        try {
            const response = await axios.get(`${API_BASE_URL}/users`, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            return response.data;
        } catch (error) {
            console.error('Get all users error:', error);
            throw error;
        }
    },

    async createUser(token, userData) {
        try {
            const response = await axios.post(`${API_BASE_URL}/users`, userData, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            return response.data;
        } catch (error) {
            console.error('Create user error:', error);
            throw error;
        }
    },

    async deleteUser(token, userId) {
        try {
            const response = await axios.delete(`${API_BASE_URL}/users/${userId}`, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            return response.data;
        } catch (error) {
            console.error('Delete user error:', error);
            throw error;
        }
    },

    async resetPassword(token, userId, newPassword) {
        try {
            const response = await axios.post(`${API_BASE_URL}/users/${userId}/reset-password`, {
                new_password: newPassword
            }, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            return response.data;
        } catch (error) {
            console.error('Reset password error:', error);
            throw error;
        }
    }
};
