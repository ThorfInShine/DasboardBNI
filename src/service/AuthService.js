import axios from 'axios';

const API_BASE_URL = '/api';

export const AuthService = {
    async login(npp, password) {
        try {
            const response = await axios.post(`${API_BASE_URL}/login`, {
                npp,
                password
            });
            return response.data;
        } catch (error) {
            console.error('Login error:', error);
            throw error;
        }
    },

    async register(name, email, password, password_confirmation) {
        try {
            const response = await axios.post(`${API_BASE_URL}/register`, {
                name,
                email,
                password,
                password_confirmation
            });
            return response.data;
        } catch (error) {
            console.error('Register error:', error);
            throw error;
        }
    },

    async logout(token) {
        try {
            const response = await axios.post(`${API_BASE_URL}/logout`, {}, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            return response.data;
        } catch (error) {
            console.error('Logout error:', error);
            throw error;
        }
    },

    async getProfile(token) {
        try {
            const response = await axios.get(`${API_BASE_URL}/profile`, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            return response.data;
        } catch (error) {
            console.error('Get profile error:', error);
            throw error;
        }
    },

    async updateProfile(token, data) {
        try {
            const response = await axios.put(`${API_BASE_URL}/profile`, data, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            return response.data;
        } catch (error) {
            console.error('Update profile error:', error);
            throw error;
        }
    },

    async changePassword(token, currentPassword, newPassword, newPasswordConfirmation) {
        try {
            const response = await axios.post(`${API_BASE_URL}/change-password`, {
                current_password: currentPassword,
                new_password: newPassword,
                new_password_confirmation: newPasswordConfirmation
            }, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            return response.data;
        } catch (error) {
            console.error('Change password error:', error);
            throw error;
        }
    }
};
