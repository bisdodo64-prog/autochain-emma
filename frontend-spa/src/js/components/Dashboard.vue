<template>
  <div class="dashboard animate-fade-in-up">
    <!-- Header -->
    <div class="dashboard-header">
      <div class="header-content">
        <div class="welcome-section">
          <h1 class="text-glow animate-fade-in-down">
            <i class="fas fa-tachometer-alt"></i> Tableau de Bord
          </h1>
          <p class="animate-fade-in-up" style="animation-delay: 0.1s">
            Bienvenue, <strong class="text-primary-400">{{ userName }}</strong>
            <span class="role-badge">{{ userRole }}</span>
          </p>
        </div>
        <div class="header-actions">
          <button class="btn-primary animate-float" @click="openAddVehicle" v-if="!isAuditeur">
            <i class="fas fa-plus"></i> Nouveau Véhicule
          </button>
        </div>
      </div>
    </div>

    <!-- Stats Charts -->
    <div class="stats-grid charts-grid animate-fade-in-up" style="animation-delay: 0.2s">
      <div class="chart-card" v-for="(chart, idx) in chartStats" :key="chart.label" :style="{ animationDelay: `${0.2 + idx * 0.08}s` }">
        <div class="donut" :style="{ background: chart.conic }">
          <div class="donut-hole">
            <span class="donut-value">{{ chart.value }}</span>
            <span class="donut-pct">{{ chart.percent }}%</span>
          </div>
        </div>
        <div class="chart-meta">
          <div class="chart-icon" :style="{ color: chart.color, background: chart.bg }">
            <i :class="chart.icon"></i>
          </div>
          <div>
            <div class="chart-label">{{ chart.label }}</div>
            <div class="chart-bar">
              <div class="chart-bar-fill" :style="{ width: chart.percent + '%', background: chart.color }"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="insight-banner animate-fade-in-up" style="animation-delay: 0.55s">
      <div class="insight-copy">
        <i class="fas fa-shield-alt"></i>
        <span>{{ SOURCE_LABELS[dataSource] || 'Chargement...' }}</span>
      </div>
      <button class="btn-secondary btn-small" @click="refreshDashboard" :class="{ spinning: refreshing }">
        <i class="fas fa-sync-alt" :class="{ 'fa-spin': refreshing }"></i> Actualiser
      </button>
    </div>

    <!-- SUPER ADMIN Actions -->
    <div v-if="isSuperAdmin" class="section-card card animate-fade-in-up" style="animation-delay: 0.6s">
      <div class="section-header">
        <h2 class="text-glow-purple">
          <i class="fas fa-bolt"></i> Actions Rapides
        </h2>
      </div>
      <div class="actions-grid">
        <button type="button" class="action-card group" @click="goTo('/admin')">
          <div class="action-icon animate-float">
            <i class="fas fa-users-cog"></i>
          </div>
          <span>Gérer Utilisateurs</span>
        </button>
        <button type="button" class="action-card group" @click="goTo('/blockchain')">
          <div class="action-icon animate-float">
            <i class="fas fa-cogs"></i>
          </div>
          <span>Smart Contract</span>
        </button>
        <button type="button" class="action-card group" @click="goTo('/audit')">
          <div class="action-icon animate-float">
            <i class="fas fa-search"></i>
          </div>
          <span>Audit Global</span>
        </button>
        <button type="button" class="action-card group" @click="goTo('/documents')">
          <div class="action-icon animate-float">
            <i class="fas fa-archive"></i>
          </div>
          <span>Archives</span>
        </button>
      </div>
    </div>

    <!-- GESTIONNAIRE Actions -->
    <div v-if="isGestionnaire" class="section-card card animate-fade-in-up" style="animation-delay: 0.6s">
      <div class="section-header">
        <h2 class="text-glow">
          <i class="fas fa-car-side"></i> Gestion de Flotte
        </h2>
      </div>
      <div class="manager-actions">
        <button class="btn-primary" @click="openAddVehicle">
          <i class="fas fa-plus"></i> Ajouter un Véhicule
        </button>
        <button class="btn-secondary" @click="goTo('/drivers')">
          <i class="fas fa-users"></i> Gérer Chauffeurs
        </button>
        <button class="btn-secondary" @click="goTo('/maintenance')">
          <i class="fas fa-tools"></i> Voir Maintenance
        </button>
        <button class="btn-secondary" @click="goTo('/documents')">
          <i class="fas fa-file-alt"></i> Documents
        </button>
      </div>
    </div>

    <!-- CHAUFFEUR Vehicle -->
    <div v-if="isChauffeur" class="section-card card animate-fade-in-up" style="animation-delay: 0.6s">
      <div class="driver-card">
        <div class="vehicle-icon animate-float">
          <i class="fas fa-car-side text-6xl"></i>
        </div>
        <div class="vehicle-info">
          <h2 class="text-glow">{{ myVehicle.brand }} {{ myVehicle.model }}</h2>
          <div class="vehicle-details">
            <span class="plate-badge">{{ myVehicle.licensePlate }}</span>
            <span class="mileage-badge">{{ formatKm(myVehicle.mileage) }} km</span>
            <span class="fuel-badge">Carburant: {{ myVehicle.fuelLevel }}%</span>
          </div>
          <div class="driver-actions">
            <button @click="startMission" class="btn-primary">
              <i class="fas fa-play"></i> Démarrer Mission
            </button>
            <button @click="openMileageModal" class="btn-secondary">
              <i class="fas fa-edit"></i> Relever Km
            </button>
            <button @click="goTo('/profile?role=chauffeur')" class="btn-secondary">
              <i class="fas fa-user"></i> Mon profil
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- GARAGISTE Interventions -->
    <div v-if="isGaragiste" class="section-card card animate-fade-in-up" style="animation-delay: 0.6s">
      <div class="section-header">
        <h2 class="text-glow">
          <i class="fas fa-clipboard-list"></i> Interventions
        </h2>
        <div class="header-btn-group">
          <button class="btn-secondary" @click="goTo('/profile?role=garagiste_agree')">
            <i class="fas fa-user"></i> Mon profil
          </button>
          <button class="btn-primary" @click="openAddVehicle">
            <i class="fas fa-plus"></i> Ajouter un Véhicule
          </button>
        </div>
      </div>
      <div class="interventions-list">
        <div v-for="inter in interventions" :key="inter.id" class="intervention-card group">
          <div class="intervention-info">
            <strong class="text-primary-400">{{ inter.vehicle }}</strong>
            <p class="text-slate-400">{{ inter.description }}</p>
            <span :class="'priority-badge ' + inter.urgency">{{ inter.urgencyLabel }}</span>
            <span class="inter-status">{{ inter.status || 'En attente' }}</span>
          </div>
          <div class="intervention-actions">
            <button
              v-if="(inter.status || 'En attente') === 'En attente'"
              @click="startIntervention(inter)"
              class="action-btn btn-start"
            >
              <i class="fas fa-play"></i> Démarrer
            </button>
            <button @click="addParts(inter)" class="action-btn btn-parts">
              <i class="fas fa-cog"></i> Pièces
            </button>
            <button @click="certifyIntervention(inter)" class="action-btn btn-chain" :disabled="certifyingId === inter.id">
              <i class="fas fa-cubes"></i> {{ certifyingId === inter.id ? '...' : 'Certifier' }}
            </button>
          </div>
          <div v-if="inter.parts?.length" class="parts-list">
            <span v-for="(p, i) in inter.parts" :key="i" class="part-chip">{{ p }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- AUDITEUR Verification -->
    <div v-if="isAuditeur" class="section-card card animate-fade-in-up" style="animation-delay: 0.6s">
      <div class="section-header">
        <h2 class="text-glow-purple">
          <i class="fas fa-search"></i> Vérification Blockchain
        </h2>
      </div>
      <div class="verify-section">
        <div class="verify-input-group">
          <i class="fas fa-search verify-icon"></i>
          <input v-model="vinToVerify" placeholder="VIN ou plaque d'immatriculation..." class="verify-input" />
          <button @click="verifyVehicle" class="btn-primary">
            <i class="fas fa-check"></i> Vérifier
          </button>
        </div>
        <div v-if="verificationResult" class="verification-result" :class="verificationResult.valid ? 'success' : 'error'">
          <i :class="verificationResult.valid ? 'fas fa-check-circle' : 'fas fa-times-circle'" class="result-icon"></i>
          <div class="result-content">
            <strong>{{ verificationResult.vehicle }}</strong>
            <p>{{ verificationResult.message }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Vehicles Table -->
    <div class="section-card card animate-fade-in-up" style="animation-delay: 0.7s">
      <div class="section-header">
        <h2 class="text-glow">
          <i class="fas fa-list"></i> Véhicules
        </h2>
        <button v-if="!isAuditeur" class="btn-primary" @click="openAddVehicle">
          <i class="fas fa-plus"></i> Ajouter
        </button>
      </div>
      <div class="search-wrapper">
        <i class="fas fa-search search-icon"></i>
        <input v-model="searchQuery" placeholder="Rechercher un véhicule..." class="search-input" />
      </div>
      <div class="table-container">
        <table class="data-table">
          <thead>
            <tr>
              <th>Véhicule</th>
              <th>Plaque</th>
              <th>Kilométrage</th>
              <th>Statut</th>
              <th>Blockchain</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="v in filteredVehicles" :key="v.id" class="table-row group">
              <td>
                <div class="vehicle-cell">
                  <div class="vehicle-avatar">
                    <i class="fas fa-car"></i>
                  </div>
                  <div>
                    <strong class="text-slate-100">{{ v.brand }} {{ v.model }}</strong>
                    <small class="text-slate-400">{{ v.year }}</small>
                  </div>
                </div>
              </td>
              <td><span class="plate-tag">{{ v.licensePlate }}</span></td>
              <td class="text-slate-300">{{ formatKm(v.mileage) }} km</td>
              <td><span :class="'status-badge ' + v.statusClass">{{ v.status }}</span></td>
              <td>
                <span :class="'blockchain-badge ' + (v.blockchainVerified ? 'verified' : 'pending')">
                  <i :class="v.blockchainVerified ? 'fas fa-check-circle' : 'fas fa-clock'"></i>
                  {{ v.blockchainVerified ? 'Certifié' : 'En attente' }}
                </span>
              </td>
              <td>
                <div class="action-buttons">
                  <button @click="goTo('/vehicle/'+v.id)" class="icon-btn group" title="Voir">
                    <i class="fas fa-eye group-hover:animate-pulse"></i>
                  </button>
                  <button @click="goTo('/vehicle/'+v.id+'/timeline')" class="icon-btn group" title="Timeline">
                    <i class="fas fa-history group-hover:animate-pulse"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- MODAL: Add Vehicle -->
    <div v-if="showAddVehicle" class="modal-overlay animate-fade-in" @click.self="showAddVehicle = false">
      <div class="modal glass-dark animate-slide-in-up">
        <div class="modal-header">
          <h3 class="text-glow">
            <i class="fas fa-plus-circle"></i> Ajouter un Véhicule
          </h3>
          <button @click="showAddVehicle = false" class="modal-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-grid">
            <div class="form-group">
              <label>Marque</label>
              <input v-model="newVehicle.brand" class="input-field" placeholder="Toyota, Renault...">
            </div>
            <div class="form-group">
              <label>Modèle</label>
              <input v-model="newVehicle.model" class="input-field" placeholder="Corolla, Clio...">
            </div>
            <div class="form-group">
              <label>Année</label>
              <input v-model="newVehicle.year" class="input-field" placeholder="2024">
            </div>
            <div class="form-group">
              <label>Plaque</label>
              <input v-model="newVehicle.licensePlate" class="input-field" placeholder="AA-123-BB">
            </div>
            <div class="form-group">
              <label>Kilométrage initial</label>
              <input v-model="newVehicle.mileage" type="number" class="input-field" placeholder="0">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="showAddVehicle = false" class="btn-secondary">Annuler</button>
          <button @click="saveVehicle" class="btn-primary">
            <i class="fas fa-save"></i> Enregistrer
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL: Mileage -->
    <div v-if="showMileage" class="modal-overlay animate-fade-in" @click.self="showMileage = false">
      <div class="modal glass-dark modal-sm animate-slide-in-up">
        <div class="modal-header">
          <h3 class="text-glow">
            <i class="fas fa-tachometer-alt"></i> Relever Kilométrage
          </h3>
          <button @click="showMileage = false" class="modal-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <p class="text-slate-300">Véhicule : <strong class="text-primary-400">{{ myVehicle.brand }} {{ myVehicle.model }}</strong></p>
          <div class="form-group">
            <label>Nouveau kilométrage</label>
            <input v-model="newMileage" type="number" class="input-field">
          </div>
        </div>
        <div class="modal-footer">
          <button @click="showMileage = false" class="btn-secondary">Annuler</button>
          <button @click="saveMileage" class="btn-primary">
            <i class="fas fa-check"></i> Enregistrer
          </button>
        </div>
      </div>
    </div>

    <!-- TOAST Notification -->
    <div v-if="toast.show" class="toast animate-slide-in-right" :class="toast.type">
      <i :class="toast.icon"></i>
      <span>{{ toast.message }}</span>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useStore } from 'vuex'
import { useRouter } from 'vue-router'
import api from '../api'
import { getRoleLabel } from '../utils/roles'
import { normalizeVehicle } from '../utils/vehicles'
import { loadVehicles, saveVehicles } from '../utils/localData'
import { fetchVehiclesHybrid, fetchDashboardInterventionsHybrid, createVehicleHybrid, certifyMaintenanceHybrid, updateMileageHybrid, SOURCE_LABELS, isLocalToken } from '../utils/dataService'

export default {
  name: 'Dashboard',
  setup() {
    const store = useStore()
    const router = useRouter()
    const searchQuery = ref('')
    const vinToVerify = ref('')
    const verificationResult = ref(null)
    const showAddVehicle = ref(false)
    const showMileage = ref(false)
    const newMileage = ref(0)
    
    const newVehicle = ref({ brand: '', model: '', year: '', licensePlate: '', mileage: 0 })
    
    const toast = ref({ show: false, message: '', type: 'success', icon: 'fas fa-check-circle' })

    const showToast = (message, type = 'success') => {
      toast.value = {
        show: true,
        message,
        type,
        icon: type === 'success' ? 'fas fa-check-circle' : type === 'error' ? 'fas fa-times-circle' : 'fas fa-info-circle'
      }
      setTimeout(() => toast.value.show = false, 3000)
    }

    const normalizeVehicleRow = (vehicle) => normalizeVehicle(vehicle)

    const userName = computed(() => store.state.auth?.user?.name || 'Utilisateur')
    const userRole = computed(() => {
      const roles = store.state.auth?.user?.roles
      if (!roles?.length) return 'Visiteur'
      return getRoleLabel(roles[0]?.name)
    })
    const viewRole = computed(() => store.state.auth?.user?.roles?.[0]?.name)
    const isSuperAdmin = computed(() => viewRole.value === 'super_admin')
    const isGestionnaire = computed(() => viewRole.value === 'gestionnaire_parc')
    const isChauffeur = computed(() => viewRole.value === 'chauffeur')
    const isGaragiste = computed(() => viewRole.value === 'garagiste_agree')
    const isAuditeur = computed(() => viewRole.value === 'auditeur')

    const vehicles = ref(loadVehicles())
    const interventions = ref([])
    const dataSource = ref('local')
    const myVehicle = ref({ brand: 'Renault', model: 'Clio', licensePlate: 'CC-456-DD', mileage: 78000, fuelLevel: 65 })

    const persistVehicles = () => saveVehicles(vehicles.value)

    const loadInitialData = async () => {
      const vehiclesResult = await fetchVehiclesHybrid()
      vehicles.value = vehiclesResult.data
      dataSource.value = vehiclesResult.source

      const interventionsResult = await fetchDashboardInterventionsHybrid()
      interventions.value = interventionsResult.data

      const missionVehicle = vehiclesResult.data.find((v) => v.statusClass === 'mission' || v.status === 'En mission')
      if (missionVehicle) {
        myVehicle.value = {
          brand: missionVehicle.brand,
          model: missionVehicle.model,
          licensePlate: missionVehicle.licensePlate,
          mileage: missionVehicle.mileage,
          fuelLevel: 65
        }
      }
    }

    const stats = computed(() => ({
      available: vehicles.value.filter(v => v.status === 'Disponible').length,
      inMission: vehicles.value.filter(v => v.status === 'En mission').length,
      inMaintenance: vehicles.value.filter(v => v.status === 'En maintenance').length,
      blockchainCertified: vehicles.value.filter(v => v.blockchainVerified).length,
    }))

    const chartStats = computed(() => {
      const total = Math.max(vehicles.value.length, 1)
      const items = [
        { label: 'Disponibles', value: stats.value.available, color: '#34d399', bg: 'rgba(52,211,153,.15)', icon: 'fas fa-check-circle' },
        { label: 'En Mission', value: stats.value.inMission, color: '#60a5fa', bg: 'rgba(96,165,250,.15)', icon: 'fas fa-truck' },
        { label: 'Maintenance', value: stats.value.inMaintenance, color: '#fbbf24', bg: 'rgba(251,191,36,.15)', icon: 'fas fa-tools' },
        { label: 'Blockchain', value: stats.value.blockchainCertified, color: '#a78bfa', bg: 'rgba(167,139,250,.15)', icon: 'fas fa-cubes' }
      ]
      return items.map((item) => {
        const percent = Math.round((item.value / total) * 100)
        return {
          ...item,
          percent,
          conic: `conic-gradient(${item.color} 0% ${percent}%, rgba(148,163,184,0.2) ${percent}% 100%)`
        }
      })
    })

    const refreshing = ref(false)
    const refreshDashboard = async () => {
      refreshing.value = true
      await loadInitialData()
      showToast('✅ Données du tableau de bord mises à jour')
      setTimeout(() => { refreshing.value = false }, 600)
    }

    const filteredVehicles = computed(() => {
      if (!searchQuery.value) return vehicles.value
      const q = searchQuery.value.toLowerCase()
      return vehicles.value.filter(v =>
        (v.brand || '').toLowerCase().includes(q) ||
        (v.model || '').toLowerCase().includes(q) ||
        (v.licensePlate || '').toLowerCase().includes(q)
      )
    })

    const formatKm = n => n?.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ') || '0'
    const goTo = (path) => router.push(path)

    const openAddVehicle = () => {
      newVehicle.value = { brand: '', model: '', year: '', licensePlate: '', mileage: 0 }
      showAddVehicle.value = true
    }

    const saveVehicle = async () => {
      if (!newVehicle.value.brand || !newVehicle.value.model) {
        showToast('Veuillez remplir la marque et le modèle', 'error')
        return
      }

      const fallbackVehicle = normalizeVehicleRow({
        id: Date.now(),
        brand: newVehicle.value.brand,
        model: newVehicle.value.model,
        year: newVehicle.value.year || '2024',
        plate_number: newVehicle.value.licensePlate,
        current_mileage: parseInt(newVehicle.value.mileage) || 0,
        status: 'available'
      })

      try {
        const created = await createVehicleHybrid(newVehicle.value)
        vehicles.value.unshift(created)
        persistVehicles()
        showToast('✅ Véhicule ' + created.brand + ' ' + created.model + ' ajouté avec succès !')
      } catch {
        vehicles.value.unshift(fallbackVehicle)
        persistVehicles()
        showToast('✅ Véhicule ' + fallbackVehicle.brand + ' ' + fallbackVehicle.model + ' ajouté (local) !')
      }

      showAddVehicle.value = false
    }

    const startMission = () => {
      const v = vehicles.value.find(v => v.licensePlate === myVehicle.value.licensePlate)
      if (v) {
        v.status = 'En mission'
        v.statusClass = 'mission'
      }
      showToast('🚗 Mission démarrée pour ' + myVehicle.value.brand + ' ' + myVehicle.value.model + ' !')
    }

    const openMileageModal = () => {
      newMileage.value = myVehicle.value.mileage
      showMileage.value = true
    }

    const saveMileage = async () => {
      if (newMileage.value < myVehicle.value.mileage) {
        showToast('Le kilométrage ne peut pas diminuer !', 'error')
        return
      }

      const nextMileage = parseInt(newMileage.value)
      const vehicleMatch = vehicles.value.find(v => v.licensePlate === myVehicle.value.licensePlate)

      try {
        if (vehicleMatch?.id) {
          await updateMileageHybrid(vehicleMatch.id, nextMileage, vehicleMatch.vin)
        }
      } catch (error) {
        showToast(error?.message || 'Mise à jour kilométrage échouée', 'error')
        return
      }

      myVehicle.value.mileage = nextMileage
      if (vehicleMatch) {
        vehicleMatch.mileage = nextMileage
      }
      persistVehicles()
      showMileage.value = false
      showToast('📏 Kilométrage enregistré : ' + formatKm(myVehicle.value.mileage) + ' km')
    }

    const startIntervention = (inter) => {
      inter.status = 'En cours'
      showToast('🔧 Intervention démarrée sur ' + inter.vehicle + ' - ' + inter.description, 'info')
    }

    const addParts = (inter) => {
      const part = window.prompt('Pièce à ajouter pour ' + inter.vehicle + ' :', 'Filtre à huile')
      if (!part) return
      if (!inter.parts) inter.parts = []
      inter.parts.push(part)
      showToast('⚙️ Pièce "' + part + '" ajoutée à l\'intervention ' + inter.vehicle)
    }

    const certifyingId = ref(null)

    const certifyIntervention = async (inter) => {
      certifyingId.value = inter.id
      try {
        const result = await certifyMaintenanceHybrid(inter, vehicles.value)
        if (result.source === 'api') {
          const refreshed = await fetchDashboardInterventionsHybrid()
          interventions.value = refreshed.data
        } else if (result.item) {
          inter.certified = true
          inter.status = 'Certifié'
        }
        showToast('🔗 ' + (result.message || 'Intervention certifiée on-chain'))
      } catch (err) {
        showToast(err?.response?.data?.message || err.message || 'Certification échouée', 'error')
      } finally {
        certifyingId.value = null
      }
    }

    const verifyVehicle = async () => {
      if (!vinToVerify.value) {
        showToast('Veuillez saisir un VIN ou une plaque.', 'error')
        return
      }
      if (!isLocalToken()) {
        try {
          const result = await api.verifyVehicle(vinToVerify.value)
          verificationResult.value = {
            valid: Boolean(result?.valid ?? result?.found),
            vehicle: result?.vehicle || result?.label || '—',
            message: result?.message || (result?.valid ? '✅ Véhicule authentifié via l\'API' : '❌ Non trouvé')
          }
          return
        } catch {
          // fallback local
        }
      }
      const found = vehicles.value.find(v => v.licensePlate.toLowerCase() === vinToVerify.value.toLowerCase() || v.vin?.toLowerCase() === vinToVerify.value.toLowerCase())
      if (found) {
        verificationResult.value = {
          valid: true,
          vehicle: found.brand + ' ' + found.model + ' (' + found.licensePlate + ')',
          message: '✅ Authentifié. Kilométrage certifié : ' + formatKm(found.mileage) + ' km. Blockchain : ' + (found.blockchainVerified ? 'Certifié' : 'En attente')
        }
      } else {
        verificationResult.value = { valid: false, vehicle: 'Inconnu', message: '❌ Aucun véhicule trouvé avec cet identifiant.' }
      }
    }

    onMounted(() => {
      loadInitialData()
    })

    return {
      searchQuery, vinToVerify, verificationResult,
      userName, userRole,
      isSuperAdmin, isGestionnaire, isChauffeur, isGaragiste, isAuditeur,
      vehicles, myVehicle, interventions, stats, chartStats, filteredVehicles, dataSource, SOURCE_LABELS,
      showAddVehicle, showMileage, newVehicle, newMileage, toast, refreshing,
      formatKm, goTo, openAddVehicle, saveVehicle, startMission, openMileageModal, saveMileage,
      startIntervention, addParts, certifyIntervention, verifyVehicle, refreshDashboard, certifyingId
    }
  }
}
</script>

<style scoped>
.dashboard {
  padding: 0;
  max-width: 100%;
}

/* Header */
.dashboard-header {
  margin-bottom: 32px;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 24px;
}

.selected-role-banner {
  margin-top: 16px;
  padding: 14px 18px;
  border-radius: 16px;
  background: rgba(56, 189, 248, 0.1);
  border: 1px solid rgba(56, 189, 248, 0.2);
  color: #e2e8f0;
  display: inline-flex;
  flex-direction: column;
  gap: 4px;
}

.selected-role-banner strong {
  color: #a5b4fc;
}

.welcome-section h1 {
  font-size: 28px;
  font-weight: 700;
  color: #f8fafc;
  margin-bottom: 8px;
}

.welcome-section h1 i {
  margin-right: 12px;
  color: #38bdf8;
}

.welcome-section p {
  font-size: 14px;
  color: #94a3b8;
}

.role-badge {
  background: linear-gradient(135deg, #38bdf8, #a855f7);
  color: white;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 600;
  margin-left: 8px;
}

/* Stats Grid — graphiques */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 20px;
  margin-bottom: 32px;
}

.charts-grid .chart-card {
  background: rgba(30, 41, 59, 0.9);
  border: 1px solid rgba(148, 163, 184, 0.15);
  border-radius: 18px;
  padding: 18px;
  display: flex;
  align-items: center;
  gap: 16px;
  animation: chartIn 0.55s ease both;
  transition: transform 0.3s, box-shadow 0.3s;
}

.chart-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 30px rgba(14, 165, 233, 0.15);
}

.donut {
  width: 84px;
  height: 84px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  animation: donutSpin 0.8s ease;
}

.donut-hole {
  width: 58px;
  height: 58px;
  border-radius: 50%;
  background: #0f172a;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.donut-value {
  font-size: 18px;
  font-weight: 800;
  color: #f8fafc;
  line-height: 1;
}

.donut-pct {
  font-size: 10px;
  color: #94a3b8;
}

.chart-meta {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  flex: 1;
}

.chart-icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.chart-label {
  font-size: 13px;
  font-weight: 600;
  color: #e2e8f0;
  margin-bottom: 8px;
}

.chart-bar {
  height: 6px;
  width: 100%;
  min-width: 90px;
  background: rgba(148, 163, 184, 0.2);
  border-radius: 999px;
  overflow: hidden;
}

.chart-bar-fill {
  height: 100%;
  border-radius: 999px;
  animation: barGrow 0.9s ease;
}

@keyframes chartIn {
  from { opacity: 0; transform: translateY(12px) scale(0.96); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}

@keyframes donutSpin {
  from { transform: rotate(-90deg) scale(0.8); opacity: 0; }
  to { transform: rotate(0) scale(1); opacity: 1; }
}

@keyframes barGrow {
  from { width: 0 !important; }
}

.stat-card {
  padding: 24px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  gap: 16px;
  position: relative;
  overflow: hidden;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.stat-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: linear-gradient(90deg, transparent, rgba(56, 189, 248, 0.5), transparent);
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
}

.stat-icon-wrapper {
  width: 56px;
  height: 56px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 14px;
  font-size: 24px;
  transition: all 0.3s;
}

.stat-card:hover .stat-icon-wrapper {
  transform: scale(1.1);
  box-shadow: 0 0 30px rgba(56, 189, 248, 0.4);
}

.stat-content {
  flex: 1;
}

.stat-value {
  font-size: 32px;
  font-weight: 700;
  color: #f8fafc;
  line-height: 1;
  margin-bottom: 4px;
}

.stat-label {
  font-size: 13px;
  color: #94a3b8;
  font-weight: 500;
}

.stat-trend {
  font-size: 12px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 4px;
}

/* Insight Banner */
.insight-banner {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  padding: 16px 20px;
  border-radius: 16px;
  background: linear-gradient(135deg, rgba(15, 23, 42, 0.7), rgba(30, 41, 59, 0.9));
  border: 1px solid rgba(56, 189, 248, 0.18);
  margin-bottom: 24px;
}

.insight-copy {
  display: flex;
  align-items: center;
  gap: 10px;
  color: #e2e8f0;
  font-size: 14px;
}

.insight-copy i {
  color: #38bdf8;
}

.btn-small {
  padding: 8px 12px;
  font-size: 13px;
}

/* Section Cards */
.btn-primary,
.btn-secondary,
.btn-danger,
.icon-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 10px 16px;
  border-radius: 12px;
  border: 1px solid transparent;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-primary:hover,
.btn-secondary:hover,
.icon-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(56, 189, 248, 0.18);
}

.btn-primary {
  color: #f8fafc;
  background: linear-gradient(135deg, #38bdf8, #6366f1);
  border-color: rgba(56, 189, 248, 0.4);
}

.btn-primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 12px 28px rgba(56, 189, 248, 0.18);
}

.btn-secondary {
  color: #cbd5e1;
  background: rgba(255, 255, 255, 0.06);
  border-color: rgba(148, 163, 184, 0.25);
}

.btn-secondary:hover {
  background: rgba(255, 255, 255, 0.1);
}

.btn-danger {
  color: #f8fafc;
  background: linear-gradient(135deg, #ef4444, #dc2626);
  border-color: rgba(239, 68, 68, 0.35);
}

.btn-danger:hover {
  box-shadow: 0 12px 28px rgba(239, 68, 68, 0.2);
}

.icon-btn {
  width: 38px;
  height: 38px;
  padding: 0;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(56, 189, 248, 0.12);
  border-radius: 12px;
  color: #38bdf8;
}

.icon-btn:hover {
  background: rgba(56, 189, 248, 0.12);
}

.section-card {
  margin-bottom: 24px;
  padding: 24px;
  border-radius: 16px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.section-header h2 {
  font-size: 18px;
  font-weight: 600;
  color: #f8fafc;
}

.section-header h2 i {
  margin-right: 10px;
  color: #38bdf8;
}

/* Actions Grid */
.actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 16px;
}

.action-card {
  appearance: none;
  width: 100%;
  padding: 20px;
  border-radius: 14px;
  background: linear-gradient(135deg, rgba(56, 189, 248, 0.18), rgba(129, 140, 248, 0.16));
  border: 1px solid rgba(96, 165, 250, 0.35);
  text-align: center;
  cursor: pointer;
  color: #f8fafc;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.06);
}

.action-card:hover {
  background: linear-gradient(135deg, rgba(34, 211, 238, 0.24), rgba(129, 140, 248, 0.22));
  border-color: rgba(34, 211, 238, 0.45);
  transform: translateY(-4px);
  box-shadow: 0 12px 30px rgba(34, 211, 238, 0.18);
}

.action-icon {
  font-size: 28px;
  color: #f8fafc;
  margin-bottom: 12px;
  display: block;
}

.action-card span {
  font-size: 13px;
  font-weight: 600;
  color: #e2e8f0;
}

/* Manager Actions */
.manager-actions {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

/* Driver Card */
.driver-card {
  display: flex;
  gap: 24px;
  align-items: center;
}

.vehicle-icon {
  width: 80px;
  height: 80px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, rgba(56, 189, 248, 0.1), rgba(168, 85, 247, 0.1));
  border-radius: 20px;
  color: #38bdf8;
}

.vehicle-info h2 {
  font-size: 20px;
  font-weight: 600;
  color: #f8fafc;
  margin-bottom: 12px;
}

.vehicle-details {
  display: flex;
  gap: 12px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.plate-badge,
.mileage-badge,
.fuel-badge {
  padding: 6px 12px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 500;
}

.plate-badge {
  background: rgba(56, 189, 248, 0.1);
  color: #38bdf8;
  border: 1px solid rgba(56, 189, 248, 0.3);
  font-family: monospace;
}

.mileage-badge {
  background: rgba(16, 185, 129, 0.1);
  color: #10b981;
  border: 1px solid rgba(16, 185, 129, 0.3);
}

.fuel-badge {
  background: rgba(245, 158, 11, 0.1);
  color: #f59e0b;
  border: 1px solid rgba(245, 158, 11, 0.3);
}

.driver-actions {
  display: flex;
  gap: 12px;
}

/* Interventions */
.interventions-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.header-btn-group {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.inter-status {
  display: inline-block;
  margin-left: 8px;
  padding: 3px 8px;
  border-radius: 999px;
  font-size: 11px;
  background: rgba(148, 163, 184, 0.15);
  color: #cbd5e1;
}

.parts-list {
  width: 100%;
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 8px;
}

.part-chip {
  padding: 4px 8px;
  border-radius: 999px;
  background: rgba(56, 189, 248, 0.15);
  color: #7dd3fc;
  font-size: 11px;
}

.intervention-card {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  border-radius: 12px;
  background: rgba(56, 189, 248, 0.05);
  border: 1px solid rgba(56, 189, 248, 0.1);
  transition: all 0.3s;
  flex-wrap: wrap;
  gap: 10px;
}

.intervention-card:hover {
  background: rgba(56, 189, 248, 0.1);
  border-color: rgba(56, 189, 248, 0.3);
  transform: translateX(4px);
}

.intervention-info strong {
  font-size: 14px;
  color: #f8fafc;
  display: block;
  margin-bottom: 4px;
}

.intervention-info p {
  font-size: 13px;
  color: #94a3b8;
  margin-bottom: 8px;
}

.priority-badge {
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 600;
}

.priority-badge.urgent {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
  border: 1px solid rgba(239, 68, 68, 0.3);
}

.priority-badge.normal {
  background: rgba(56, 189, 248, 0.1);
  color: #38bdf8;
  border: 1px solid rgba(56, 189, 248, 0.3);
}

.priority-badge.low {
  background: rgba(148, 163, 184, 0.1);
  color: #94a3b8;
  border: 1px solid rgba(148, 163, 184, 0.3);
}

.intervention-actions {
  display: flex;
  gap: 8px;
}

.action-btn {
  padding: 8px 12px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s;
  border: none;
}

.btn-start {
  background: rgba(56, 189, 248, 0.1);
  color: #38bdf8;
  border: 1px solid rgba(56, 189, 248, 0.3);
}

.btn-start:hover {
  background: rgba(56, 189, 248, 0.2);
  box-shadow: 0 0 20px rgba(56, 189, 248, 0.3);
}

.btn-parts {
  background: rgba(148, 163, 184, 0.1);
  color: #94a3b8;
  border: 1px solid rgba(148, 163, 184, 0.3);
}

.btn-parts:hover {
  background: rgba(148, 163, 184, 0.2);
}

.btn-chain {
  background: rgba(168, 85, 247, 0.1);
  color: #a855f7;
  border: 1px solid rgba(168, 85, 247, 0.3);
}

.btn-chain:hover {
  background: rgba(168, 85, 247, 0.2);
  box-shadow: 0 0 20px rgba(168, 85, 247, 0.3);
}

/* Verify Section */
.verify-section {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.verify-input-group {
  display: flex;
  gap: 12px;
  align-items: center;
}

.verify-icon {
  color: #94a3b8;
  font-size: 16px;
}

.verify-input {
  flex: 1;
  padding: 12px 16px;
  background: rgba(15, 23, 42, 0.5);
  border: 1px solid rgba(56, 189, 248, 0.2);
  border-radius: 12px;
  color: #f8fafc;
  font-size: 14px;
  transition: all 0.3s;
}

.verify-input:focus {
  outline: none;
  border-color: #38bdf8;
  box-shadow: 0 0 20px rgba(56, 189, 248, 0.3);
}

.verify-input::placeholder {
  color: #64748b;
}

.verification-result {
  padding: 16px;
  border-radius: 12px;
  display: flex;
  gap: 12px;
  align-items: flex-start;
}

.verification-result.success {
  background: rgba(16, 185, 129, 0.1);
  border: 1px solid rgba(16, 185, 129, 0.3);
}

.verification-result.error {
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.3);
}

.result-icon {
  font-size: 24px;
}

.verification-result.success .result-icon {
  color: #10b981;
}

.verification-result.error .result-icon {
  color: #ef4444;
}

.result-content strong {
  font-size: 14px;
  color: #f8fafc;
  display: block;
  margin-bottom: 4px;
}

.result-content p {
  font-size: 13px;
  color: #94a3b8;
}

/* Search */
.search-wrapper {
  position: relative;
  margin-bottom: 20px;
}

.search-icon {
  position: absolute;
  left: 16px;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  font-size: 14px;
}

.search-input {
  width: 100%;
  padding: 12px 16px 12px 44px;
  background: rgba(15, 23, 42, 0.5);
  border: 1px solid rgba(56, 189, 248, 0.2);
  border-radius: 12px;
  color: #f8fafc;
  font-size: 14px;
  transition: all 0.3s;
}

.search-input:focus {
  outline: none;
  border-color: #38bdf8;
  box-shadow: 0 0 20px rgba(56, 189, 248, 0.3);
}

.search-input::placeholder {
  color: #64748b;
}

/* Table */
.table-container {
  overflow-x: auto;
  border-radius: 12px;
  border: 1px solid rgba(56, 189, 248, 0.1);
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th {
  text-align: left;
  padding: 16px;
  font-size: 12px;
  font-weight: 600;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-bottom: 1px solid rgba(56, 189, 248, 0.1);
  background: rgba(15, 23, 42, 0.5);
}

.data-table td {
  padding: 16px;
  border-bottom: 1px solid rgba(56, 189, 248, 0.1);
  font-size: 14px;
}

.vehicle-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}

.vehicle-avatar {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, rgba(56, 189, 248, 0.1), rgba(168, 85, 247, 0.1));
  border-radius: 10px;
  color: #38bdf8;
  font-size: 16px;
}

.plate-tag {
  background: rgba(56, 189, 248, 0.1);
  color: #38bdf8;
  padding: 4px 10px;
  border-radius: 6px;
  font-family: monospace;
  font-weight: 600;
  font-size: 12px;
  border: 1px solid rgba(56, 189, 248, 0.3);
}

.status-badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 600;
}

.status-badge.available {
  background: rgba(16, 185, 129, 0.1);
  color: #10b981;
  border: 1px solid rgba(16, 185, 129, 0.3);
}

.status-badge.mission {
  background: rgba(56, 189, 248, 0.1);
  color: #38bdf8;
  border: 1px solid rgba(56, 189, 248, 0.3);
}

.status-badge.maintenance {
  background: rgba(245, 158, 11, 0.1);
  color: #f59e0b;
  border: 1px solid rgba(245, 158, 11, 0.3);
}

.blockchain-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 500;
}

.blockchain-badge.verified {
  background: rgba(16, 185, 129, 0.1);
  color: #10b981;
  border: 1px solid rgba(16, 185, 129, 0.3);
}

.blockchain-badge.pending {
  background: rgba(245, 158, 11, 0.1);
  color: #f59e0b;
  border: 1px solid rgba(245, 158, 11, 0.3);
}

.action-buttons {
  display: flex;
  gap: 8px;
}

.icon-btn {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(56, 189, 248, 0.1);
  border: 1px solid rgba(56, 189, 248, 0.2);
  border-radius: 8px;
  color: #38bdf8;
  cursor: pointer;
  transition: all 0.3s;
}

.icon-btn:hover {
  background: rgba(56, 189, 248, 0.2);
  border-color: rgba(56, 189, 248, 0.4);
  box-shadow: 0 0 15px rgba(56, 189, 248, 0.3);
  transform: translateY(-2px);
}

/* Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.7);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 20px;
}

.modal {
  width: 100%;
  max-width: 500px;
  border-radius: 20px;
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
}

.modal-sm {
  max-width: 400px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px;
  border-bottom: 1px solid rgba(56, 189, 248, 0.1);
}

.modal-header h3 {
  font-size: 18px;
  font-weight: 600;
  color: #f8fafc;
  margin: 0;
}

.modal-header h3 i {
  margin-right: 10px;
  color: #38bdf8;
}

.modal-close {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.3);
  border-radius: 8px;
  color: #ef4444;
  cursor: pointer;
  transition: all 0.3s;
}

.modal-close:hover {
  background: rgba(239, 68, 68, 0.2);
  box-shadow: 0 0 15px rgba(239, 68, 68, 0.3);
}

.modal-body {
  padding: 24px;
}

.modal-footer {
  padding: 16px 24px;
  border-top: 1px solid rgba(56, 189, 248, 0.1);
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.form-group {
  margin-bottom: 16px;
}

.form-group label {
  display: block;
  font-size: 12px;
  color: #94a3b8;
  margin-bottom: 8px;
  font-weight: 500;
}

/* Toast */
.toast {
  position: fixed;
  bottom: 32px;
  right: 32px;
  padding: 16px 24px;
  border-radius: 12px;
  color: white;
  font-weight: 500;
  z-index: 99999;
  display: flex;
  align-items: center;
  gap: 12px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
}

.toast.success {
  background: linear-gradient(135deg, #10b981, #059669);
}

.toast.error {
  background: linear-gradient(135deg, #ef4444, #dc2626);
}

.toast.info {
  background: linear-gradient(135deg, #38bdf8, #0284c7);
}

@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .header-content {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .form-grid {
    grid-template-columns: 1fr;
  }
  
  .driver-card {
    flex-direction: column;
    text-align: center;
  }
  
  .vehicle-details {
    justify-content: center;
  }
  
  .intervention-card {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }
  
  .intervention-actions {
    width: 100%;
    justify-content: space-between;
  }
}
</style>