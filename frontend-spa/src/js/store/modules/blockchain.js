import web3Service from '../../web3'

const state = {
  isConnected: false,
  account: null,
  chainId: null,
  loading: false,
  error: null,
  mock: false,
  hasContract: Boolean(import.meta.env.VITE_APP_CONTRACT_ADDRESS)
}

const getters = {
  isConnected: (state) => state.isConnected,
  account: (state) => state.account,
  chainId: (state) => state.chainId,
  loading: (state) => state.loading,
  error: (state) => state.error,
  mock: (state) => state.mock,
  hasContract: (state) => state.hasContract,
  isMetaMaskAvailable: () => web3Service.isMetaMaskAvailable()
}

const mutations = {
  SET_CONNECTED(state, connected) {
    state.isConnected = connected
  },
  SET_ACCOUNT(state, account) {
    state.account = account
  },
  SET_CHAIN_ID(state, chainId) {
    state.chainId = chainId
  },
  SET_LOADING(state, loading) {
    state.loading = loading
  },
  SET_ERROR(state, error) {
    state.error = error
  },
  SET_MOCK(state, mock) {
    state.mock = mock
  },
  RESET_STATE(state) {
    state.isConnected = false
    state.account = null
    state.chainId = null
    state.error = null
    state.mock = false
  }
}

const actions = {
  async connectWallet({ commit }) {
    commit('SET_LOADING', true)
    commit('SET_ERROR', null)
    try {
      const address = await web3Service.init()
      commit('SET_ACCOUNT', address)
      commit('SET_CHAIN_ID', web3Service.chainId)
      commit('SET_MOCK', web3Service.mock)
      commit('SET_CONNECTED', true)
      localStorage.setItem('wallet_address', address)
      return address
    } catch (error) {
      commit('SET_ERROR', error.message)
      throw error
    } finally {
      commit('SET_LOADING', false)
    }
  },

  async disconnectWallet({ commit }) {
    web3Service.disconnect()
    commit('RESET_STATE')
    localStorage.removeItem('wallet_address')
  },

  async checkConnection({ commit, dispatch }) {
    const savedAddress = localStorage.getItem('wallet_address')
    if (!savedAddress) return

    if (web3Service.isMetaMaskAvailable()) {
      try {
        await dispatch('connectWallet')
      } catch {
        commit('SET_CONNECTED', true)
        commit('SET_ACCOUNT', savedAddress)
      }
    } else {
      commit('SET_CONNECTED', true)
      commit('SET_ACCOUNT', savedAddress)
      commit('SET_MOCK', true)
    }
  },

  async signMessage({ state }, message) {
    if (!state.isConnected) {
      throw new Error('Wallet non connecté')
    }
    return web3Service.signMessage(message)
  },

  async ensureLocalNetwork() {
    return web3Service.ensureLocalNetwork()
  },

  async registerVehicleOnChain(_, { vin, initialMileage }) {
    const receipt = await web3Service.registerVehicle(vin, initialMileage)
    return { transactionHash: receipt?.hash }
  },

  async updateMileageOnChain(_, { vehicleId, newMileage }) {
    const receipt = await web3Service.updateMileage(vehicleId, newMileage)
    return { transactionHash: receipt?.hash }
  },

  async recordMaintenanceOnChain(_, { vehicleId, description, partsChanged }) {
    const receipt = await web3Service.recordMaintenance(vehicleId, description, partsChanged)
    return { transactionHash: receipt?.hash }
  },

  async authorizeGarageOnChain(_, { address, status }) {
    const receipt = await web3Service.authorizeGarage(address, status)
    return { transactionHash: receipt?.hash }
  }
}

export default {
  namespaced: true,
  state,
  getters,
  mutations,
  actions
}
