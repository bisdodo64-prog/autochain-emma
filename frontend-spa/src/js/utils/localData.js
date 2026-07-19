import { normalizeVehicle } from './vehicles'
import { DEMO_VEHICLES, DEMO_INTERVENTIONS, DEMO_DRIVERS } from './demoData'

export const STORAGE_KEYS = {
  vehicles: 'dashboard_vehicles',
  drivers: 'autochain_drivers',
  documents: 'autochain_documents',
  maintenance: 'maintenance_interventions',
  maintenanceHistory: 'maintenance_history'
}

export const getDefaultVehicles = () => DEMO_VEHICLES.map((v) => normalizeVehicle(v))

export const loadVehicles = () => {
  try {
    const raw = localStorage.getItem(STORAGE_KEYS.vehicles)
    if (raw) {
      const parsed = JSON.parse(raw)
      if (Array.isArray(parsed) && parsed.length) return parsed
    }
  } catch { /* ignore */ }
  const defaults = getDefaultVehicles()
  localStorage.setItem(STORAGE_KEYS.vehicles, JSON.stringify(defaults))
  return defaults
}

export const saveVehicles = (vehicles) => {
  localStorage.setItem(STORAGE_KEYS.vehicles, JSON.stringify(vehicles))
}

export const loadDrivers = () => {
  try {
    const raw = localStorage.getItem(STORAGE_KEYS.drivers)
    if (raw) return JSON.parse(raw)
  } catch { /* ignore */ }
  localStorage.setItem(STORAGE_KEYS.drivers, JSON.stringify(DEMO_DRIVERS))
  return DEMO_DRIVERS
}

export const saveDrivers = (drivers) => {
  localStorage.setItem(STORAGE_KEYS.drivers, JSON.stringify(drivers))
}

export const DEFAULT_DOCUMENTS = [
  { id: 1, name: 'Carte Grise', vehicle: 'Toyota Corolla', type: 'Carte grise', expiry: 'Permanent', expiryClass: 'ok', icon: 'fas fa-id-card', color: 'rgba(59,130,246,.2)', blockchainVerified: true },
  { id: 2, name: 'Assurance 2026', vehicle: 'Renault Clio', type: 'Assurance', expiry: '31/12/2026', expiryClass: 'ok', icon: 'fas fa-shield-alt', color: 'rgba(16,185,129,.2)', blockchainVerified: true },
  { id: 3, name: 'Contrôle Technique', vehicle: 'Peugeot 308', type: 'Contrôle technique', expiry: '15/08/2026', expiryClass: 'warning', icon: 'fas fa-clipboard-check', color: 'rgba(245,158,11,.2)', blockchainVerified: false },
  { id: 4, name: 'Facture Révision', vehicle: 'BMW Serie 3', type: 'Facture', expiry: 'N/A', expiryClass: 'ok', icon: 'fas fa-file-invoice', color: 'rgba(124,58,237,.2)', blockchainVerified: true },
  { id: 5, name: 'Carte Grise', vehicle: 'Citroën C3', type: 'Carte grise', expiry: 'Permanent', expiryClass: 'ok', icon: 'fas fa-id-card', color: 'rgba(59,130,246,.2)', blockchainVerified: true }
]

export const loadDocuments = () => {
  try {
    const raw = localStorage.getItem(STORAGE_KEYS.documents)
    if (raw) return JSON.parse(raw)
  } catch { /* ignore */ }
  localStorage.setItem(STORAGE_KEYS.documents, JSON.stringify(DEFAULT_DOCUMENTS))
  return DEFAULT_DOCUMENTS
}

export const saveDocuments = (docs) => {
  localStorage.setItem(STORAGE_KEYS.documents, JSON.stringify(docs))
}

export const DEFAULT_MAINTENANCE = [
  { id: 1, vehicle: 'Peugeot 308', description: 'Vidange moteur + remplacement filtres', mechanic: 'Garage AutoPlus', date: '06/07/2026', urgency: 'urgent', urgencyLabel: 'Urgent', status: 'En attente', statusClass: 'pending', icon: 'fas fa-oil-can' },
  { id: 2, vehicle: 'Citroën C3', description: 'Changement plaquettes de frein avant', mechanic: 'Garage AutoPlus', date: '10/07/2026', urgency: 'normal', urgencyLabel: 'Normal', status: 'En cours', statusClass: 'progress', icon: 'fas fa-wrench' },
  { id: 3, vehicle: 'Toyota Corolla', description: 'Révision complète 45 000 km', mechanic: 'Garage Martin', date: '20/07/2026', urgency: 'low', urgencyLabel: 'Planifié', status: 'En attente', statusClass: 'pending', icon: 'fas fa-clipboard-check' }
]

export const DEFAULT_MAINTENANCE_HISTORY = [
  { id: 1, vehicle: 'Renault Clio', type: 'Vidange', date: '01/06/2026', mechanic: 'Garage AutoPlus', cost: 180, certified: true },
  { id: 2, vehicle: 'BMW Serie 3', type: 'Pneus', date: '15/05/2026', mechanic: 'Garage Martin', cost: 450, certified: true },
  { id: 3, vehicle: 'Audi A4', type: 'Freins', date: '20/04/2026', mechanic: 'Garage AutoPlus', cost: 320, certified: false }
]

export const loadMaintenance = () => {
  try {
    const raw = localStorage.getItem(STORAGE_KEYS.maintenance)
    if (raw) return JSON.parse(raw)
  } catch { /* ignore */ }
  localStorage.setItem(STORAGE_KEYS.maintenance, JSON.stringify(DEFAULT_MAINTENANCE))
  return DEFAULT_MAINTENANCE
}

export const loadMaintenanceHistory = () => {
  try {
    const raw = localStorage.getItem(STORAGE_KEYS.maintenanceHistory)
    if (raw) return JSON.parse(raw)
  } catch { /* ignore */ }
  localStorage.setItem(STORAGE_KEYS.maintenanceHistory, JSON.stringify(DEFAULT_MAINTENANCE_HISTORY))
  return DEFAULT_MAINTENANCE_HISTORY
}

export { DEMO_INTERVENTIONS }
