import axios from 'axios';

const API_URL = import.meta.env.VITE_API_URL || '/api';

const api = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

// Add auth token to requests
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Handle response errors
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token');
      localStorage.removeItem('user');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export default {
  // Auth
  async register(name, email, password) {
    const response = await api.post('/auth/register', { name, email, password });
    return response.data;
  },

  async login(email, password) {
    const response = await api.post('/auth/login', { email, password });
    return response.data;
  },

  async web3Login(walletAddress, signature, message) {
    const response = await api.post('/auth/web3-login', {
      wallet_address: walletAddress,
      signature,
      message
    });
    return response.data;
  },

  async logout() {
    await api.post('/auth/logout');
  },

  async getMe() {
    const response = await api.get('/auth/me');
    return response.data;
  },

  async updateProfile(data) {
    const response = await api.put('/auth/profile', data);
    return response.data;
  },

  async updatePassword(password, passwordConfirmation) {
    const response = await api.put('/auth/password', {
      password,
      password_confirmation: passwordConfirmation
    });
    return response.data;
  },

  async uploadAvatar(file) {
    const formData = new FormData();
    formData.append('avatar', file);
    const response = await api.post('/auth/avatar', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
    return response.data;
  },

  // Vehicles
  async getVehicles() {
    const response = await api.get('/vehicles');
    return response.data;
  },

  async getVehicle(id) {
    const response = await api.get(`/vehicles/${id}`);
    return response.data;
  },

  async createVehicle(data, web3 = {}) {
    const response = await api.post('/vehicles', { ...data, ...web3 });
    return response.data;
  },

  async deleteVehicle(id) {
    const response = await api.delete(`/vehicles/${id}`);
    return response.data;
  },

  async certifyVehicle(id, web3 = {}, force = false) {
    const response = await api.post(`/vehicles/${id}/certify`, { ...web3, force: Boolean(force) });
    return response.data;
  },

  async updateMileage(id, mileage, web3 = {}) {
    const response = await api.put(`/vehicles/${id}/mileage`, { new_mileage: mileage, ...web3 });
    return response.data;
  },

  async assignDriver(id, driverId) {
    const response = await api.post(`/vehicles/${id}/assign-driver`, { driver_id: driverId });
    return response.data;
  },

  async getVehicleTimeline(id) {
    const response = await api.get(`/vehicles/${id}/timeline`);
    return response.data;
  },

  async verifyDocument(vehicleId, docId) {
    const response = await api.get(`/vehicles/${vehicleId}/documents/${docId}/verify`);
    return response.data;
  },

  // Maintenance
  async getMaintenance(vehicleId) {
    const response = await api.get(`/vehicles/${vehicleId}/maintenance`);
    return response.data;
  },

  async createMaintenance(vehicleId, data, web3 = {}) {
    const response = await api.post(`/vehicles/${vehicleId}/maintenance`, { ...data, ...web3 });
    return response.data;
  },

  async certifyMaintenance(id, web3 = {}) {
    const response = await api.post(`/maintenance/${id}/certify`, web3);
    return response.data;
  },

  // Drivers
  async getDrivers() {
    const response = await api.get('/drivers');
    return response.data;
  },

  async createDriver(data) {
    const response = await api.post('/drivers', data);
    return response.data;
  },

  async assignDriverWallet(id, walletAddress) {
    const response = await api.post(`/drivers/${id}/wallet`, { wallet_address: walletAddress });
    return response.data;
  },

  // Documents
  async getDocuments(vehicleId) {
    const response = await api.get(`/vehicles/${vehicleId}/documents`);
    return response.data;
  },

  async uploadDocument(vehicleId, formData) {
    const response = await api.post(`/vehicles/${vehicleId}/documents`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });
    return response.data;
  },

  async deleteDocument(id) {
    const response = await api.delete(`/documents/${id}`);
    return response.data;
  },

  async downloadDocument(id) {
    const response = await api.get(`/documents/${id}/download`, { responseType: 'blob' });
    return response.data;
  },

  async certifyDocument(id, web3 = {}) {
    const response = await api.post(`/documents/${id}/certify`, web3);
    return response.data;
  },

  // Alerts
  async getAlerts() {
    const response = await api.get('/alerts');
    return response.data;
  },

  async dismissAlert(id) {
    const response = await api.post(`/alerts/${id}/dismiss`);
    return response.data;
  },

  async getAlertStats() {
    const response = await api.get('/alerts/stats');
    return response.data;
  },

  // Blockchain Admin
  async authorizeGarage(address, status, web3 = {}) {
    const response = await api.post('/blockchain/authorize-garage', { address, status, ...web3 });
    return response.data;
  },

  async syncAll(web3 = {}) {
    const response = await api.post('/blockchain/sync', web3, { timeout: 180000 });
    return response.data;
  },

  async getBlockchainTransactions() {
    const response = await api.get('/blockchain/transactions');
    return response.data;
  },

  async getBlockchainStats() {
    const response = await api.get('/blockchain/stats');
    return response.data;
  },

  async getBlockchainStatus() {
    const response = await api.get('/blockchain/status');
    return response.data;
  },

  // Admin users
  async getAdminUsers() {
    const response = await api.get('/admin/users');
    return response.data;
  },

  async createAdminUser(data) {
    const response = await api.post('/admin/users', data);
    return response.data;
  },

  async updateAdminUser(id, data) {
    const response = await api.put(`/admin/users/${id}`, data);
    return response.data;
  },

  async deleteAdminUser(id) {
    const response = await api.delete(`/admin/users/${id}`);
    return response.data;
  },

  // Audit
  async verifyVehicle(query) {
    const response = await api.get('/audit/verify', { params: { q: query } });
    return response.data;
  }
};
