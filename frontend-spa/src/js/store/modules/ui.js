const state = {
  sidebarOpen: false
}

const getters = {
  sidebarOpen: (s) => s.sidebarOpen
}

const mutations = {
  SET_SIDEBAR_OPEN(state, open) {
    state.sidebarOpen = Boolean(open)
  },
  TOGGLE_SIDEBAR(state) {
    state.sidebarOpen = !state.sidebarOpen
  }
}

const actions = {
  openSidebar({ commit }) {
    commit('SET_SIDEBAR_OPEN', true)
  },
  closeSidebar({ commit }) {
    commit('SET_SIDEBAR_OPEN', false)
  },
  toggleSidebar({ commit }) {
    commit('TOGGLE_SIDEBAR')
  }
}

export default {
  namespaced: true,
  state,
  getters,
  mutations,
  actions
}
