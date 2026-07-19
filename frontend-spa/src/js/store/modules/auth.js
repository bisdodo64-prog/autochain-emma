import api from '../../api'
import { normalizeUser, isSuperAdmin } from '../../utils/roles'

const ACCOUNTS_KEY = 'autochain_accounts'
const ADMIN_BACKUP_KEY = 'autochain_admin_backup'

const ROLE_PROFILES = {
  gestionnaire_parc: {
    name: 'Jean Dupont',
    email: 'manager@autochain.com',
    phone: '+225 07 02 34 56 78',
    roles: [{ name: 'gestionnaire_parc' }]
  },
  chauffeur: {
    name: 'Pierre Martin',
    email: 'driver1@autochain.com',
    phone: '+225 07 03 45 67 89',
    roles: [{ name: 'chauffeur' }]
  },
  garagiste_agree: {
    name: 'Garage Auto Plus',
    email: 'garage@autochain.com',
    phone: '+225 07 05 67 89 01',
    roles: [{ name: 'garagiste_agree' }]
  },
  auditeur: {
    name: 'Sophie Bernard',
    email: 'auditor@autochain.com',
    phone: '+225 07 06 78 90 12',
    roles: [{ name: 'auditeur' }]
  }
}

const getLocalAccounts = () => {
  try {
    const raw = localStorage.getItem(ACCOUNTS_KEY)
    return raw ? JSON.parse(raw) : []
  } catch {
    return []
  }
}

const saveLocalAccounts = (accounts) => {
  localStorage.setItem(ACCOUNTS_KEY, JSON.stringify(accounts))
}

const purgeLocalAccount = (email) => {
  if (!email) return
  const normalized = String(email).trim().toLowerCase()
  const accounts = getLocalAccounts().filter((a) => a.email.toLowerCase() !== normalized)
  saveLocalAccounts(accounts)
}

const formatApiError = (error) => {
  const data = error?.response?.data
  if (data?.errors) {
    const first = Object.values(data.errors).flat().find(Boolean)
    if (first) return first
  }
  return data?.message || error?.message || 'Erreur de connexion à l\'API'
}

const state = {
  user: null,
  token: null,
  loading: false,
  error: null,
  canSwitchRoles: false
}

const getters = {
  isAuthenticated: (state) => !!state.token,
  user: (state) => state.user,
  token: (state) => state.token,
  loading: (state) => state.loading,
  error: (state) => state.error,
  userRoles: (state) => state.user?.roles || [],
  canSwitchRoles: (state) => state.canSwitchRoles,
  hasRole: (state) => (role) => state.user?.roles?.some((r) => r.name === role) || false
}

const mutations = {
  SET_USER(state, user) { state.user = user },
  SET_TOKEN(state, token) { state.token = token },
  SET_LOADING(state, loading) { state.loading = loading },
  SET_ERROR(state, error) { state.error = error },
  SET_CAN_SWITCH_ROLES(state, value) { state.canSwitchRoles = value },
  CLEAR_AUTH(state) {
    state.user = null
    state.token = null
    state.error = null
    state.canSwitchRoles = false
  }
}

const persistAuth = (user, token, walletAddress = null) => {
  localStorage.setItem('auth_token', token)
  localStorage.setItem('user', JSON.stringify(user))
  if (walletAddress) localStorage.setItem('wallet_address', walletAddress)
}

const localLogin = (credentials) => {
  const accounts = getLocalAccounts()
  const account = accounts.find(
    (a) => a.email.toLowerCase() === String(credentials.email || '').toLowerCase()
  )
  if (!account) throw new Error('Aucun compte trouvé avec cet email')
  if (account.password !== credentials.password) throw new Error('Mot de passe incorrect')
  return {
    id: account.id,
    name: account.name,
    email: account.email,
    roles: [{ name: account.role || 'super_admin' }]
  }
}

const applySession = ({ commit }, user, token) => {
  const normalized = normalizeUser(user)
  const canSwitch = isSuperAdmin(normalized) || normalized?.roles?.some((r) => r.name === 'super_admin')
  commit('SET_USER', normalized)
  commit('SET_TOKEN', token)
  commit('SET_CAN_SWITCH_ROLES', !!canSwitch)
  if (canSwitch) localStorage.setItem(ADMIN_BACKUP_KEY, JSON.stringify(normalized))
  else localStorage.removeItem(ADMIN_BACKUP_KEY)
  if (!String(token).startsWith('local_token_')) {
    purgeLocalAccount(normalized?.email)
  }
  persistAuth(normalized, token)
  return { user: normalized, token }
}

const isNetworkError = (error) => {
  const status = error?.response?.status
  return !status || status >= 500 || error?.code === 'ERR_NETWORK'
}

const actions = {
  async register({ commit }, payload) {
    commit('SET_LOADING', true)
    commit('SET_ERROR', null)
    try {
      const name = String(payload.name || '').trim()
      const email = String(payload.email || '').trim().toLowerCase()
      const password = String(payload.password || '')

      if (!name || !email || !password) throw new Error('Tous les champs sont requis')
      if (password.length < 6) throw new Error('Le mot de passe doit contenir au moins 6 caractères')
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) throw new Error('Email invalide')

      try {
        const { user, token } = await api.register(name, email, password)
        if (user && token) return applySession({ commit }, user, token)
      } catch (error) {
        if (!isNetworkError(error)) throw new Error(formatApiError(error))
      }

      const accounts = getLocalAccounts()
      if (accounts.some((a) => a.email.toLowerCase() === email)) {
        throw new Error('Un compte existe déjà avec cet email')
      }

      const account = {
        id: Date.now(),
        name,
        email,
        password,
        role: 'super_admin',
        createdAt: new Date().toISOString()
      }
      accounts.push(account)
      saveLocalAccounts(accounts)

      const user = { id: account.id, name: account.name, email: account.email, roles: [{ name: 'super_admin' }] }
      const token = 'local_token_' + Date.now()
      return applySession({ commit }, user, token)
    } catch (error) {
      commit('SET_ERROR', error.message)
      throw error
    } finally {
      commit('SET_LOADING', false)
    }
  },

  async login({ commit }, credentials) {
    commit('SET_LOADING', true)
    commit('SET_ERROR', null)
    try {
      try {
        const { user, token } = await api.login(credentials.email, credentials.password)
        if (user && token) return applySession({ commit }, user, token)
      } catch (error) {
        if (!isNetworkError(error)) {
          throw new Error(formatApiError(error))
        }
      }
      const user = localLogin(credentials)
      const token = 'local_token_' + Date.now()
      return applySession({ commit }, user, token)
    } catch (error) {
      commit('SET_ERROR', error.message || 'Erreur de connexion')
      throw error
    } finally {
      commit('SET_LOADING', false)
    }
  },

  async web3Login({ commit }, { walletAddress, signature, message }) {
    commit('SET_LOADING', true)
    commit('SET_ERROR', null)
    try {
      try {
        const { user, token } = await api.web3Login(walletAddress, signature, message)
        if (user && token) {
          return applySession({ commit }, user, token)
        }
      } catch (error) {
        if (!isNetworkError(error)) throw new Error(formatApiError(error))
      }

      const user = {
        id: Date.now(),
        name: 'Web3 ' + walletAddress.slice(0, 6),
        email: `${walletAddress.slice(0, 10).toLowerCase()}@web3.local`,
        roles: [{ name: 'auditeur' }],
        wallet_address: walletAddress
      }
      const token = 'local_token_' + Date.now()
      localStorage.setItem('wallet_address', walletAddress)
      return applySession({ commit }, user, token)
    } catch (error) {
      commit('SET_ERROR', error.message)
      throw error
    } finally {
      commit('SET_LOADING', false)
    }
  },

  async switchRole({ commit, state }, roleName) {
    const canSwitch =
      state.canSwitchRoles ||
      !!localStorage.getItem(ADMIN_BACKUP_KEY) ||
      isSuperAdmin(state.user)

    if (!canSwitch && roleName !== 'super_admin') {
      throw new Error('Changement de rôle non autorisé')
    }

    if (roleName === 'super_admin') {
      const backup = localStorage.getItem(ADMIN_BACKUP_KEY)
      const adminUser = backup
        ? JSON.parse(backup)
        : { ...state.user, roles: [{ name: 'super_admin' }] }
      adminUser.roles = [{ name: 'super_admin' }]
      delete adminUser.switchedFromAdmin
      commit('SET_USER', adminUser)
      commit('SET_CAN_SWITCH_ROLES', true)
      persistAuth(adminUser, state.token || localStorage.getItem('auth_token'))
      return adminUser
    }

    if (!localStorage.getItem(ADMIN_BACKUP_KEY) && state.user) {
      const backupUser = { ...state.user }
      delete backupUser.switchedFromAdmin
      localStorage.setItem(ADMIN_BACKUP_KEY, JSON.stringify(backupUser))
    }

    const profile = ROLE_PROFILES[roleName]
    if (!profile) throw new Error('Rôle inconnu')

    let realUser = null
    const token = state.token || localStorage.getItem('auth_token')
    if (token && !String(token).startsWith('local_token_')) {
      try {
        const users = await api.getAdminUsers()
        const list = Array.isArray(users) ? users : []
        realUser = list.find(
          (u) => String(u.email || '').toLowerCase() === profile.email.toLowerCase()
        ) || null
      } catch {
        realUser = null
      }
    }

    const switched = normalizeUser({
      id: realUser?.id || `role-${roleName}`,
      name: realUser?.name || profile.name,
      email: realUser?.email || profile.email,
      phone: realUser?.phone || profile.phone,
      avatar_url: realUser?.avatar_url || null,
      wallet_address: realUser?.wallet_address || null,
      created_at: realUser?.created_at || null,
      roles: [{ name: roleName }],
      switchedFromAdmin: true
    })

    commit('SET_USER', switched)
    commit('SET_CAN_SWITCH_ROLES', true)
    persistAuth(switched, token)
    return switched
  },

  async logout({ commit }) {
    try { await api.logout() } catch { /* ignore */ }
    commit('CLEAR_AUTH')
    localStorage.removeItem('auth_token')
    localStorage.removeItem('user')
    localStorage.removeItem('wallet_address')
    localStorage.removeItem(ADMIN_BACKUP_KEY)
  },

  initializeAuth({ commit }) {
    const token = localStorage.getItem('auth_token')
    const user = localStorage.getItem('user')
    const backup = localStorage.getItem(ADMIN_BACKUP_KEY)
    if (!token || !user) return

    try {
      const parsed = JSON.parse(user)
      const normalized = normalizeUser(parsed)
      commit('SET_TOKEN', token)
      commit('SET_USER', normalized)
      commit('SET_CAN_SWITCH_ROLES', !!backup || isSuperAdmin(normalized))

      // Ne pas écraser un rôle switché avec le profil admin API
      if (normalized?.switchedFromAdmin) return

      if (!String(token).startsWith('local_token_')) {
        api.getMe()
          .then((freshUser) => {
            const updated = normalizeUser(freshUser)
            commit('SET_USER', updated)
            commit('SET_CAN_SWITCH_ROLES', !!backup || isSuperAdmin(updated))
            persistAuth(updated, token)
          })
          .catch(() => {})
      }
    } catch {
      localStorage.removeItem('auth_token')
      localStorage.removeItem('user')
    }
  }
}

export default {
  namespaced: true,
  state,
  getters,
  mutations,
  actions
}
