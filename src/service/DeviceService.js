import axios from 'axios';

const API_BASE_URL = '/api';

export const DeviceService = {
    getDevicesSmall() {
        return axios.get(`${API_BASE_URL}/data`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('user_token')}`
            }
        })
            .then(response => response.data.data || [])
            .catch(error => {
                console.error('Error fetching devices:', error);
                return [];
            });
    },
    getManufacturers() {
        return axios.get(`${API_BASE_URL}/dashboard/bar-chart`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('user_token')}`
            }
        })
            .then(response => {
                // Backend returns { labels: [...], values: [...], counts: [...] }
                const labels = response.data.labels || [];
                const counts = response.data.counts || [];
                const totalCount = counts.reduce((sum, count) => sum + count, 0);

                return labels.map((label, index) => ({
                    name: label,
                    count: counts[index],
                    percentage: totalCount > 0 ? ((counts[index] / totalCount) * 100).toFixed(1) : '0.0'
                })).slice(0, 6);
            })
            .catch(error => {
                console.error('Error fetching manufacturers:', error);
                return [];
            });
    }
};