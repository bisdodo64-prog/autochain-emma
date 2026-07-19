import axios from 'axios';

const API_URL = import.meta.env.VITE_API_URL || '/api';

const state = {
  vehicles: [],
  currentVehicle: null,
  loading: false,
  error: null
};

const getters = {
  vehicles: state => state.vehicles,
  currentVehicle: state => state.currentVehicle,
  loading: state => state.loading,
  error: state => state.error,
  availableVehicles: state => state.vehicles.filter(v => v.status === 'available'),
  inMissionVehicles: state => state.vehicles.filter(v => v.status === 'in_mission'),
  maintenanceVehicles: state => state.vehicles.filter(v => v.status === 'maintenance')
};

const mutations = {
  SET_LOADING(state, loading) {
    state.loading = loading;
  },
  SET_VEHICLES(state, vehicles) {
    state.vehicles = vehicles;
  },
  SET_CURRENT_VEHICLE(state, vehicle) {
    state.currentVehicle = vehicle;
  },
  SET_ERROR(state, error) {
    state.error = error;
  },
  ADD_VEHICLE(state, vehicle) {
    state.vehicles.push(vehicle);
  },
  UPDATE_VEHICLE(state, updatedVehicle) {
    const index = state.vehicles.findIndex(v => v.id === updatedVehicle.id);
    if (index !== -1) {
      state.vehicles.splice(index, 1, updatedVehicle);
    }
  }
};

const actions = {
  async fetchVehicles({ commit }) {
    commit('SET_LOADING', true);
    try {
      const token = localStorage.getItem('auth_token');
      const response = await axios.get(`${API_URL}/vehicles`, {
        headers: { Authorization: `Bearer ${token}` }
      });
      commit('SET_VEHICLES', response.data);
      commit('SET_ERROR', null);
    } catch (error) {
      commit('SET_ERROR', error.response?.data?.message || 'Failed to fetch vehicles');
      console.error('Error fetching vehicles:', error);
    } finally {
      commit('SET_LOADING', false);
    }
  },

  async fetchVehicle({ commit }, id) {
    commit('SET_LOADING', true);
    try {
      const token = localStorage.getItem('auth_token');
      const response = await axios.get(`${API_URL}/vehicles/${id}`, {
        headers: { Authorization: `Bearer ${token}` }
      });
      commit('SET_CURRENT_VEHICLE', response.data);
      commit('SET_ERROR', null);
      return response.data;
    } catch (error) {
      commit('SET_ERROR', error.response?.data?.message || 'Failed to fetch vehicle');
      console.error('Error fetching vehicle:', error);
      throw error;
    } finally {
      commit('SET_LOADING', false);
    }
  },

  async createVehicle({ commit }, vehicleData) {
    commit('SET_LOADING', true);
    try {
      const token = localStorage.getItem('auth_token');
      const response = await axios.post(`${API_URL}/vehicles`, vehicleData, {
        headers: { Authorization: `Bearer ${token}` }
      });
      commit('ADD_VEHICLE', response.data.vehicle);
      commit('SET_ERROR', null);
      return response.data;
    } catch (error) {
      commit('SET_ERROR', error.response?.data?.message || 'Failed to create vehicle');
      console.error('Error creating vehicle:', error);
      throw error;
    } finally {
      commit('SET_LOADING', false);
    }
  },

  async updateMileage({ commit }, { id, mileage }) {
    commit('SET_LOADING', true);
    try {
      const token = localStorage.getItem('auth_token');
      const response = await axios.put(`${API_URL}/vehicles/${id}/mileage`, 
        { new_mileage: mileage },
        { headers: { Authorization: `Bearer ${token}` } }
      );
      commit('SET_ERROR', null);
      return response.data;
    } catch (error) {
      commit('SET_ERROR', error.response?.data?.message || 'Failed to update mileage');
      console.error('Error updating mileage:', error);
      throw error;
    } finally {
      commit('SET_LOADING', false);
    }
  },

  async assignDriver({ commit }, { id, driverId }) {
    commit('SET_LOADING', true);
    try {
      const token = localStorage.getItem('auth_token');
      const response = await axios.post(`${API_URL}/vehicles/${id}/assign-driver`, 
        { driver_id: driverId },
        { headers: { Authorization: `Bearer ${token}` } }
      );
      commit('UPDATE_VEHICLE', response.data.vehicle);
      commit('SET_ERROR', null);
      return response.data;
    } catch (error) {
      commit('SET_ERROR', error.response?.data?.message || 'Failed to assign driver');
      console.error('Error assigning driver:', error);
      throw error;
    } finally {
      commit('SET_LOADING', false);
    }
  },

  async getVehicleTimeline({ commit }, vehicleId) {
    commit('SET_LOADING', true);
    try {
      const token = localStorage.getItem('auth_token');
      const response = await axios.get(`${API_URL}/vehicles/${vehicleId}/timeline`, {
        headers: { Authorization: `Bearer ${token}` }
      });
      commit('SET_ERROR', null);
      return response.data;
    } catch (error) {
      commit('SET_ERROR', error.response?.data?.message || 'Failed to fetch timeline');
      console.error('Error fetching timeline:', error);
      throw error;
    } finally {
      commit('SET_LOADING', false);
    }
  },

  async verifyDocument({ commit }, { vehicleId, documentId }) {
    commit('SET_LOADING', true);
    try {
      const token = localStorage.getItem('auth_token');
      const response = await axios.get(
        `${API_URL}/vehicles/${vehicleId}/documents/${documentId}/verify`,
        { headers: { Authorization: `Bearer ${token}` } }
      );
      commit('SET_ERROR', null);
      return response.data;
    } catch (error) {
      commit('SET_ERROR', error.response?.data?.message || 'Failed to verify document');
      console.error('Error verifying document:', error);
      throw error;
    } finally {
      commit('SET_LOADING', false);
    }
  },

  async syncVehicle({ commit }, vehicleId) {
    commit('SET_LOADING', true);
    try {
      const token = localStorage.getItem('auth_token');
      const response = await axios.post(`${API_URL}/blockchain/sync`, 
        { vehicle_id: vehicleId },
        { headers: { Authorization: `Bearer ${token}` } }
      );
      commit('SET_ERROR', null);
      return response.data;
    } catch (error) {
      commit('SET_ERROR', error.response?.data?.message || 'Failed to sync vehicle');
      console.error('Error syncing vehicle:', error);
      throw error;
    } finally {
      commit('SET_LOADING', false);
    }
  }
};

export default {
  namespaced: true,
  state,
  getters,
  mutations,
  actions
};
