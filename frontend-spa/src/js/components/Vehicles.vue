<template>
  <div class="vehicles-page animate-fade-in-up">
    <!-- Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="title-section">
          <h1 class="text-glow animate-fade-in-down">
            <i class="fas fa-car-side"></i> Gestion des Véhicules
          </h1>
          <p class="animate-fade-in-up" style="animation-delay: 0.1s">
            Gérez votre flotte automobile avec traçabilité blockchain
          </p>
        </div>
        <div class="header-actions">
          <button class="btn-secondary btn-styled" @click="refreshVehicles" :disabled="refreshing">
            <i class="fas fa-sync-alt" :class="{ 'fa-spin': refreshing }"></i>
            {{ refreshing ? 'Actualisation…' : 'Actualiser' }}
          </button>
          <button class="btn-primary animate-float btn-styled" @click="openAddModal">
            <i class="fas fa-plus"></i> {{ addVehicleLabel }}
          </button>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="filters-section card animate-fade-in-up" style="animation-delay: 0.2s">
      <div class="filter-group">
        <div class="search-wrapper">
          <i class="fas fa-search search-icon"></i>
          <input v-model="searchQuery" placeholder="Rechercher par marque, modèle, plaque..." class="search-input" />
        </div>
      </div>
      <div class="filter-group">
        <select v-model="statusFilter" class="filter-select">
          <option value="">Tous les statuts</option>
          <option value="available">Disponible</option>
          <option value="mission">En mission</option>
          <option value="maintenance">En maintenance</option>
        </select>
      </div>
      <div class="filter-group">
        <select v-model="blockchainFilter" class="filter-select">
          <option value="">Tous</option>
          <option value="verified">Certifié Blockchain</option>
          <option value="pending">En attente</option>
        </select>
      </div>
    </div>

    <!-- Stats Summary -->
    <div class="stats-summary animate-fade-in-up" style="animation-delay: 0.3s">
      <div class="summary-item">
        <div class="summary-icon bg-emerald-500/20">
          <i class="fas fa-car text-emerald-400"></i>
        </div>
        <div class="summary-content">
          <div class="summary-value">{{ totalVehicles }}</div>
          <div class="summary-label">Total</div>
        </div>
      </div>
      <div class="summary-item">
        <div class="summary-icon bg-blue-500/20">
          <i class="fas fa-check-circle text-blue-400"></i>
        </div>
        <div class="summary-content">
          <div class="summary-value">{{ blockchainVerified }}</div>
          <div class="summary-label">Certifiés</div>
        </div>
      </div>
      <div class="summary-item">
        <div class="summary-icon bg-amber-500/20">
          <i class="fas fa-clock text-amber-400"></i>
        </div>
        <div class="summary-content">
          <div class="summary-value">{{ pendingCertification }}</div>
          <div class="summary-label">En attente</div>
        </div>
      </div>
    </div>

    <!-- Vehicles Grid -->
    <div class="vehicles-grid animate-fade-in-up" style="animation-delay: 0.4s">
      <div v-for="vehicle in filteredVehicles" :key="vehicle.id" class="vehicle-card card group">
        <div class="vehicle-header">
          <div class="vehicle-avatar">
            <i class="fas fa-car"></i>
          </div>
          <div class="vehicle-info">
            <h3 class="text-glow">{{ vehicle.brand }} {{ vehicle.model }}</h3>
            <span class="vehicle-year">{{ vehicle.year }}</span>
          </div>
          <div class="vehicle-menu">
            <button class="menu-btn" @click="toggleMenu(vehicle.id)">
              <i class="fas fa-ellipsis-v"></i>
            </button>
            <div class="dropdown-menu" :class="{ 'active': activeMenu === vehicle.id }">
              <button @click="viewDetails(vehicle)" class="dropdown-item">
                <i class="fas fa-eye"></i> Voir détails
              </button>
              <button @click="viewTimeline(vehicle)" class="dropdown-item">
                <i class="fas fa-history"></i> Timeline
              </button>
              <button @click="editVehicle(vehicle)" class="dropdown-item">
                <i class="fas fa-edit"></i> Modifier
              </button>
              <div class="dropdown-divider"></div>
              <button @click="deleteVehicle(vehicle)" class="dropdown-item danger">
                <i class="fas fa-trash"></i> Supprimer
              </button>
            </div>
          </div>
        </div>

        <div class="vehicle-body">
          <div class="vehicle-specs">
            <div class="spec-item">
              <i class="fas fa-id-card spec-icon"></i>
              <div class="spec-content">
                <span class="spec-label">Plaque</span>
                <span class="spec-value">{{ vehicle.licensePlate }}</span>
              </div>
            </div>
            <div class="spec-item">
              <i class="fas fa-tachometer-alt spec-icon"></i>
              <div class="spec-content">
                <span class="spec-label">Kilométrage</span>
                <span class="spec-value">{{ formatKm(vehicle.mileage) }} km</span>
              </div>
            </div>
            <div class="spec-item" v-if="vehicle.purchasePrice">
              <i class="fas fa-coins spec-icon"></i>
              <div class="spec-content">
                <span class="spec-label">Prix d'achat</span>
                <span class="spec-value">{{ formatFcfa(vehicle.purchasePrice) }}</span>
              </div>
            </div>
          </div>

          <div class="vehicle-status">
            <span :class="'status-badge ' + vehicle.statusClass">
              <i :class="getStatusIcon(vehicle.statusClass)"></i>
              {{ vehicle.status }}
            </span>
            <span :class="'blockchain-badge ' + (vehicle.blockchainVerified ? 'verified' : 'pending')">
              <i :class="vehicle.blockchainVerified ? 'fas fa-check-circle' : 'fas fa-clock'"></i>
              {{ vehicle.blockchainVerified ? 'Certifié' : 'En attente' }}
            </span>
          </div>
        </div>

        <div class="vehicle-footer">
          <button @click="viewDetails(vehicle)" class="btn-view group-hover:animate-pulse">
            <i class="fas fa-eye"></i> Voir détails
          </button>
          <button @click="registerOnBlockchain(vehicle)" class="btn-blockchain" v-if="!vehicle.blockchainVerified">
            <i class="fas fa-cubes"></i> Certifier
          </button>
          <button @click="recertifyOnBlockchain(vehicle)" class="btn-blockchain recertify" v-else>
            <i class="fas fa-redo"></i> Re-certifier
          </button>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="filteredVehicles.length === 0" class="empty-state animate-fade-in">
      <div class="empty-icon animate-float">
        <i class="fas fa-car-side text-6xl"></i>
      </div>
      <h3>Aucun véhicule trouvé</h3>
      <p>Commencez par ajouter votre premier véhicule à la flotte</p>
      <button class="btn-primary" @click="openAddModal">
        <i class="fas fa-plus"></i> {{ addVehicleLabel }}
      </button>
    </div>

    <!-- MODAL: Add/Edit Vehicle -->
    <div v-if="showModal" class="modal-overlay animate-fade-in" @click.self="closeModal">
      <div class="modal glass-dark animate-slide-in-up modal-lg">
        <div class="modal-header">
          <h3 class="text-glow">
            <i class="fas fa-plus-circle"></i> {{ isEditing ? 'Modifier' : 'Ajouter' }} un Véhicule
          </h3>
          <button @click="closeModal" class="modal-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <!-- Tabs -->
          <div class="modal-tabs">
            <button :class="{ active: modalTab === 'form' }" @click="modalTab = 'form'">
              <i class="fas fa-car"></i> Formulaire
            </button>
            <button :class="{ active: modalTab === 'vehicles' }" @click="modalTab = 'vehicles'">
              <i class="fas fa-list"></i> Mes Véhicules
            </button>
            <button :class="{ active: modalTab === 'documents' }" @click="modalTab = 'documents'">
              <i class="fas fa-file-alt"></i> Documents
            </button>
            <button :class="{ active: modalTab === 'consumption' }" @click="modalTab = 'consumption'">
              <i class="fas fa-gas-pump"></i> Consommation
            </button>
          </div>

          <!-- Form Tab -->
          <div v-if="modalTab === 'form'" class="form-grid">
            <div class="form-group">
              <label>Marque *</label>
              <input v-model="vehicleForm.brand" class="input-field" placeholder="Toyota, Renault...">
            </div>
            <div class="form-group">
              <label>Modèle *</label>
              <input v-model="vehicleForm.model" class="input-field" placeholder="Corolla, Clio...">
            </div>
            <div class="form-group">
              <label>Année *</label>
              <input v-model="vehicleForm.year" type="number" class="input-field" placeholder="2024">
            </div>
            <div class="form-group">
              <label>Plaque *</label>
              <input v-model="vehicleForm.licensePlate" class="input-field" placeholder="AA-123-BB">
            </div>
            <div class="form-group">
              <label>Kilométrage initial</label>
              <input v-model="vehicleForm.mileage" type="number" class="input-field" placeholder="0">
            </div>
            <div class="form-group">
              <label>Prix d'achat (FCFA)</label>
              <input v-model="vehicleForm.purchasePrice" type="number" min="0" class="input-field" placeholder="ex: 8500000">
            </div>
            <div class="form-group">
              <label>Statut</label>
              <select v-model="vehicleForm.status" class="input-field">
                <option value="Disponible">Disponible</option>
                <option value="En mission">En mission</option>
                <option value="En maintenance">En maintenance</option>
              </select>
            </div>
          </div>

          <!-- Vehicles List Tab -->
          <div v-if="modalTab === 'vehicles'" class="vehicles-list">
            <div v-if="vehicles.length === 0" class="empty-state">
              <i class="fas fa-car-side text-4xl"></i>
              <p>Aucun véhicule enregistré</p>
            </div>
            <div v-for="v in vehicles" :key="v.id" class="vehicle-list-item" @click="editVehicle(v)">
              <div class="v-item-icon"><i class="fas fa-car"></i></div>
              <div class="v-item-info">
                <strong>{{ v.brand }} {{ v.model }}</strong>
                <small>{{ v.licensePlate }} • {{ formatKm(v.mileage) }} km</small>
              </div>
              <div class="v-item-status">
                <span :class="'status-badge ' + v.statusClass">{{ v.status }}</span>
              </div>
            </div>
          </div>

          <!-- Documents Tab -->
          <div v-if="modalTab === 'documents'" class="documents-section">
            <div v-if="isEditing" class="vehicle-documents">
              <div v-for="doc in vehicleDocuments" :key="doc.id" class="doc-item">
                <div class="doc-icon"><i :class="doc.icon"></i></div>
                <div class="doc-info">
                  <strong>{{ doc.name }}</strong>
                  <small>{{ doc.type }} • Exp: {{ doc.expiryDate }}</small>
                </div>
                <span v-if="doc.blockchainVerified" class="verified-badge"><i class="fas fa-check-circle"></i></span>
              </div>
            </div>
            <div v-else class="empty-state">
              <i class="fas fa-file-alt text-4xl"></i>
              <p>Ajoutez d'abord un véhicule pour gérer ses documents</p>
            </div>
          </div>

          <!-- Consumption Tab -->
          <div v-if="modalTab === 'consumption'" class="consumption-section">
            <div v-if="isEditing" class="vehicle-consumption">
              <div class="consumption-stats">
                <div class="stat-item">
                  <span class="stat-label">Moyenne</span>
                  <span class="stat-value">{{ vehicleConsumption.avg }} L/100km</span>
                </div>
                <div class="stat-item">
                  <span class="stat-label">Coût total</span>
                  <span class="stat-value">{{ formatFcfa(vehicleConsumption.totalCost) }}</span>
                </div>
              </div>
              <div v-for="record in vehicleConsumption.history" :key="record.id" class="fuel-record">
                <small>{{ record.date }}</small>
                <strong>{{ record.liters }} L</strong>
                <small>{{ formatFcfa(record.cost) }}</small>
              </div>
            </div>
            <div v-else class="empty-state">
              <i class="fas fa-gas-pump text-4xl"></i>
              <p>Ajoutez d'abord un véhicule pour suivre sa consommation</p>
            </div>
          </div>

          <p v-if="formError" class="form-error">{{ formError }}</p>
        </div>
        <div class="modal-footer">
          <button @click="closeModal" class="btn-secondary" :disabled="saving">
            <i class="fas fa-times"></i> Annuler
          </button>
          <button @click="saveVehicle" class="btn-primary" :disabled="saving">
            <i :class="saving ? 'fas fa-spinner fa-spin' : 'fas fa-save'"></i>
            {{ saving ? 'Enregistrement…' : 'Enregistrer' }}
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL: Confirm delete -->
    <div v-if="deleteModal.show" class="modal-overlay animate-fade-in" @click.self="closeDeleteModal">
      <div class="modal glass-dark animate-slide-in-up delete-modal">
        <div class="delete-visual">
          <div class="delete-icon-wrap"><i class="fas fa-trash-alt"></i></div>
          <h3>Supprimer ce véhicule ?</h3>
          <p>
            Vous allez supprimer
            <strong>{{ deleteModal.vehicle?.brand }} {{ deleteModal.vehicle?.model }}</strong>
            <span v-if="deleteModal.vehicle?.licensePlate">({{ deleteModal.vehicle.licensePlate }})</span>.
            Cette action est irréversible.
          </p>
        </div>
        <div class="modal-footer centered">
          <button class="btn-secondary" @click="closeDeleteModal" :disabled="deleting">Annuler</button>
          <button class="btn-danger" @click="confirmDelete" :disabled="deleting">
            <i :class="deleting ? 'fas fa-spinner fa-spin' : 'fas fa-trash'"></i>
            {{ deleting ? 'Suppression…' : 'Oui, supprimer' }}
          </button>
        </div>
      </div>
    </div>

    <!-- TOAST -->
    <div v-if="toast.show" class="toast animate-slide-in-right" :class="toast.type">
      <i :class="toast.icon"></i>
      <span>{{ toast.message }}</span>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useStore } from 'vuex'
import { normalizeVehicle } from '../utils/vehicles'
import { loadVehicles, saveVehicles } from '../utils/localData'
import { fetchVehiclesHybrid, createVehicleHybrid, certifyVehicleHybrid, deleteVehicleHybrid } from '../utils/dataService'
import { formatFcfa } from '../utils/currency'

export default {
  name: 'Vehicles',
  setup() {
    const router = useRouter()
    const store = useStore()
    const searchQuery = ref('')
    const statusFilter = ref('')
    const blockchainFilter = ref('')
    const activeMenu = ref(null)
    const showModal = ref(false)
    const isEditing = ref(false)
    const saving = ref(false)
    const refreshing = ref(false)
    const deleting = ref(false)
    const formError = ref('')
    const deleteModal = ref({ show: false, vehicle: null })
    const vehicleForm = ref({
      brand: '',
      model: '',
      year: '',
      licensePlate: '',
      mileage: 0,
      purchasePrice: '',
      status: 'Disponible'
    })

    const toast = ref({ show: false, message: '', type: 'success', icon: 'fas fa-check-circle' })
    const vehicles = ref(loadVehicles())
    const modalTab = ref('form')
    const vehicleDocuments = ref([])
    const vehicleConsumption = ref({ avg: '0', totalCost: 0, history: [] })

    const currentRole = computed(() => store.state.auth?.user?.roles?.[0]?.name || '')
    const addVehicleLabel = computed(() => {
      if (currentRole.value === 'garagiste_agree') return 'Enregistrer un véhicule'
      if (currentRole.value === 'gestionnaire_parc') return 'Ajouter à la flotte'
      if (currentRole.value === 'super_admin') return 'Nouveau véhicule'
      return 'Nouveau véhicule'
    })

    const persistVehicles = () => saveVehicles(vehicles.value)

    const loadVehiclesList = async () => {
      const result = await fetchVehiclesHybrid()
      vehicles.value = result.data
      persistVehicles()
    }

    const refreshVehicles = async () => {
      refreshing.value = true
      try {
        await loadVehiclesList()
        showToast('Liste des véhicules actualisée')
      } catch {
        showToast('Impossible d\'actualiser la liste', 'error')
      } finally {
        setTimeout(() => { refreshing.value = false }, 400)
      }
    }

    const filteredVehicles = computed(() => {
      return vehicles.value.filter((v) => {
        const matchesSearch = !searchQuery.value ||
          v.brand.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
          v.model.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
          v.licensePlate.toLowerCase().includes(searchQuery.value.toLowerCase())

        const matchesStatus = !statusFilter.value || v.statusClass === statusFilter.value
        const matchesBlockchain = !blockchainFilter.value ||
          (blockchainFilter.value === 'verified' && v.blockchainVerified) ||
          (blockchainFilter.value === 'pending' && !v.blockchainVerified)

        return matchesSearch && matchesStatus && matchesBlockchain
      })
    })

    const totalVehicles = computed(() => vehicles.value.length)
    const blockchainVerified = computed(() => vehicles.value.filter((v) => v.blockchainVerified).length)
    const pendingCertification = computed(() => vehicles.value.filter((v) => !v.blockchainVerified).length)

    const formatKm = (n) => n?.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ') || '0'

    const getStatusIcon = (status) => {
      const icons = {
        available: 'fas fa-check-circle',
        mission: 'fas fa-truck',
        maintenance: 'fas fa-tools'
      }
      return icons[status] || 'fas fa-circle'
    }

    const showToast = (message, type = 'success') => {
      toast.value = {
        show: true,
        message,
        type,
        icon: type === 'success' ? 'fas fa-check-circle' : type === 'error' ? 'fas fa-times-circle' : 'fas fa-info-circle'
      }
      setTimeout(() => {
        toast.value.show = false
      }, 3200)
    }

    const toggleMenu = (id) => {
      activeMenu.value = activeMenu.value === id ? null : id
    }

    const openAddModal = () => {
      isEditing.value = false
      formError.value = ''
      modalTab.value = 'form'
      vehicleForm.value = {
        brand: '',
        model: '',
        year: new Date().getFullYear(),
        licensePlate: '',
        mileage: 0,
        purchasePrice: '',
        status: 'Disponible'
      }
      vehicleDocuments.value = []
      vehicleConsumption.value = { avg: '0', totalCost: 0, history: [] }
      showModal.value = true
    }

    const closeModal = () => {
      if (saving.value) return
      showModal.value = false
      activeMenu.value = null
      formError.value = ''
    }

    const saveVehicle = async () => {
      formError.value = ''
      if (!vehicleForm.value.brand || !vehicleForm.value.model) {
        formError.value = 'Veuillez renseigner la marque et le modèle.'
        showToast('Marque et modèle obligatoires', 'error')
        return
      }
      if (!vehicleForm.value.licensePlate) {
        formError.value = 'La plaque d\'immatriculation est obligatoire.'
        showToast('Plaque obligatoire', 'error')
        return
      }

      saving.value = true
      try {
        if (isEditing.value) {
          const index = vehicles.value.findIndex((v) => v.id === vehicleForm.value.id)
          if (index !== -1) {
            const updated = normalizeVehicle({
              ...vehicles.value[index],
              ...vehicleForm.value,
              plate_number: vehicleForm.value.licensePlate,
              current_mileage: vehicleForm.value.mileage,
              purchase_price: vehicleForm.value.purchasePrice,
              status: vehicleForm.value.status
            })
            vehicles.value[index] = updated
            persistVehicles()
            showToast('Véhicule modifié avec succès')
          }
        } else {
          const created = await createVehicleHybrid(vehicleForm.value)
          vehicles.value = [created, ...vehicles.value.filter((v) => v.id !== created.id)]
          persistVehicles()
          showToast(`Véhicule ${created.brand} ${created.model} ajouté`)
        }
        closeModal()
      } catch (error) {
        const msg = error?.response?.data?.errors
          ? Object.values(error.response.data.errors).flat()[0]
          : error?.response?.data?.message || error?.message || 'Erreur lors de l\'enregistrement'
        formError.value = msg
        showToast(msg, 'error')
      } finally {
        saving.value = false
      }
    }

    const editVehicle = (vehicle) => {
      isEditing.value = true
      formError.value = ''
      modalTab.value = 'form'
      vehicleForm.value = {
        id: vehicle.id,
        brand: vehicle.brand,
        model: vehicle.model,
        year: vehicle.year,
        licensePlate: vehicle.licensePlate,
        mileage: vehicle.mileage,
        purchasePrice: vehicle.purchasePrice || '',
        status: vehicle.status || 'Disponible'
      }
      // Simuler des données de documents et consommation
      vehicleDocuments.value = [
        { id: 1, name: 'Carte Grise', type: 'Administratif', expiryDate: '2025-12-31', icon: 'fas fa-id-card', blockchainVerified: vehicle.blockchainVerified },
        { id: 2, name: 'Assurance', type: 'Assurance', expiryDate: '2024-06-30', icon: 'fas fa-shield-alt', blockchainVerified: vehicle.blockchainVerified }
      ]
      vehicleConsumption.value = {
        avg: '5.2',
        totalCost: 125000,
        history: [
          { id: 1, date: '2024-01-15', liters: 45, cost: 45000 },
          { id: 2, date: '2024-02-20', liters: 38, cost: 38000 },
          { id: 3, date: '2024-03-10', liters: 42, cost: 42000 }
        ]
      }
      activeMenu.value = null
      showModal.value = true
    }

    const deleteVehicle = (vehicle) => {
      deleteModal.value = { show: true, vehicle }
      activeMenu.value = null
    }

    const closeDeleteModal = () => {
      if (deleting.value) return
      deleteModal.value = { show: false, vehicle: null }
    }

    const confirmDelete = async () => {
      const vehicle = deleteModal.value.vehicle
      if (!vehicle) return
      deleting.value = true
      try {
        await deleteVehicleHybrid(vehicle.id)
        vehicles.value = vehicles.value.filter((v) => v.id !== vehicle.id)
        persistVehicles()
        showToast(`${vehicle.brand} ${vehicle.model} a été supprimé`)
        deleteModal.value = { show: false, vehicle: null }
      } catch (error) {
        showToast(error?.response?.data?.message || 'Suppression impossible', 'error')
      } finally {
        deleting.value = false
      }
    }

    const viewDetails = (vehicle) => {
      activeMenu.value = null
      router.push(`/vehicle/${vehicle.id}`)
    }

    const viewTimeline = (vehicle) => {
      activeMenu.value = null
      router.push(`/vehicle/${vehicle.id}/timeline`)
    }

    const certifyingId = ref(null)

    const registerOnBlockchain = async (vehicle) => {
      certifyingId.value = vehicle.id
      try {
        const result = await certifyVehicleHybrid(vehicle)
        const index = vehicles.value.findIndex((v) => v.id === vehicle.id)
        if (index !== -1) {
          vehicles.value[index] = result.vehicle
          persistVehicles()
        }
        showToast(result.message || 'Certification blockchain réussie', 'success')
      } catch (err) {
        showToast(err?.response?.data?.message || err.message || 'Certification échouée', 'error')
      } finally {
        certifyingId.value = null
      }
    }

    const recertifyOnBlockchain = async (vehicle) => {
      certifyingId.value = vehicle.id
      try {
        const result = await certifyVehicleHybrid(vehicle, { force: true })
        const index = vehicles.value.findIndex((v) => v.id === vehicle.id)
        if (index !== -1) {
          vehicles.value[index] = result.vehicle
          persistVehicles()
        }
        showToast(result.message || 'Re-certification réussie', 'success')
      } catch (err) {
        showToast(err?.response?.data?.message || err.message || 'Re-certification échouée', 'error')
      } finally {
        certifyingId.value = null
      }
    }

    onMounted(() => {
      loadVehiclesList()
    })
    return {
      searchQuery,
      statusFilter,
      blockchainFilter,
      activeMenu,
      showModal,
      isEditing,
      vehicleForm,
      toast,
      vehicles,
      filteredVehicles,
      totalVehicles,
      blockchainVerified,
      pendingCertification,
      formatKm,
      formatFcfa,
      getStatusIcon,
      toggleMenu,
      openAddModal,
      closeModal,
      saveVehicle,
      editVehicle,
      deleteVehicle,
      deleteModal,
      closeDeleteModal,
      confirmDelete,
      viewDetails,
      viewTimeline,
      registerOnBlockchain,
      recertifyOnBlockchain,
      certifyingId,
      refreshVehicles,
      refreshing,
      saving,
      deleting,
      formError,
      addVehicleLabel,
      modalTab,
      vehicleDocuments,
      vehicleConsumption
    }
  }
}
</script>

<style scoped>
.vehicles-page {
  padding: 0;
}

/* Header */
.page-header {
  margin-bottom: 32px;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 24px;
}

.title-section h1 {
  font-size: 28px;
  font-weight: 700;
  color: #f8fafc;
  margin-bottom: 8px;
}

.title-section h1 i {
  margin-right: 12px;
  color: #38bdf8;
}

.title-section p {
  font-size: 14px;
  color: #94a3b8;
}

/* Filters */
.filters-section {
  display: flex;
  gap: 16px;
  padding: 20px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}

.filter-group {
  flex: 1;
  min-width: 200px;
}

.search-wrapper {
  position: relative;
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

.filter-select {
  width: 100%;
  padding: 12px 16px;
  background: rgba(15, 23, 42, 0.5);
  border: 1px solid rgba(56, 189, 248, 0.2);
  border-radius: 12px;
  color: #f8fafc;
  font-size: 14px;
  transition: all 0.3s;
  cursor: pointer;
}

.filter-select:focus {
  outline: none;
  border-color: #38bdf8;
  box-shadow: 0 0 20px rgba(56, 189, 248, 0.3);
}

/* Stats Summary */
.stats-summary {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 32px;
}

.summary-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px;
  background: rgba(15, 23, 42, 0.5);
  border: 1px solid rgba(56, 189, 248, 0.1);
  border-radius: 12px;
  transition: all 0.3s;
}

.summary-item:hover {
  border-color: rgba(56, 189, 248, 0.3);
  transform: translateY(-2px);
}

.summary-icon {
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
  font-size: 20px;
}

.summary-content {
  flex: 1;
}

.summary-value {
  font-size: 24px;
  font-weight: 700;
  color: #f8fafc;
  line-height: 1;
  margin-bottom: 4px;
}

.summary-label {
  font-size:  twelve;
  color: #94a3b8;
  font-weight: 500;
}

/* Vehicles Grid */
.vehicles-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 24px;
}

.vehicle-card {
  padding: 24px;
  border-radius: 16px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}

.vehicle-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: linear-gradient(90deg, transparent, rgba(56, 189, 248, 0.5), transparent);
}

.vehicle-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
}

.vehicle-header {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 20px;
  position: relative;
}

.vehicle-avatar {
  width: 56px;
  height: 56px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, rgba(56, 189, 248, 0.1), rgba(168, 85, 247, 0.1));
  border-radius: 14px;
  color: #38bdf8;
  font-size: 24px;
}

.vehicle-info h3 {
  font-size: 18px;
  font-weight: 600;
  color: #f8fafc;
  margin-bottom: 4px;
}

.vehicle-year {
  font-size: 12px;
  color: #94a3b8;
}

.vehicle-menu {
  margin-left: auto;
  position: relative;
}

.menu-btn {
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

.menu-btn:hover {
  background: rgba(56, 189, 248, 0.2);
  box-shadow: 0 0 15px rgba(56, 189, 248, 0.3);
}

.dropdown-menu {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  min-width: 180px;
  background: rgba(15, 23, 42, 0.95);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(56, 189, 248, 0.2);
  border-radius: 12px;
  padding: 8px;
  opacity: 0;
  visibility: hidden;
  transform: translateY(-10px);
  transition: all 0.3s;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
  z-index: 10;
}

.dropdown-menu.active {
  opacity: 1;
  visibility: visible;
  transform: translateY(0);
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  color: #94a3b8;
  border-radius: 8px;
  font-size: 13px;
  transition: all 0.3s;
  cursor: pointer;
  border: none;
  background: none;
  width: 100%;
}

.dropdown-item:hover {
  background: rgba(56, 189, 248, 0.1);
  color: #38bdf8;
  transform: translateX(4px);
}

.dropdown-item.danger {
  color: #ef4444;
}

.dropdown-item.danger:hover {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
}

.dropdown-divider {
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(148, 163, 184, 0.3), transparent);
  margin: 8px 0;
}

.vehicle-body {
  margin-bottom: 20px;
}

.vehicle-specs {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 16px;
}

.spec-item {
  display: flex;
  align-items: center;
  gap: 12px;
}

.spec-icon {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(56, 189, 248, 0.1);
  border-radius: 10px;
  color: #38bdf8;
  font-size: 14px;
}

.spec-content {
  display: flex;
  flex-direction: column;
}

.spec-label {
  font-size: 11px;
  color: #94a3b8;
  font-weight: 500;
}

.spec-value {
  font-size: 14px;
  color: #f8fafc;
  font-weight: 600;
}

.vehicle-status {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
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
  padding: 4px 12px;
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

.vehicle-footer {
  display: flex;
  gap: 12px;
  padding-top: 16px;
  border-top: 1px solid rgba(56, 189, 248, 0.1);
}

.btn-view {
  flex: 1;
  padding: 10px 16px;
  background: rgba(56, 189, 248, 0.1);
  border: 1px solid rgba(56, 189, 248, 0.3);
  border-radius: 10px;
  color: #38bdf8;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-view:hover {
  background: rgba(56, 189, 248, 0.2);
  box-shadow: 0 0 20px rgba(56, 189, 248, 0.3);
}

.btn-blockchain {
  padding: 10px 16px;
  background: rgba(168, 85, 247, 0.1);
  border: 1px solid rgba(168, 85, 247, 0.3);
  border-radius: 10px;
  color: #a855f7;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-blockchain:hover {
  background: rgba(168, 85, 247, 0.2);
  box-shadow: 0 0 20px rgba(168, 85, 247, 0.3);
}

.btn-blockchain.recertify {
  background: rgba(14, 165, 233, 0.12);
  border-color: rgba(14, 165, 233, 0.4);
  color: #38bdf8;
}

.btn-blockchain.recertify:hover {
  background: rgba(14, 165, 233, 0.22);
  box-shadow: 0 0 20px rgba(14, 165, 233, 0.3);
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 80px 20px;
}

.empty-icon {
  width: 120px;
  height: 120px;
  margin: 0 auto 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, rgba(56, 189, 248, 0.1), rgba(168, 85, 247, 0.1));
  border-radius: 30px;
  color: #38bdf8;
}

.empty-state h3 {
  font-size: 20px;
  font-weight: 600;
  color: #f8fafc;
  margin-bottom: 8px;
}

.empty-state p {
  font-size: 14px;
  color: #94a3b8;
  margin-bottom: 24px;
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

.input-field {
  width: 100%;
  padding: 12px 16px;
  background: rgba(15, 23, 42, 0.5);
  border: 1px solid rgba(56, 189, 248, 0.2);
  border-radius: 12px;
  color: #f8fafc;
  font-size: 14px;
  transition: all 0.3s;
}

.input-field:focus {
  outline: none;
  border-color: #38bdf8;
  box-shadow: 0 0 20px rgba(56, 189, 248, 0.3);
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.header-actions .btn-primary,
.btn-primary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px 20px;
  border: none;
  border-radius: 12px;
  background: linear-gradient(135deg, #0ea5e9, #6366f1);
  color: #fff;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  box-shadow: 0 8px 24px rgba(14, 165, 233, 0.35);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.header-actions .btn-primary:hover,
.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 28px rgba(99, 102, 241, 0.45);
}

.header-actions .btn-secondary,
.btn-secondary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px 16px;
  border-radius: 12px;
  border: 1px solid rgba(148, 163, 184, 0.35);
  background: rgba(30, 41, 59, 0.9);
  color: #e2e8f0;
  cursor: pointer;
  font-weight: 600;
  font-size: 14px;
}

.header-actions .btn-secondary:disabled {
  opacity: 0.7;
  cursor: wait;
}

.empty-state .btn-primary {
  margin-top: 8px;
}

.btn-styled {
  background: linear-gradient(135deg, #0ea5e9, #6366f1);
  box-shadow: 0 8px 24px rgba(14, 165, 233, 0.35);
  border: none;
  color: #fff;
}

.btn-styled:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 28px rgba(99, 102, 241, 0.45);
}

.modal-footer .btn-secondary {
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: #cbd5e1;
}

.modal-footer .btn-secondary:hover {
  background: rgba(255, 255, 255, 0.15);
  border-color: rgba(255, 255, 255, 0.3);
}

.modal-footer .btn-primary {
  background: linear-gradient(135deg, #10b981, #059669);
  border: none;
  box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
}

.modal-footer .btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
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

.form-error {
  margin-top: 12px;
  color: #fca5a5;
  font-size: 13px;
}

.delete-modal {
  max-width: 420px;
  text-align: center;
  padding-top: 8px;
}

.delete-visual {
  padding: 28px 24px 8px;
}

.delete-icon-wrap {
  width: 64px;
  height: 64px;
  margin: 0 auto 16px;
  border-radius: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(239, 68, 68, 0.15);
  color: #f87171;
  font-size: 24px;
}

.delete-visual h3 {
  margin: 0 0 10px;
  color: #f8fafc;
  font-size: 18px;
}

.delete-visual p {
  margin: 0;
  color: #94a3b8;
  font-size: 14px;
  line-height: 1.5;
}

.delete-visual strong {
  color: #f8fafc;
}

.modal-footer.centered {
  justify-content: center;
}

.btn-danger {
  padding: 10px 18px;
  border: none;
  border-radius: 10px;
  background: linear-gradient(135deg, #ef4444, #dc2626);
  color: #fff;
  cursor: pointer;
  font-weight: 600;
}

.btn-danger:disabled {
  opacity: 0.7;
  cursor: wait;
}

/* Modal Tabs */
.modal-lg {
  max-width: 700px;
}

.modal-tabs {
  display: flex;
  gap: 8px;
  margin-bottom: 20px;
  border-bottom: 1px solid rgba(56, 189, 248, 0.2);
  padding-bottom: 12px;
}

.modal-tabs button {
  flex: 1;
  padding: 10px 16px;
  border: none;
  background: transparent;
  color: #94a3b8;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  border-radius: 8px;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.modal-tabs button:hover:not(:disabled) {
  background: rgba(56, 189, 248, 0.1);
  color: #38bdf8;
}

.modal-tabs button.active {
  background: rgba(56, 189, 248, 0.2);
  color: #38bdf8;
}

.modal-tabs button:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

/* Vehicles List in Modal */
.vehicles-list {
  max-height: 300px;
  overflow-y: auto;
}

.vehicle-list-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  background: rgba(15, 23, 42, 0.5);
  border: 1px solid rgba(56, 189, 248, 0.1);
  border-radius: 10px;
  margin-bottom: 8px;
  cursor: pointer;
  transition: all 0.3s;
}

.vehicle-list-item:hover {
  background: rgba(56, 189, 248, 0.1);
  border-color: rgba(56, 189, 248, 0.3);
  transform: translateX(4px);
}

.v-item-icon {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(56, 189, 248, 0.15);
  border-radius: 8px;
  color: #38bdf8;
}

.v-item-info {
  flex: 1;
}

.v-item-info strong {
  display: block;
  color: #f8fafc;
  font-size: 14px;
}

.v-item-info small {
  color: #94a3b8;
  font-size: 12px;
}

.v-item-status .status-badge {
  font-size: 11px;
  padding: 4px 10px;
}

/* Documents Section */
.documents-section,
.consumption-section {
  max-height: 300px;
  overflow-y: auto;
}

.vehicle-documents {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.doc-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  background: rgba(15, 23, 42, 0.5);
  border: 1px solid rgba(56, 189, 248, 0.1);
  border-radius: 10px;
}

.doc-icon {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(56, 189, 248, 0.15);
  border-radius: 8px;
  color: #38bdf8;
}

.doc-info {
  flex: 1;
}

.doc-info strong {
  display: block;
  color: #f8fafc;
  font-size: 14px;
}

.doc-info small {
  color: #94a3b8;
  font-size: 12px;
}

.verified-badge {
  color: #10b981;
  font-size: 16px;
}

/* Consumption Section */
.vehicle-consumption {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.consumption-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 12px;
}

.stat-item {
  padding: 16px;
  background: rgba(15, 23, 42, 0.5);
  border: 1px solid rgba(56, 189, 248, 0.1);
  border-radius: 10px;
  text-align: center;
}

.stat-label {
  display: block;
  font-size: 11px;
  color: #94a3b8;
  text-transform: uppercase;
  margin-bottom: 6px;
}

.stat-value {
  display: block;
  font-size: 18px;
  font-weight: 700;
  color: #f8fafc;
}

.fuel-record {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: rgba(15, 23, 42, 0.5);
  border: 1px solid rgba(56, 189, 248, 0.1);
  border-radius: 8px;
}

.fuel-record small {
  color: #94a3b8;
  font-size: 12px;
}

.fuel-record strong {
  color: #f8fafc;
  font-size: 14px;
}

@media (max-width: 768px) {
  .header-content {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .filters-section {
    flex-direction: column;
  }
  
  .filter-group {
    width: 100%;
  }
  
  .vehicles-grid {
    grid-template-columns: 1fr;
  }
  
  .form-grid {
    grid-template-columns: 1fr;
  }
}
</style>
