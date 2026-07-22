<template>
  <div class="missions-page animate-fade-in-up">
    <!-- Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="title-section">
          <h1 class="text-glow animate-fade-in-down">
            <i class="fas fa-route"></i> Mes Missions
          </h1>
          <p class="animate-fade-in-up" style="animation-delay: 0.1s">
            Gérez vos missions en cours et terminées
          </p>
        </div>
        <div class="header-actions">
          <button class="btn-primary animate-float btn-styled" @click="startNewMission">
            <i class="fas fa-plus"></i> Nouvelle Mission
          </button>
        </div>
      </div>
    </div>

    <!-- Stats Summary -->
    <div class="stats-summary animate-fade-in-up" style="animation-delay: 0.2s">
      <div class="summary-item">
        <div class="summary-icon bg-blue-500/20">
          <i class="fas fa-truck text-blue-400"></i>
        </div>
        <div class="summary-content">
          <div class="summary-value">{{ activeMissions }}</div>
          <div class="summary-label">En cours</div>
        </div>
      </div>
      <div class="summary-item">
        <div class="summary-icon bg-emerald-500/20">
          <i class="fas fa-check-circle text-emerald-400"></i>
        </div>
        <div class="summary-content">
          <div class="summary-value">{{ completedMissions }}</div>
          <div class="summary-label">Terminées</div>
        </div>
      </div>
      <div class="summary-item">
        <div class="summary-icon bg-amber-500/20">
          <i class="fas fa-road text-amber-400"></i>
        </div>
        <div class="summary-content">
          <div class="summary-value">{{ totalKm }}</div>
          <div class="summary-label">Km parcourus</div>
        </div>
      </div>
    </div>

    <!-- Missions List -->
    <div class="missions-grid animate-fade-in-up" style="animation-delay: 0.3s">
      <div v-if="missions.length === 0" class="empty-state">
        <div class="empty-icon animate-float">
          <i class="fas fa-route text-6xl"></i>
        </div>
        <h3>Aucune mission</h3>
        <p>Commencez par créer votre première mission</p>
        <button class="btn-primary" @click="startNewMission">
          <i class="fas fa-plus"></i> Nouvelle Mission
        </button>
      </div>

      <div v-for="mission in missions" :key="mission.id" class="mission-card card group">
        <div class="mission-header">
          <div class="mission-vehicle">
            <i class="fas fa-car"></i>
            <span>{{ mission.vehicle }}</span>
          </div>
          <div class="mission-status" :class="mission.status">
            <i :class="getStatusIcon(mission.status)"></i>
            {{ getStatusLabel(mission.status) }}
          </div>
        </div>

        <div class="mission-body">
          <div class="mission-route">
            <div class="route-point">
              <i class="fas fa-map-marker-alt start"></i>
              <span>{{ mission.startPoint }}</span>
            </div>
            <div class="route-line"></div>
            <div class="route-point">
              <i class="fas fa-map-marker-alt end"></i>
              <span>{{ mission.endPoint }}</span>
            </div>
          </div>

          <div class="mission-details">
            <div class="detail-item">
              <i class="fas fa-calendar"></i>
              <span>{{ mission.date }}</span>
            </div>
            <div class="detail-item">
              <i class="fas fa-tachometer-alt"></i>
              <span>{{ formatKm(mission.distance) }} km</span>
            </div>
            <div class="detail-item">
              <i class="fas fa-clock"></i>
              <span>{{ mission.duration }}</span>
            </div>
          </div>
        </div>

        <div class="mission-footer">
          <button v-if="mission.status === 'active'" @click="completeMission(mission)" class="btn-complete">
            <i class="fas fa-check"></i> Terminer
          </button>
          <button v-else @click="viewMissionDetails(mission)" class="btn-view">
            <i class="fas fa-eye"></i> Voir détails
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL: New Mission -->
    <div v-if="showModal" class="modal-overlay animate-fade-in" @click.self="closeModal">
      <div class="modal glass-dark animate-slide-in-up">
        <div class="modal-header">
          <h3 class="text-glow">
            <i class="fas fa-plus-circle"></i> Nouvelle Mission
          </h3>
          <button @click="closeModal" class="modal-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Véhicule</label>
            <select v-model="missionForm.vehicle" class="input-field">
              <option value="">Sélectionner un véhicule</option>
              <option v-for="v in availableVehicles" :key="v.id" :value="`${v.brand} ${v.model} (${v.licensePlate})`">
                {{ v.brand }} {{ v.model }} - {{ v.licensePlate }}
              </option>
            </select>
          </div>
          <div class="form-group">
            <label>Point de départ</label>
            <input v-model="missionForm.startPoint" class="input-field" placeholder="Ex: Siège social">
          </div>
          <div class="form-group">
            <label>Destination</label>
            <input v-model="missionForm.endPoint" class="input-field" placeholder="Ex: Client A">
          </div>
          <div class="form-group">
            <label>Distance estimée (km)</label>
            <input v-model="missionForm.distance" type="number" class="input-field" placeholder="Ex: 50">
          </div>
        </div>
        <div class="modal-footer">
          <button @click="closeModal" class="btn-secondary">Annuler</button>
          <button @click="saveMission" class="btn-primary">
            <i class="fas fa-save"></i> Créer
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL: Mission Details -->
    <div v-if="showDetailModal" class="modal-overlay animate-fade-in" @click.self="closeDetailModal">
      <div class="modal glass-dark animate-slide-in-up">
        <div class="modal-header">
          <h3 class="text-glow">
            <i class="fas fa-info-circle"></i> Détails de la Mission
          </h3>
          <button @click="closeDetailModal" class="modal-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div v-if="selectedMission" class="mission-detail-content">
            <div class="detail-section">
              <h4><i class="fas fa-car"></i> Véhicule</h4>
              <p>{{ selectedMission.vehicle }}</p>
            </div>
            <div class="detail-section">
              <h4><i class="fas fa-route"></i> Trajet</h4>
              <div class="route-detail">
                <div class="route-point-detail">
                  <i class="fas fa-map-marker-alt start"></i>
                  <span>{{ selectedMission.startPoint }}</span>
                </div>
                <div class="route-arrow">↓</div>
                <div class="route-point-detail">
                  <i class="fas fa-map-marker-alt end"></i>
                  <span>{{ selectedMission.endPoint }}</span>
                </div>
              </div>
            </div>
            <div class="detail-section">
              <h4><i class="fas fa-info-circle"></i> Informations</h4>
              <div class="info-grid">
                <div class="info-item">
                  <span class="info-label">Date</span>
                  <span class="info-value">{{ selectedMission.date }}</span>
                </div>
                <div class="info-item">
                  <span class="info-label">Distance</span>
                  <span class="info-value">{{ formatKm(selectedMission.distance) }} km</span>
                </div>
                <div class="info-item">
                  <span class="info-label">Durée</span>
                  <span class="info-value">{{ selectedMission.duration }}</span>
                </div>
                <div class="info-item">
                  <span class="info-label">Statut</span>
                  <span class="info-value" :class="'status-' + selectedMission.status">
                    {{ getStatusLabel(selectedMission.status) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="closeDetailModal" class="btn-primary">Fermer</button>
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
import { useStore } from 'vuex'
import { loadVehicles } from '../js/utils/localData'

export default {
  name: 'Missions',
  setup() {
    const store = useStore()
    const showModal = ref(false)
    const showDetailModal = ref(false)
    const selectedMission = ref(null)
    const missions = ref([])
    const availableVehicles = ref([])

    const missionForm = ref({
      vehicle: '',
      startPoint: '',
      endPoint: '',
      distance: 0
    })

    const toast = ref({ show: false, message: '', type: 'success', icon: 'fas fa-check-circle' })

    const showToast = (message, type = 'success') => {
      toast.value = {
        show: true,
        message,
        type,
        icon: type === 'success' ? 'fas fa-check-circle' : type === 'error' ? 'fas fa-times-circle' : 'fas fa-info-circle'
      }
      setTimeout(() => toast.value.show = false, 3200)
    }

    const loadMissions = () => {
      const saved = localStorage.getItem('missions')
      if (saved) {
        missions.value = JSON.parse(saved)
      } else {
        // Données de démo
        missions.value = [
          {
            id: 1,
            vehicle: 'Renault Clio (CC-456-DD)',
            startPoint: 'Siège social',
            endPoint: 'Client A',
            distance: 45,
            date: '2024-01-15',
            duration: '1h 30min',
            status: 'completed'
          },
          {
            id: 2,
            vehicle: 'Renault Clio (CC-456-DD)',
            startPoint: 'Client A',
            endPoint: 'Dépôt',
            distance: 30,
            date: '2024-01-20',
            duration: '45min',
            status: 'active'
          }
        ]
      }
    }

    const loadAvailableVehicles = () => {
      availableVehicles.value = loadVehicles()
    }

    const activeMissions = computed(() => missions.value.filter(m => m.status === 'active').length)
    const completedMissions = computed(() => missions.value.filter(m => m.status === 'completed').length)
    const totalKm = computed(() => missions.value.reduce((sum, m) => sum + (m.distance || 0), 0))

    const formatKm = (n) => n?.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ') || '0'

    const getStatusIcon = (status) => {
      const icons = {
        active: 'fas fa-truck',
        completed: 'fas fa-check-circle',
        pending: 'fas fa-clock'
      }
      return icons[status] || 'fas fa-circle'
    }

    const getStatusLabel = (status) => {
      const labels = {
        active: 'En cours',
        completed: 'Terminée',
        pending: 'En attente'
      }
      return labels[status] || status
    }

    const startNewMission = () => {
      missionForm.value = {
        vehicle: '',
        startPoint: '',
        endPoint: '',
        distance: 0
      }
      showModal.value = true
    }

    const closeModal = () => {
      showModal.value = false
    }

    const saveMission = () => {
      if (!missionForm.value.vehicle || !missionForm.value.startPoint || !missionForm.value.endPoint) {
        showToast('Veuillez remplir tous les champs', 'error')
        return
      }

      const newMission = {
        id: Date.now(),
        vehicle: missionForm.value.vehicle,
        startPoint: missionForm.value.startPoint,
        endPoint: missionForm.value.endPoint,
        distance: parseInt(missionForm.value.distance) || 0,
        date: new Date().toISOString().split('T')[0],
        duration: 'Estimé',
        status: 'active'
      }

      missions.value.unshift(newMission)
      localStorage.setItem('missions', JSON.stringify(missions.value))
      showToast('Mission créée avec succès')
      closeModal()
    }

    const completeMission = (mission) => {
      mission.status = 'completed'
      mission.duration = 'Terminé'
      localStorage.setItem('missions', JSON.stringify(missions.value))
      showToast('Mission terminée')
    }

    const viewMissionDetails = (mission) => {
      selectedMission.value = mission
      showDetailModal.value = true
    }

    const closeDetailModal = () => {
      showDetailModal.value = false
      selectedMission.value = null
    }

    onMounted(() => {
      loadMissions()
      loadAvailableVehicles()
    })

    return {
      showModal,
      showDetailModal,
      selectedMission,
      missions,
      availableVehicles,
      missionForm,
      toast,
      activeMissions,
      completedMissions,
      totalKm,
      formatKm,
      getStatusIcon,
      getStatusLabel,
      startNewMission,
      closeModal,
      saveMission,
      completeMission,
      viewMissionDetails,
      closeDetailModal
    }
  }
}
</script>

<style scoped>
.missions-page {
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
  font-size: 12px;
  color: #94a3b8;
  font-weight: 500;
}

/* Missions Grid */
.missions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 24px;
}

.mission-card {
  padding: 24px;
  border-radius: 16px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}

.mission-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: linear-gradient(90deg, transparent, rgba(56, 189, 248, 0.5), transparent);
}

.mission-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
}

.mission-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.mission-vehicle {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  background: rgba(56, 189, 248, 0.1);
  border-radius: 8px;
  color: #38bdf8;
  font-size: 14px;
  font-weight: 500;
}

.mission-status {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
}

.mission-status.active {
  background: rgba(56, 189, 248, 0.1);
  color: #38bdf8;
  border: 1px solid rgba(56, 189, 248, 0.3);
}

.mission-status.completed {
  background: rgba(16, 185, 129, 0.1);
  color: #10b981;
  border: 1px solid rgba(16, 185, 129, 0.3);
}

.mission-body {
  margin-bottom: 20px;
}

.mission-route {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
  padding: 16px;
  background: rgba(15, 23, 42, 0.5);
  border-radius: 12px;
}

.route-point {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  color: #f8fafc;
  font-weight: 500;
}

.route-point i.start {
  color: #10b981;
}

.route-point i.end {
  color: #ef4444;
}

.route-line {
  flex: 1;
  height: 2px;
  background: linear-gradient(90deg, #10b981, #38bdf8, #ef4444);
  border-radius: 1px;
}

.mission-details {
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
}

.detail-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  color: #94a3b8;
}

.detail-item i {
  color: #38bdf8;
}

.mission-footer {
  display: flex;
  gap: 12px;
  padding-top: 16px;
  border-top: 1px solid rgba(56, 189, 248, 0.1);
}

.btn-complete {
  flex: 1;
  padding: 10px 16px;
  background: rgba(16, 185, 129, 0.1);
  border: 1px solid rgba(16, 185, 129, 0.3);
  border-radius: 10px;
  color: #10b981;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-complete:hover {
  background: rgba(16, 185, 129, 0.2);
  box-shadow: 0 0 20px rgba(16, 185, 129, 0.3);
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

/* Empty State */
.empty-state {
  text-align: center;
  padding: 80px 20px;
  grid-column: 1 / -1;
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
  background: rgba(15, 23, 42, 0.95);
  border: 1px solid rgba(56, 189, 248, 0.2);
  border-radius: 16px;
  padding: 24px;
  backdrop-filter: blur(10px);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.modal-header h3 {
  font-size: 18px;
  font-weight: 600;
  color: #f8fafc;
}

.modal-close {
  width: 32px;
  height: 32px;
  border: none;
  background: rgba(56, 189, 248, 0.1);
  border-radius: 8px;
  color: #38bdf8;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-body {
  margin-bottom: 20px;
}

.form-group {
  margin-bottom: 16px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-size: 13px;
  color: #94a3b8;
  font-weight: 500;
}

.input-field {
  width: 100%;
  padding: 12px 16px;
  background: rgba(15, 23, 42, 0.5);
  border: 1px solid rgba(56, 189, 248, 0.2);
  border-radius: 10px;
  color: #f8fafc;
  font-size: 14px;
  transition: all 0.3s;
}

.input-field:focus {
  outline: none;
  border-color: #38bdf8;
  box-shadow: 0 0 20px rgba(56, 189, 248, 0.3);
}

.modal-footer {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}

.btn-primary,
.btn-secondary {
  padding: 10px 16px;
  border-radius: 10px;
  border: none;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-primary {
  background: #0ea5e9;
  color: #fff;
}

.btn-primary:hover {
  background: #0284c7;
}

.btn-secondary {
  background: rgba(148, 163, 184, 0.15);
  color: #e2e8f0;
}

.btn-secondary:hover {
  background: rgba(148, 163, 184, 0.25);
}

/* Toast */
.toast {
  position: fixed;
  right: 20px;
  bottom: 20px;
  padding: 12px 16px;
  border-radius: 12px;
  background: #1e293b;
  color: #f8fafc;
  display: flex;
  gap: 10px;
  align-items: center;
  z-index: 1100;
  border: 1px solid rgba(148, 163, 184, 0.25);
}

.toast.success { border-color: #10b981; }
.toast.error { border-color: #ef4444; }
.toast.info { border-color: #38bdf8; }

/* Mission Detail Modal */
.mission-detail-content {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.detail-section h4 {
  font-size: 14px;
  font-weight: 600;
  color: #38bdf8;
  margin-bottom: 12px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.detail-section p {
  color: #f8fafc;
  font-size: 14px;
  padding: 12px 16px;
  background: rgba(15, 23, 42, 0.5);
  border-radius: 8px;
}

.route-detail {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 16px;
  background: rgba(15, 23, 42, 0.5);
  border-radius: 8px;
}

.route-point-detail {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  color: #f8fafc;
  font-weight: 500;
}

.route-point-detail i.start {
  color: #10b981;
}

.route-point-detail i.end {
  color: #ef4444;
}

.route-arrow {
  color: #38bdf8;
  font-size: 18px;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 12px;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 12px;
  background: rgba(15, 23, 42, 0.5);
  border-radius: 8px;
}

.info-label {
  font-size: 11px;
  color: #94a3b8;
  text-transform: uppercase;
  font-weight: 500;
}

.info-value {
  font-size: 14px;
  color: #f8fafc;
  font-weight: 600;
}

.info-value.status-active {
  color: #38bdf8;
}

.info-value.status-completed {
  color: #10b981;
}

.info-value.status-pending {
  color: #f59e0b;
}

@media (max-width: 768px) {
  .header-content {
    flex-direction: column;
    align-items: flex-start;
  }

  .missions-grid {
    grid-template-columns: 1fr;
  }

  .mission-route {
    flex-direction: column;
    align-items: flex-start;
  }

  .route-line {
    width: 2px;
    height: 20px;
    margin: 8px 0;
  }
}
</style>
