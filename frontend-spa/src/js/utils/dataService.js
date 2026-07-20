import api from '../api'
import { normalizeVehicle } from './vehicles'
import { withWeb3Proof } from './web3Sign'
import { normalizeUser, getRoleLabel, getPrimaryRole } from './roles'
import { resolveAvatarUrl } from './avatarUrl'
import {
  loadVehicles,
  saveVehicles,
  loadDrivers,
  saveDrivers,
  loadDocuments,
  saveDocuments,
  loadMaintenance,
  loadMaintenanceHistory,
  STORAGE_KEYS,
  DEFAULT_MAINTENANCE,
  DEFAULT_MAINTENANCE_HISTORY,
  DEMO_INTERVENTIONS
} from './localData'

const DOC_TYPE_LABELS = {
  registration: 'Carte grise',
  insurance: 'Assurance',
  tech_control: 'Contrôle technique',
  facture: 'Facture'
}

const DOC_TYPE_COLORS = {
  registration: 'rgba(59,130,246,.2)',
  insurance: 'rgba(16,185,129,.2)',
  tech_control: 'rgba(245,158,11,.2)',
  facture: 'rgba(124,58,237,.2)'
}

const DOC_TYPE_ICONS = {
  registration: 'fas fa-id-card',
  insurance: 'fas fa-shield-alt',
  tech_control: 'fas fa-clipboard-check',
  facture: 'fas fa-file-invoice'
}

const COLORS = ['#2563eb', '#7c3aed', '#f59e0b', '#10b981', '#ef4444', '#06b6d4']

export const normalizeApiDocument = (doc, vehicle) => {
  const typeLabel = DOC_TYPE_LABELS[doc.document_type] || doc.document_type || 'Autre'
  const expiry = doc.expiry_date ? new Date(doc.expiry_date).toLocaleDateString('fr-FR') : 'N/A'
  const expiryDate = doc.expiry_date ? new Date(doc.expiry_date) : null
  const expiryClass = !expiryDate
    ? 'ok'
    : expiryDate < new Date()
      ? 'danger'
      : expiryDate < new Date(Date.now() + 30 * 24 * 3600 * 1000)
        ? 'warning'
        : 'ok'

  return {
    id: doc.id,
    vehicleId: vehicle?.id,
    name: doc.file_name || 'Document',
    vehicle: vehicle ? `${vehicle.brand} ${vehicle.model}` : 'Véhicule',
    type: typeLabel,
    expiry,
    expiryClass,
    icon: DOC_TYPE_ICONS[doc.document_type] || 'fas fa-file-alt',
    color: DOC_TYPE_COLORS[doc.document_type] || 'rgba(148,163,184,.2)',
    blockchainVerified: Boolean(doc.ipfs_hash || doc.file_hash)
  }
}

export const normalizeApiDriver = (driver, index) => {
  const vehicle = driver.vehicles?.[0]
  return {
    id: driver.id,
    name: driver.name,
    initials: driver.name.split(' ').map((n) => n[0]).join('').toUpperCase().slice(0, 2),
    role: 'Chauffeur',
    email: driver.email,
    phone: driver.phone || 'Non renseigné',
    vehicle: vehicle ? `${vehicle.brand} ${vehicle.model} (${vehicle.plate_number})` : null,
    vehicleId: vehicle?.id || null,
    missions: driver.missions_count || 0,
    km: vehicle?.current_mileage ? `${vehicle.current_mileage.toLocaleString('fr-FR')} km` : '0 km',
    rating: driver.rating || 4.8,
    active: true,
    avatarUrl: resolveAvatarUrl(driver.avatar_url) || null,
    color: COLORS[index % COLORS.length]
  }
}

export const normalizeApiMaintenance = (record, vehicle, active = false) => ({
  id: record.id,
  vehicleId: vehicle?.id,
  vehicle: vehicle ? `${vehicle.brand} ${vehicle.model}` : 'Véhicule',
  description: record.description || record.type || 'Intervention',
  mechanic: record.garage?.name || 'Garage AutoPlus',
  date: record.performed_at
    ? new Date(record.performed_at).toLocaleDateString('fr-FR')
    : new Date().toLocaleDateString('fr-FR'),
  urgency: active || vehicle?.status === 'maintenance' ? 'urgent' : 'normal',
  urgencyLabel: active || vehicle?.status === 'maintenance' ? 'Urgent' : 'Normal',
  status: active || vehicle?.status === 'maintenance' ? 'En cours' : 'En attente',
  statusClass: active || vehicle?.status === 'maintenance' ? 'progress' : 'pending',
  icon: 'fas fa-wrench',
  cost: record.cost || 0,
  certified: Boolean(record.blockchain_tx_hash),
  type: record.type || 'Maintenance',
  parts_changed: record.parts_changed || record.description || ''
})

export const normalizeApiMaintenanceHistory = (record, vehicle) => ({
  id: record.id,
  vehicleId: vehicle?.id,
  vehicle: vehicle ? `${vehicle.brand} ${vehicle.model}` : 'Véhicule',
  type: record.type || record.description?.slice(0, 24) || 'Maintenance',
  date: record.performed_at
    ? new Date(record.performed_at).toLocaleDateString('fr-FR')
    : new Date().toLocaleDateString('fr-FR'),
  mechanic: record.garage?.name || 'Garage',
  cost: record.cost || 0,
  certified: Boolean(record.blockchain_tx_hash),
  description: record.description || '',
  parts_changed: record.parts_changed || record.description || ''
})

const isApiToken = () => {
  const token = localStorage.getItem('auth_token')
  return token && !String(token).startsWith('local_token_')
}

export const isLocalToken = () => !isApiToken()

export async function fetchVehiclesHybrid() {
  if (isApiToken()) {
    try {
      const response = await api.getVehicles()
      const payload = Array.isArray(response) ? response : response?.vehicles || []
      if (payload.length) {
        const normalized = payload.map((v) => normalizeVehicle(v))
        saveVehicles(normalized)
        return { data: normalized, source: 'api' }
      }
    } catch (error) {
      console.warn('API véhicules indisponible, fallback local', error)
    }
  }
  return { data: loadVehicles(), source: 'local' }
}

export async function fetchDriversHybrid() {
  if (isApiToken()) {
    try {
      const response = await api.getDrivers()
      const list = Array.isArray(response) ? response : []
      if (list.length) {
        const normalized = list.map(normalizeApiDriver)
        saveDrivers(normalized)
        return { data: normalized, source: 'api' }
      }
    } catch (error) {
      console.warn('API chauffeurs indisponible, fallback local', error)
    }
  }
  return { data: loadDrivers(), source: 'local' }
}

export async function fetchDocumentsHybrid() {
  if (isApiToken()) {
    try {
      const vehicles = await api.getVehicles()
      const list = Array.isArray(vehicles) ? vehicles : []
      const allDocs = []
      for (const vehicle of list) {
        const docs = await api.getDocuments(vehicle.id)
        ;(Array.isArray(docs) ? docs : []).forEach((doc) => {
          allDocs.push(normalizeApiDocument(doc, vehicle))
        })
      }
      if (allDocs.length) {
        saveDocuments(allDocs)
        return { data: allDocs, source: 'api' }
      }
    } catch (error) {
      console.warn('API documents indisponible, fallback local', error)
    }
  }
  return { data: loadDocuments(), source: 'local' }
}

export async function fetchMaintenanceHybrid() {
  if (isApiToken()) {
    try {
      const vehicles = await api.getVehicles()
      const list = Array.isArray(vehicles) ? vehicles : []
      const interventions = []
      const history = []

      for (const vehicle of list) {
        const records = await api.getMaintenance(vehicle.id)
        ;(Array.isArray(records) ? records : []).forEach((record) => {
          if (vehicle.status === 'maintenance') {
            interventions.push(normalizeApiMaintenance(record, vehicle, true))
          } else {
            history.push(normalizeApiMaintenanceHistory(record, vehicle))
          }
        })
        if (vehicle.status === 'maintenance' && !interventions.some((i) => i.vehicleId === vehicle.id)) {
          interventions.push({
            id: `pending-${vehicle.id}`,
            vehicleId: vehicle.id,
            vehicle: `${vehicle.brand} ${vehicle.model}`,
            description: 'Maintenance en cours',
            mechanic: 'Garage',
            date: new Date().toLocaleDateString('fr-FR'),
            urgency: 'urgent',
            urgencyLabel: 'Urgent',
            status: 'En cours',
            statusClass: 'progress',
            icon: 'fas fa-tools'
          })
        }
      }

      if (interventions.length || history.length) {
        localStorage.setItem(STORAGE_KEYS.maintenance, JSON.stringify(interventions))
        localStorage.setItem(STORAGE_KEYS.maintenanceHistory, JSON.stringify(history))
        return {
          interventions,
          history,
          source: 'api'
        }
      }
    } catch (error) {
      console.warn('API maintenance indisponible, fallback local', error)
    }
  }

  return {
    interventions: loadMaintenance(),
    history: loadMaintenanceHistory(),
    source: 'local'
  }
}

export async function fetchDashboardInterventionsHybrid() {
  const result = await fetchMaintenanceHybrid()
  if (result.interventions.length) return { data: result.interventions, source: result.source }
  return { data: [...DEMO_INTERVENTIONS], source: 'local' }
}

export async function fetchAlertsHybrid() {
  if (isApiToken()) {
    try {
      const alerts = await api.getAlerts()
      if (Array.isArray(alerts)) {
        return { data: alerts, source: 'api' }
      }
    } catch (error) {
      console.warn('API alertes indisponible', error)
    }
  }
  return { data: [], source: 'local' }
}

const ADMIN_STORAGE_KEY = 'autochain_admin_users'

const DEFAULT_ADMIN_USERS = [
  { id: 1, name: 'Admin Système', email: 'admin@autochain.com', role: 'Super Admin', active: true },
  { id: 2, name: 'Jean Dupont', email: 'manager@autochain.com', role: 'Gestionnaire', active: true },
  { id: 3, name: 'Marie Martin', email: 'driver1@autochain.com', role: 'Chauffeur', active: true },
  { id: 4, name: 'Paul Durand', email: 'garage@autochain.com', role: 'Garagiste', active: false },
  { id: 5, name: 'Sophie Bernard', email: 'auditor@autochain.com', role: 'Auditeur', active: true }
]

export const normalizeAdminUser = (user) => ({
  id: user.id,
  name: user.name,
  email: user.email,
  role: user.role || 'Chauffeur',
  active: user.active !== false,
  avatar_url: resolveAvatarUrl(user.avatar_url) || null
})

const loadLocalAdminUsers = () => {
  try {
    const raw = localStorage.getItem(ADMIN_STORAGE_KEY)
    if (raw) return JSON.parse(raw)
  } catch (e) { /* ignore */ }
  return DEFAULT_ADMIN_USERS
}

const saveLocalAdminUsers = (users) => {
  localStorage.setItem(ADMIN_STORAGE_KEY, JSON.stringify(users))
}

export async function fetchUsersHybrid() {
  if (isApiToken()) {
    try {
      const list = await api.getAdminUsers()
      if (Array.isArray(list)) {
        const normalized = list.map(normalizeAdminUser)
        saveLocalAdminUsers(normalized)
        return { data: normalized, source: 'api' }
      }
    } catch (error) {
      console.warn('API admin indisponible, fallback local', error)
    }
  }
  return { data: loadLocalAdminUsers(), source: 'local' }
}

export async function saveUserHybrid(form, editing) {
  const payload = {
    name: form.name,
    email: form.email,
    role: form.role,
    active: form.active
  }

  if (isApiToken()) {
    try {
      const response = editing
        ? await api.updateAdminUser(form.id, payload)
        : await api.createAdminUser(payload)
      const user = normalizeAdminUser(response?.user || response)
      const local = loadLocalAdminUsers()
      if (editing) {
        const idx = local.findIndex((u) => u.id === user.id)
        if (idx !== -1) local[idx] = user
        else local.push(user)
      } else {
        local.push(user)
      }
      saveLocalAdminUsers(local)
      return { user, source: 'api' }
    } catch (error) {
      console.warn('Enregistrement API échoué, fallback local', error)
    }
  }

  const local = loadLocalAdminUsers()
  if (editing) {
    const idx = local.findIndex((u) => u.id === form.id)
    if (idx !== -1) local[idx] = { ...form }
  } else {
    local.push({ ...form, id: Date.now() })
  }
  saveLocalAdminUsers(local)
  return { user: form, source: 'local' }
}

export async function deleteUserHybrid(id) {
  if (isApiToken()) {
    try {
      await api.deleteAdminUser(id)
      const local = loadLocalAdminUsers().filter((u) => u.id !== id)
      saveLocalAdminUsers(local)
      return { source: 'api' }
    } catch (error) {
      console.warn('Suppression API échouée, fallback local', error)
    }
  }

  const local = loadLocalAdminUsers().filter((u) => u.id !== id)
  saveLocalAdminUsers(local)
  return { source: 'local' }
}

export async function verifyVehicleHybrid(query) {
  if (isApiToken()) {
    try {
      const result = await api.verifyVehicle(query)
      if (result) {
        return {
          data: {
            valid: result.valid,
            vehicle: result.vehicle,
            message: result.message,
            mileage: String(result.mileage || 0).replace(/\B(?=(\d{3})+(?!\d))/g, ' '),
            lastMaintenance: result.last_maintenance || 'N/A',
            txCount: result.tx_count || 0
          },
          source: 'api'
        }
      }
    } catch (error) {
      console.warn('API audit indisponible, fallback local', error)
    }
  }

  const stored = localStorage.getItem('dashboard_vehicles')
  let found = null
  if (stored) {
    try {
      const list = JSON.parse(stored)
      found = list.find((v) =>
        String(v.licensePlate || v.plate_number || '').toLowerCase() === query.toLowerCase() ||
        String(v.vin || '').toLowerCase() === query.toLowerCase() ||
        String(v.id) === query
      )
    } catch (e) { /* ignore */ }
  }

  if (found) {
    return {
      data: {
        valid: true,
        vehicle: `${found.brand} ${found.model} (${found.licensePlate || found.plate_number})`,
        message: 'Véhicule authentifié (cache local).',
        mileage: String(found.mileage || found.current_mileage || 0).replace(/\B(?=(\d{3})+(?!\d))/g, ' '),
        lastMaintenance: 'N/A',
        txCount: found.blockchainVerified ? 12 : 3
      },
      source: 'local'
    }
  }

  return {
    data: {
      valid: false,
      vehicle: query,
      message: 'Aucun véhicule trouvé pour cette recherche.'
    },
    source: 'local'
  }
}

const ALERT_TYPE_ICONS = {
  maintenance: 'fas fa-tools',
  insurance_expiry: 'fas fa-file-alt',
  tech_control: 'fas fa-clipboard-check',
  document: 'fas fa-file-alt',
  blockchain: 'fas fa-cubes',
  vehicle: 'fas fa-car'
}

const formatRelativeTime = (dateStr) => {
  if (!dateStr) return 'Récemment'
  const date = new Date(dateStr)
  const diffMs = Date.now() - date.getTime()
  const mins = Math.floor(diffMs / 60000)
  if (mins < 1) return "À l'instant"
  if (mins < 60) return `Il y a ${mins} min`
  const hours = Math.floor(mins / 60)
  if (hours < 24) return `Il y a ${hours} h`
  const days = Math.floor(hours / 24)
  return days === 1 ? 'Hier' : `Il y a ${days} j`
}

export const normalizeAlertNotification = (alert) => ({
  id: alert.id,
  icon: ALERT_TYPE_ICONS[alert.type] || 'fas fa-bell',
  title: (alert.type || 'alerte').replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase()),
  text: alert.message,
  time: formatRelativeTime(alert.triggered_at),
  read: Boolean(alert.dismissed_at),
  severity: alert.severity || 'info'
})

const DEFAULT_NOTIFICATIONS = [
  { id: 1, icon: 'fas fa-car', title: 'Nouveau véhicule', text: 'Peugeot 308 ajouté à la flotte', time: 'Il y a 5 min', read: false },
  { id: 2, icon: 'fas fa-tools', title: 'Maintenance', text: 'Intervention en attente sur Citroën C3', time: 'Il y a 20 min', read: false },
  { id: 3, icon: 'fas fa-file-alt', title: 'Document', text: 'Contrôle technique à renouveler', time: 'Il y a 1 h', read: false }
]

export async function fetchNotificationsHybrid() {
  const { data, source } = await fetchAlertsHybrid()
  if (source === 'api' && data.length) {
    return { data: data.map(normalizeAlertNotification), source: 'api' }
  }
  if (source === 'api' && !data.length) {
    return { data: [], source: 'api' }
  }
  return { data: DEFAULT_NOTIFICATIONS, source: 'local' }
}

export async function dismissAlertHybrid(id) {
  if (isApiToken()) {
    try {
      await api.dismissAlert(id)
      return { source: 'api' }
    } catch (error) {
      console.warn('Dismiss alerte API échoué', error)
    }
  }
  return { source: 'local' }
}

const PROFILE_KEY = 'autochain_profile'

const profileStorageKey = (roleKey = 'default') => `${PROFILE_KEY}_${roleKey || 'default'}`

const loadLocalProfileExtras = (roleKey = 'default') => {
  try {
    const keyed = localStorage.getItem(profileStorageKey(roleKey))
    if (keyed) return JSON.parse(keyed)
    const raw = localStorage.getItem(PROFILE_KEY)
    return raw ? JSON.parse(raw) : {}
  } catch {
    return {}
  }
}

const saveLocalProfileExtras = (extras, roleKey = 'default') => {
  localStorage.setItem(profileStorageKey(roleKey), JSON.stringify(extras || {}))
}

const formatDateFr = (value) => {
  if (!value) return 'N/A'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return String(value)
  return date.toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' })
}

const departmentForRole = (role) => {
  if (role === 'garagiste_agree' || role === 'garage') return 'Atelier'
  if (role === 'chauffeur' || role === 'driver') return 'Transport'
  if (role === 'auditeur' || role === 'auditor') return 'Audit'
  if (role === 'super_admin') return 'Direction'
  return 'Logistique'
}

export const normalizeProfile = (user, extras = {}) => {
  const normalized = normalizeUser(user) || {}
  const role = getPrimaryRole(normalized)
  return {
    id: normalized?.id,
    name: normalized?.name || 'Utilisateur',
    email: normalized?.email || '',
    phone: normalized?.phone || '+225 07 00 00 00 00',
    department: extras.department || departmentForRole(role),
    joined: formatDateFr(normalized?.created_at || extras.joined),
    walletAddress: normalized?.wallet_address || extras.walletAddress || '',
    avatarUrl: resolveAvatarUrl(normalized?.avatar_url || extras.avatarUrl) || null,
    roleLabel: getRoleLabel(role)
  }
}

export async function fetchProfileHybrid(userFromStore = null) {
  const user = userFromStore || JSON.parse(localStorage.getItem('user') || 'null')
  const roleKey = user?.roles?.[0]?.name || user?.email || 'default'
  const localExtras = loadLocalProfileExtras(roleKey)

  // En mode "rôle switché", ne pas rappeler getMe() (sinon le profil admin revient)
  if (user?.switchedFromAdmin) {
    return {
      data: normalizeProfile(user, localExtras),
      user: normalizeUser(user),
      source: 'role'
    }
  }

  if (isApiToken()) {
    try {
      const freshUser = await api.getMe()
      const profile = normalizeProfile(freshUser, localExtras)
      return { data: profile, user: normalizeUser(freshUser), source: 'api' }
    } catch (error) {
      console.warn('API profil indisponible, fallback local', error)
    }
  }

  return {
    data: normalizeProfile(user, localExtras),
    user: normalizeUser(user),
    source: 'local'
  }
}

export async function saveProfileHybrid(form) {
  const storedUser = JSON.parse(localStorage.getItem('user') || 'null') || {}
  const roleKey = storedUser?.roles?.[0]?.name || storedUser?.email || 'default'
  const payload = {
    name: form.name,
    email: form.email,
    phone: form.phone
  }

  // Profil d'un rôle switché : sauvegarde locale dédiée (ne pas écraser l'admin API)
  if (storedUser?.switchedFromAdmin) {
    const merged = {
      ...storedUser,
      name: form.name,
      email: form.email,
      phone: form.phone
    }
    localStorage.setItem('user', JSON.stringify(merged))
    const localExtras = { ...loadLocalProfileExtras(roleKey), department: form.department }
    saveLocalProfileExtras(localExtras, roleKey)
    return {
      data: normalizeProfile(merged, localExtras),
      user: normalizeUser(merged),
      source: 'role'
    }
  }

  if (isApiToken()) {
    try {
      const response = await api.updateProfile(payload)
      const user = normalizeUser(response?.user || response)
      const localExtras = { ...loadLocalProfileExtras(roleKey), department: form.department }
      saveLocalProfileExtras(localExtras, roleKey)
      return { data: normalizeProfile(user, localExtras), user, source: 'api' }
    } catch (error) {
      console.warn('Mise à jour profil API échouée, fallback local', error)
    }
  }

  const merged = { ...storedUser, name: form.name, email: form.email, phone: form.phone }
  localStorage.setItem('user', JSON.stringify(merged))
  const localExtras = { department: form.department }
  saveLocalProfileExtras(localExtras, roleKey)
  return {
    data: normalizeProfile(merged, localExtras),
    user: normalizeUser(merged),
    source: 'local'
  }
}

export async function uploadAvatarHybrid(file) {
  const storedUser = JSON.parse(localStorage.getItem('user') || 'null') || {}
  const roleKey = storedUser?.roles?.[0]?.name || storedUser?.email || 'default'

  if (storedUser?.switchedFromAdmin) {
    return new Promise((resolve, reject) => {
      const reader = new FileReader()
      reader.onload = () => {
        const localExtras = { ...loadLocalProfileExtras(roleKey), avatarUrl: reader.result }
        saveLocalProfileExtras(localExtras, roleKey)
        const user = normalizeUser({ ...storedUser, avatar_url: reader.result })
        localStorage.setItem('user', JSON.stringify(user))
        resolve({
          data: normalizeProfile(user, localExtras),
          user,
          source: 'role',
          message: 'Photo enregistrée pour ce rôle'
        })
      }
      reader.onerror = () => reject(new Error('Lecture de la photo impossible'))
      reader.readAsDataURL(file)
    })
  }

  if (isApiToken()) {
    const response = await api.uploadAvatar(file)
    const user = normalizeUser(response?.user || response)
    localStorage.setItem('user', JSON.stringify(user))
    return {
      data: normalizeProfile(user, loadLocalProfileExtras(roleKey)),
      user,
      source: 'api',
      message: response?.message
    }
  }

  return new Promise((resolve, reject) => {
    const reader = new FileReader()
    reader.onload = () => {
      const localExtras = { ...loadLocalProfileExtras(roleKey), avatarUrl: reader.result }
      saveLocalProfileExtras(localExtras, roleKey)
      const user = normalizeUser({ ...storedUser, avatar_url: reader.result })
      localStorage.setItem('user', JSON.stringify(user))
      resolve({
        data: normalizeProfile(user, localExtras),
        user,
        source: 'local',
        message: 'Photo enregistrée localement'
      })
    }
    reader.onerror = () => reject(new Error('Lecture de la photo impossible'))
    reader.readAsDataURL(file)
  })
}

export async function updateWalletHybrid(address) {
  if (isApiToken()) {
    try {
      const response = await api.updateProfile({ wallet_address: address })
      const user = normalizeUser(response?.user || response)
      return { user, source: 'api' }
    } catch (error) {
      console.warn('Mise à jour wallet API échouée', error)
    }
  }
  return { source: 'local' }
}

export async function changePasswordHybrid(password, passwordConfirmation) {
  if (isApiToken()) {
    try {
      await api.updatePassword(password, passwordConfirmation)
      return { source: 'api' }
    } catch (error) {
      console.warn('Changement mot de passe API échoué', error)
      throw error
    }
  }
  return { source: 'local' }
}

const TX_TYPE_ICONS = {
  registration: 'fas fa-plus-circle',
  mileage: 'fas fa-tachometer-alt',
  maintenance: 'fas fa-tools'
}

export const normalizeBlockchainTx = (tx) => ({
  id: tx.id,
  type: tx.type,
  icon: TX_TYPE_ICONS[tx.type] || 'fas fa-cube',
  label: tx.label,
  vehicle: tx.vehicle,
  hash: tx.hash,
  fullHash: tx.full_hash || tx.fullHash,
  explorerUrl: tx.explorer_url || tx.explorerUrl || null,
  date: tx.date
})

export async function fetchBlockchainHybrid() {
  if (isApiToken()) {
    try {
      const [transactions, stats] = await Promise.all([
        api.getBlockchainTransactions(),
        api.getBlockchainStats()
      ])
      const list = Array.isArray(transactions) ? transactions.map(normalizeBlockchainTx) : []
      return {
        transactions: list,
        stats: stats || {},
        source: 'api'
      }
    } catch (error) {
      console.warn('API blockchain indisponible, fallback local', error)
    }
  }

  return {
    transactions: [
      { id: 1, type: 'registration', icon: 'fas fa-plus-circle', label: 'Enregistrement véhicule', vehicle: 'Toyota Corolla', hash: '0x1234…5678', fullHash: '0x1234567890abcdef1234567890abcdef12345678', date: '15/03/2022' },
      { id: 2, type: 'mileage', icon: 'fas fa-tachometer-alt', label: 'Relevé kilométrique', vehicle: 'Renault Clio', hash: '0xabcd…ef01', fullHash: '0xabcdef0123456789abcdef0123456789abcdef01', date: '10/06/2023' },
      { id: 3, type: 'maintenance', icon: 'fas fa-tools', label: 'Maintenance certifiée', vehicle: 'Peugeot 308', hash: '0x2345…6789', fullHash: '0x234567890abcdef1234567890abcdef123456789', date: '05/01/2024' }
    ],
    stats: { tx: 155, certified: 48, maintenance: 12, anomalies: 0 },
    source: 'local'
  }
}

export async function syncBlockchainHybrid() {
  if (isApiToken()) {
    try {
      // La sync est exécutée côté serveur (clés Ganache du .env) — pas de signature MetaMask requise.
      const result = await api.syncAll({})
      const refreshed = await fetchBlockchainHybrid()
      return {
        ...result,
        ...refreshed,
        message: result.message || 'Registre blockchain synchronisé',
        source: 'api'
      }
    } catch (error) {
      console.warn('Sync blockchain API échouée', error)
      throw error
    }
  }
  return { message: 'Synchronisation locale simulée', source: 'local' }
}

const buildTimelineEvents = (vehicle, maintenances = [], mileageRecords = []) => {
  const events = []

  if (vehicle?.blockchain_id || vehicle?.blockchainVerified) {
    const regHash = vehicle.blockchain_tx_hash
      || `0xreg${String(vehicle.blockchain_id || vehicle.id).padStart(16, '0')}`
    events.push({
      type: 'registration',
      typeLabel: 'Enregistrement',
      date: formatDateFr(vehicle.created_at) !== 'N/A' ? new Date(vehicle.created_at).toLocaleDateString('fr-FR') : vehicle.serviceDate || '—',
      description: 'Véhicule enregistré sur la blockchain',
      author: 'Admin Système',
      txHash: regHash
    })
  }

  ;(Array.isArray(mileageRecords) ? mileageRecords : []).forEach((record) => {
    if (!record.blockchain_tx_hash) return
    events.push({
      type: 'mileage',
      typeLabel: 'Relevé Kilométrique',
      date: record.recorded_at ? new Date(record.recorded_at).toLocaleDateString('fr-FR') : '—',
      description: `Relevé certifié : ${Number(record.mileage || 0).toLocaleString('fr-FR')} km`,
      author: record.recorder?.name || 'Chauffeur',
      txHash: record.blockchain_tx_hash
    })
  })

  ;(Array.isArray(maintenances) ? maintenances : []).forEach((record) => {
    if (!record.blockchain_tx_hash) return
    events.push({
      type: 'maintenance',
      typeLabel: 'Maintenance',
      date: record.performed_at ? new Date(record.performed_at).toLocaleDateString('fr-FR') : '—',
      description: record.description || record.type || 'Intervention certifiée',
      author: record.garage?.name || 'Garage',
      txHash: record.blockchain_tx_hash
    })
  })

  return events
}

const normalizeFuelRecord = (record) => ({
  id: record.id,
  date: record.fueled_at ? new Date(record.fueled_at).toLocaleDateString('fr-FR') : '—',
  liters: Number(record.amount || 0),
  price: Number(record.total_cost || 0),
  station: 'Station',
  km: record.mileage_at_fuel || 0
})

const normalizeDetailDocument = (doc, vehicle) => ({
  id: doc.id,
  name: doc.file_name || 'Document',
  type: DOC_TYPE_LABELS[doc.document_type] || doc.document_type || 'Document',
  expiryDate: doc.expiry_date ? new Date(doc.expiry_date).toLocaleDateString('fr-FR') : 'N/A',
  icon: DOC_TYPE_ICONS[doc.document_type] || 'fas fa-file-alt',
  blockchainVerified: Boolean(doc.ipfs_hash || doc.file_hash),
  vehicle: vehicle ? `${vehicle.brand} ${vehicle.model}` : ''
})

export async function fetchVehicleDetailHybrid(vehicleId) {
  const id = Number(vehicleId)

  if (isApiToken()) {
    try {
      const [rawVehicle, maintenances, documents] = await Promise.all([
        api.getVehicle(id),
        api.getMaintenance(id),
        api.getDocuments(id)
      ])

      const normalized = normalizeVehicle(rawVehicle)
      normalized.owner = rawVehicle?.driver?.name || 'Équipe parc'
      normalized.hybridType = normalized.fuelType
      normalized.serviceDate = rawVehicle?.created_at
        ? new Date(rawVehicle.created_at).toLocaleDateString('fr-FR')
        : normalized.serviceDate

      const maintenanceList = Array.isArray(maintenances) ? maintenances : []
      const docs = (Array.isArray(documents) ? documents : []).map((doc) => normalizeDetailDocument(doc, rawVehicle))
      const fuelHistory = (rawVehicle?.fuel_records || []).map(normalizeFuelRecord)
      const blockchainEvents = buildTimelineEvents(rawVehicle, maintenanceList, rawVehicle?.mileage_records || [])

      return {
        vehicle: normalized,
        documents: docs,
        fuelHistory,
        blockchainEvents,
        maintenances: maintenanceList,
        source: 'api'
      }
    } catch (error) {
      console.warn('API détail véhicule indisponible, fallback local', error)
    }
  }

  const stored = loadVehicles()
  const found = stored.find((item) => Number(item.id) === id)
  const vehicle = found
    ? {
        ...found,
        fuelType: found.fuelType || 'Hybride',
        hybridType: found.hybridType || found.fuelType || 'Hybride standard',
        serviceDate: found.serviceDate || '15/03/2024',
        transmission: found.transmission || 'Automatique',
        owner: found.owner || 'Équipe parc',
        statusIcon: found.statusClass === 'available' ? 'fas fa-check-circle' : found.statusClass === 'mission' ? 'fas fa-truck' : 'fas fa-tools',
        blockchainVerified: Boolean(found.blockchainVerified),
        mileage: found.mileage || found.current_mileage || 0,
        licensePlate: found.licensePlate || found.plate_number || '—'
      }
    : {
        id,
        brand: 'Toyota',
        model: 'Corolla',
        year: 2022,
        licensePlate: 'AA-123-BB',
        mileage: 45000,
        fuelType: 'Hybride',
        hybridType: 'Hybride rechargeable',
        serviceDate: '15/03/2022',
        transmission: 'Automatique',
        owner: 'Florence Martin',
        status: 'Disponible',
        statusClass: 'available',
        statusIcon: 'fas fa-check-circle',
        blockchainVerified: true
      }

  return {
    vehicle,
    documents: [
      { id: 1, name: 'Carte Grise', type: 'Document administratif', expiryDate: 'N/A', icon: 'fas fa-id-card', blockchainVerified: true, vehicle: `${vehicle.brand} ${vehicle.model}` },
      { id: 2, name: 'Assurance 2026', type: 'Contrat assurance', expiryDate: '31/12/2026', icon: 'fas fa-shield-alt', blockchainVerified: true, vehicle: `${vehicle.brand} ${vehicle.model}` }
    ],
    fuelHistory: [
      { id: 1, date: '10/07/2026', liters: 42, price: 27300, station: 'Total Energies', km: 44800 }
    ],
    blockchainEvents: buildTimelineEvents(vehicle, [], []),
    maintenances: [],
    source: 'local'
  }
}

export async function createVehicleHybrid(form) {
  const payload = {
    vin: form.vin || `VIN${Date.now()}`,
    brand: form.brand,
    model: form.model,
    year: Number(form.year || new Date().getFullYear()),
    plate_number: form.licensePlate || form.plate_number || `TMP-${Date.now()}`,
    initial_mileage: parseInt(form.mileage, 10) || 0,
    purchase_price: form.purchasePrice != null && form.purchasePrice !== ''
      ? Number(form.purchasePrice)
      : null
  }

  if (isApiToken()) {
    try {
      const response = await withWeb3Proof('register_vehicle', { vin: payload.vin }, (proof) =>
        api.createVehicle(payload, proof)
      )
      return normalizeVehicle(response?.vehicle || response)
    } catch (error) {
      // Si la preuve web3 échoue, tenter sans preuve (création DB seule)
      try {
        const response = await api.createVehicle(payload)
        return normalizeVehicle(response?.vehicle || response)
      } catch (err) {
        console.warn('Création API échouée, enregistrement local', err)
        throw err
      }
    }
  }

  return normalizeVehicle({
    id: Date.now(),
    ...payload,
    current_mileage: payload.initial_mileage,
    status: 'available'
  })
}

export async function deleteVehicleHybrid(id) {
  if (!id) throw new Error('Véhicule invalide')

  if (isApiToken()) {
    try {
      await api.deleteVehicle(id)
      return { source: 'api' }
    } catch (error) {
      console.warn('Suppression API échouée, suppression locale', error)
    }
  }

  return { source: 'local' }
}

export async function certifyVehicleHybrid(vehicle, { force = false } = {}) {
  if (!vehicle?.id) throw new Error('Véhicule invalide')

  if (isApiToken()) {
    try {
      const response = await withWeb3Proof(
        'certify_vehicle',
        { vehicleId: vehicle.id, vin: vehicle.vin, force },
        (proof) => api.certifyVehicle(vehicle.id, { ...proof, force }, force)
      )
      return {
        vehicle: normalizeVehicle(response?.vehicle || vehicle),
        message: response?.message || (force ? 'Véhicule re-certifié on-chain' : 'Véhicule certifié on-chain'),
        blockchain_tx: response?.blockchain_tx,
        source: 'api'
      }
    } catch (error) {
      // Fallback sans preuve Web3
      const response = await api.certifyVehicle(vehicle.id, { force }, force)
      return {
        vehicle: normalizeVehicle(response?.vehicle || vehicle),
        message: response?.message || (force ? 'Véhicule re-certifié on-chain' : 'Véhicule certifié on-chain'),
        blockchain_tx: response?.blockchain_tx,
        source: 'api'
      }
    }
  }

  return {
    vehicle: { ...vehicle, blockchainVerified: true },
    message: force ? 'Re-certification locale simulée' : 'Certification locale simulée',
    source: 'local'
  }
}

export const resolveVehicle = (label, vehicles = []) => {
  if (!label) return null
  const normalized = String(label).trim().toLowerCase()
  return vehicles.find((v) => {
    const full = `${v.brand} ${v.model}`.toLowerCase()
    const plate = String(v.licensePlate || v.plate_number || '').toLowerCase()
    return full === normalized || normalized.includes(full) || plate === normalized || normalized.includes(plate)
  }) || null
}

export async function createMaintenanceHybrid(payload, vehicles = []) {
  const vehicle = payload.vehicleId
    ? vehicles.find((v) => v.id === payload.vehicleId)
    : resolveVehicle(payload.vehicle, vehicles)

  if (isApiToken() && vehicle?.id) {
    const response = await withWeb3Proof(
      'record_maintenance',
      { vehicleId: vehicle.id, description: payload.description },
      (proof) => api.createMaintenance(vehicle.id, {
        description: payload.description,
        parts_changed: payload.parts_changed || payload.description,
        cost: payload.cost ?? 0
      }, proof)
    )
    const record = response?.maintenance || response
    return {
      item: normalizeApiMaintenance(record, vehicle, true),
      message: response?.message || 'Intervention enregistrée on-chain',
      source: 'api'
    }
  }

  return {
    item: {
      id: Date.now(),
      vehicle: payload.vehicle,
      description: payload.description,
      mechanic: payload.mechanic || 'Garage AutoPlus',
      date: new Date().toLocaleDateString('fr-FR'),
      urgency: payload.urgency || 'normal',
      urgencyLabel: payload.urgency === 'urgent' ? 'Urgent' : payload.urgency === 'low' ? 'Planifié' : 'Normal',
      status: 'En attente',
      statusClass: 'pending',
      icon: 'fas fa-wrench',
      certified: false
    },
    message: 'Intervention créée (local)',
    source: 'local'
  }
}

export async function certifyMaintenanceHybrid(item, vehicles = []) {
  if (item?.certified) {
    return { message: 'Intervention déjà certifiée', source: 'api' }
  }

  if (isApiToken()) {
    if (item?.id && typeof item.id === 'number' && item.id < 1e12) {
      const response = await withWeb3Proof(
        'certify_maintenance',
        { maintenanceId: item.id, vehicleId: item.vehicleId },
        (proof) => api.certifyMaintenance(item.id, proof)
      )
      const vehicle = vehicles.find((v) => v.id === item.vehicleId) || resolveVehicle(item.vehicle, vehicles)
      return {
        item: normalizeApiMaintenance(response?.maintenance || item, vehicle, false),
        message: response?.message || 'Intervention certifiée on-chain',
        blockchain_tx: response?.blockchain_tx,
        source: 'api'
      }
    }

    const vehicle = item.vehicleId
      ? vehicles.find((v) => v.id === item.vehicleId)
      : resolveVehicle(item.vehicle, vehicles)

    if (vehicle?.id) {
      const created = await createMaintenanceHybrid({
        vehicleId: vehicle.id,
        vehicle: item.vehicle,
        description: item.description,
        parts_changed: item.parts_changed || item.description,
        cost: item.cost || 0
      }, vehicles)
      return {
        ...created,
        message: created.message || 'Intervention certifiée on-chain'
      }
    }

    throw new Error('Véhicule introuvable pour certification')
  }

  return {
    item: { ...item, certified: true },
    message: 'Certification locale simulée',
    source: 'local'
  }
}

export async function certifyDocumentHybrid(doc) {
  if (!doc?.id) throw new Error('Document invalide')

  if (isApiToken()) {
    const response = await withWeb3Proof(
      'certify_document',
      { documentId: doc.id },
      (proof) => api.certifyDocument(doc.id, proof)
    )
    return {
      doc: {
        ...doc,
        blockchainVerified: true,
        ipfs_hash: response?.document?.ipfs_hash
      },
      message: response?.message || 'Document certifié IPFS',
      ipfs_url: response?.ipfs_url,
      source: 'api'
    }
  }

  return {
    doc: { ...doc, blockchainVerified: true },
    message: 'Certification locale simulée',
    source: 'local'
  }
}

export async function updateMileageHybrid(vehicleId, mileage, vin = '') {
  if (isApiToken() && vehicleId) {
    const response = await withWeb3Proof(
      'update_mileage',
      { vehicleId, mileage, vin },
      (proof) => api.updateMileage(vehicleId, mileage, proof)
    )
    return { response, source: 'api' }
  }
  return { source: 'local' }
}

export const SOURCE_LABELS = {
  api: 'Synchronisé avec PostgreSQL',
  local: 'Mode local (cache navigateur)',
  hybrid: 'Données hybrides',
  role: 'Profil du rôle sélectionné'
}
