<template>
  <div class="page">
    <div class="page-top">
      <div>
        <h1><i class="fas fa-tools"></i> Maintenance</h1>
        <p>Suivi des entretiens, réparations et contrôles techniques</p>
      </div>
      <button class="btn-add" @click="openNew"><i class="fas fa-plus"></i> Nouvelle intervention</button>
    </div>

    <div class="stats-row">
      <div class="stat-card" style="border-left: 4px solid #ef4444;">
        <strong>{{ stats.pending }}</strong><span>En attente</span>
      </div>
      <div class="stat-card" style="border-left: 4px solid #f59e0b;">
        <strong>{{ stats.progress }}</strong><span>En cours</span>
      </div>
      <div class="stat-card" style="border-left: 4px solid #10b981;">
        <strong>{{ stats.done }}</strong><span>Terminées</span>
      </div>
      <div class="stat-card" style="border-left: 4px solid #2563eb;">
        <strong>{{ stats.month }}</strong><span>Ce mois</span>
      </div>
    </div>

    <div class="section">
      <div class="section-header">
        <h3>Interventions en cours</h3>
        <select v-model="statusFilter" class="filter-select">
          <option value="">Toutes</option>
          <option value="En attente">En attente</option>
          <option value="En cours">En cours</option>
          <option value="Terminé">Terminé</option>
        </select>
      </div>

      <div class="inter-list">
        <div v-for="item in filteredInterventions" :key="item.id" class="inter-card">
          <div class="inter-left">
            <div class="inter-icon" :class="item.urgency">
              <i :class="item.icon"></i>
            </div>
            <div>
              <strong>{{ item.vehicle }}</strong>
              <p>{{ item.description }}</p>
              <small><i class="fas fa-user"></i> {{ item.mechanic }} • <i class="far fa-calendar"></i> {{ item.date }}</small>
            </div>
          </div>
          <div class="inter-right">
            <span :class="'priority ' + item.urgency">{{ item.urgencyLabel }}</span>
            <span :class="'status-tag ' + item.statusClass">{{ item.status }}</span>
            <div class="inter-actions">
              <button v-if="item.status === 'En attente'" class="btn-start" @click="startIntervention(item)">
                <i class="fas fa-play"></i> Démarrer
              </button>
              <button v-if="item.status === 'En cours'" class="btn-done" @click="finishIntervention(item)">
                <i class="fas fa-check"></i> Terminer
              </button>
              <button class="btn-detail" @click="viewIntervention(item)"><i class="fas fa-eye"></i></button>
              <button class="btn-blockchain" :disabled="certifyingId === item.id" @click="certifyIntervention(item)">
                <i class="fas fa-cubes"></i> {{ certifyingId === item.id ? '...' : 'Certifier' }}
              </button>
            </div>
          </div>
        </div>
        <p v-if="filteredInterventions.length === 0" class="empty">Aucune intervention pour ce filtre.</p>
      </div>
    </div>

    <div class="section">
      <h3>Historique récent</h3>
      <table class="table">
        <thead>
          <tr>
            <th>Véhicule</th>
            <th>Type</th>
            <th>Date</th>
            <th>Mécanicien</th>
            <th>Coût</th>
            <th>Blockchain</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="h in history" :key="h.id">
            <td><strong>{{ h.vehicle }}</strong></td>
            <td>{{ h.type }}</td>
            <td>{{ h.date }}</td>
            <td>{{ h.mechanic }}</td>
            <td>{{ formatFcfa(h.cost) }}</td>
            <td>
              <i v-if="h.certified" class="fas fa-check-circle ok"></i>
              <i v-else class="fas fa-clock warn"></i>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal-card">
        <h3>{{ modalMode === 'new' ? 'Nouvelle intervention' : 'Détail intervention' }}</h3>
        <template v-if="modalMode === 'new'">
          <label>Véhicule</label>
          <input v-model="form.vehicle" placeholder="Ex: Peugeot 308" />
          <label>Description</label>
          <input v-model="form.description" placeholder="Vidange, freins..." />
          <label>Mécanicien / Garage</label>
          <input v-model="form.mechanic" placeholder="Garage AutoPlus" />
          <label>Priorité</label>
          <select v-model="form.urgency">
            <option value="urgent">Urgent</option>
            <option value="normal">Normal</option>
            <option value="low">Planifié</option>
          </select>
        </template>
        <template v-else>
          <div class="detail-grid">
            <div><label>Véhicule</label><span>{{ selected?.vehicle }}</span></div>
            <div><label>Description</label><span>{{ selected?.description }}</span></div>
            <div><label>Statut</label><span>{{ selected?.status }}</span></div>
            <div><label>Garage</label><span>{{ selected?.mechanic }}</span></div>
            <div><label>Date</label><span>{{ selected?.date }}</span></div>
          </div>
        </template>
        <div class="modal-actions">
          <button class="btn-secondary" @click="closeModal">Fermer</button>
          <button v-if="modalMode === 'new'" class="btn-primary" @click="saveNew">Enregistrer</button>
        </div>
      </div>
    </div>

    <div v-if="toast.show" class="toast" :class="toast.type">
      <i :class="toast.icon"></i> {{ toast.message }}
    </div>
  </div>
</template>

<script>
import { computed, ref, onMounted } from 'vue'
import {
  loadMaintenance,
  loadMaintenanceHistory,
  STORAGE_KEYS
} from '../js/utils/localData'
import { fetchMaintenanceHybrid, fetchVehiclesHybrid, createMaintenanceHybrid, certifyMaintenanceHybrid } from '../js/utils/dataService'
import { formatFcfa, toFcfa } from '../js/utils/currency'

export default {
  name: 'Maintenance',
  setup() {
    const statusFilter = ref('')
    const showModal = ref(false)
    const modalMode = ref('new')
    const selected = ref(null)
    const toast = ref({ show: false, message: '', type: 'success', icon: 'fas fa-check' })
    const form = ref({ vehicle: '', description: '', mechanic: 'Garage AutoPlus', urgency: 'normal' })

    const interventions = ref(loadMaintenance())
    const history = ref(loadMaintenanceHistory())
    const vehiclesList = ref([])
    const dataSource = ref('local')

    const persist = () => {
      localStorage.setItem(STORAGE_KEYS.maintenance, JSON.stringify(interventions.value))
      localStorage.setItem(STORAGE_KEYS.maintenanceHistory, JSON.stringify(history.value))
    }

    const loadData = async () => {
      const result = await fetchMaintenanceHybrid()
      interventions.value = result.interventions
      history.value = result.history
      dataSource.value = result.source
      const vehiclesResult = await fetchVehiclesHybrid()
      vehiclesList.value = vehiclesResult.data
    }

    onMounted(loadData)

    const showToast = (message, type = 'success') => {
      toast.value = {
        show: true,
        message,
        type,
        icon: type === 'success' ? 'fas fa-check-circle' : 'fas fa-info-circle'
      }
      setTimeout(() => (toast.value.show = false), 2400)
    }

    const filteredInterventions = computed(() => {
      if (!statusFilter.value) return interventions.value.filter((i) => i.status !== 'Terminé')
      return interventions.value.filter((i) => i.status === statusFilter.value)
    })

    const stats = computed(() => ({
      pending: interventions.value.filter((i) => i.status === 'En attente').length,
      progress: interventions.value.filter((i) => i.status === 'En cours').length,
      done: history.value.length + interventions.value.filter((i) => i.status === 'Terminé').length,
      month: interventions.value.length + 9
    }))

    const openNew = () => {
      modalMode.value = 'new'
      form.value = { vehicle: '', description: '', mechanic: 'Garage AutoPlus', urgency: 'normal' }
      showModal.value = true
    }

    const closeModal = () => {
      showModal.value = false
      selected.value = null
    }

    const certifyingId = ref(null)

    const saveNew = async () => {
      if (!form.value.vehicle || !form.value.description) {
        showToast('Renseignez le véhicule et la description.', 'info')
        return
      }
      const urgencyMap = { urgent: 'Urgent', normal: 'Normal', low: 'Planifié' }

      try {
        const result = await createMaintenanceHybrid({
          vehicle: form.value.vehicle,
          description: form.value.description,
          mechanic: form.value.mechanic,
          urgency: form.value.urgency,
          parts_changed: form.value.description
        }, vehiclesList.value)

        if (result.source === 'api') {
          await loadData()
          closeModal()
          showToast(result.message)
          return
        }

        interventions.value.unshift(result.item)
        persist()
        closeModal()
        showToast(result.message)
      } catch (err) {
        showToast(err?.response?.data?.message || err.message || 'Erreur enregistrement', 'info')
      }
    }

    const startIntervention = (item) => {
      item.status = 'En cours'
      item.statusClass = 'progress'
      persist()
      showToast(`Intervention démarrée sur ${item.vehicle}.`)
    }

    const finishIntervention = (item) => {
      item.status = 'Terminé'
      item.statusClass = 'done'
      history.value.unshift({
        id: Date.now(),
        vehicle: item.vehicle,
        type: item.description.slice(0, 24),
        date: new Date().toLocaleDateString('fr-FR'),
        mechanic: item.mechanic,
        cost: 150,
        certified: false
      })
      interventions.value = interventions.value.filter((i) => i.id !== item.id)
      persist()
      showToast(`Intervention terminée : ${item.vehicle}.`)
    }

    const viewIntervention = (item) => {
      selected.value = item
      modalMode.value = 'view'
      showModal.value = true
    }

    const certifyIntervention = async (item) => {
      certifyingId.value = item.id
      try {
        const result = await certifyMaintenanceHybrid(item, vehiclesList.value)
        if (result.source === 'api') {
          await loadData()
        } else if (result.item) {
          const hist = history.value.find((h) => h.vehicle === item.vehicle && !h.certified)
          if (hist) hist.certified = true
          item.certified = true
          persist()
        }
        showToast(result.message || 'Intervention certifiée on-chain')
      } catch (err) {
        showToast(err?.response?.data?.message || err.message || 'Certification échouée', 'info')
      } finally {
        certifyingId.value = null
      }
    }

    return {
      interventions,
      filteredInterventions,
      history,
      statusFilter,
      stats,
      showModal,
      modalMode,
      selected,
      form,
      toast,
      openNew,
      closeModal,
      saveNew,
      startIntervention,
      finishIntervention,
      viewIntervention,
      certifyIntervention,
      certifyingId,
      formatFcfa: (v) => formatFcfa(toFcfa(v))
    }
  }
}
</script>

<style scoped>
.page { max-width: 1100px; color: #e2e8f0; }
.page-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
.page-top h1 { font-size: 22px; color: #f8fafc; margin: 0; }
.page-top h1 i { color: #f59e0b; margin-right: 8px; }
.page-top p { color: #94a3b8; font-size: 13px; margin: 4px 0 0; }
.btn-add { padding: 10px 20px; background: #f59e0b; color: #fff; border: none; border-radius: 10px; cursor: pointer; font-weight: 600; font-size: 14px; }
.btn-add:hover { background: #d97706; }

.stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-bottom: 20px; }
.stat-card { background: #1e293b; padding: 16px; border-radius: 12px; border: 1px solid rgba(148,163,184,.12); }
.stat-card strong { display: block; font-size: 24px; color: #f8fafc; }
.stat-card span { font-size: 12px; color: #94a3b8; }

.section { background: #1e293b; border-radius: 14px; padding: 20px; margin-bottom: 16px; border: 1px solid rgba(148,163,184,.12); }
.section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; gap: 12px; }
.section h3 { font-size: 15px; color: #f8fafc; margin: 0; }
.filter-select { padding: 8px 12px; border: 1px solid rgba(148,163,184,.25); border-radius: 8px; font-size: 13px; background: #0f172a; color: #e2e8f0; }

.inter-list { display: flex; flex-direction: column; gap: 12px; }
.inter-card { display: flex; justify-content: space-between; align-items: center; padding: 16px; border-radius: 12px; background: #0f172a; flex-wrap: wrap; gap: 12px; border: 1px solid rgba(148,163,184,.1); }
.inter-left { display: flex; gap: 14px; align-items: flex-start; }
.inter-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
.inter-icon.urgent { background: rgba(239,68,68,.15); color: #f87171; }
.inter-icon.normal { background: rgba(37,99,235,.15); color: #60a5fa; }
.inter-icon.low { background: rgba(148,163,184,.15); color: #94a3b8; }
.inter-left strong { display: block; font-size: 14px; color: #f8fafc; }
.inter-left p { font-size: 12px; color: #94a3b8; margin: 2px 0; }
.inter-left small { font-size: 11px; color: #64748b; }
.inter-right { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.priority, .status-tag { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; }
.priority.urgent { background: rgba(239,68,68,.15); color: #f87171; }
.priority.normal { background: rgba(37,99,235,.15); color: #60a5fa; }
.priority.low { background: rgba(148,163,184,.15); color: #94a3b8; }
.status-tag.pending { background: rgba(245,158,11,.15); color: #fbbf24; }
.status-tag.progress { background: rgba(37,99,235,.15); color: #60a5fa; }
.status-tag.done { background: rgba(16,185,129,.15); color: #34d399; }
.inter-actions { display: flex; gap: 6px; flex-wrap: wrap; }
.inter-actions button { padding: 6px 12px; border: none; border-radius: 8px; cursor: pointer; font-size: 12px; font-weight: 500; }
.btn-start { background: #2563eb; color: #fff; }
.btn-done { background: #10b981; color: #fff; }
.btn-detail { background: rgba(148,163,184,.15); color: #e2e8f0; }
.btn-blockchain { background: #7c3aed; color: #fff; }

.table { width: 100%; border-collapse: collapse; }
.table th { text-align: left; padding: 10px; font-size: 11px; color: #94a3b8; text-transform: uppercase; border-bottom: 1px solid rgba(148,163,184,.2); }
.table td { padding: 10px; border-bottom: 1px solid rgba(148,163,184,.1); font-size: 13px; color: #e2e8f0; }
.table td strong { color: #f8fafc; }
.ok { color: #10b981; }
.warn { color: #f59e0b; }
.empty { color: #94a3b8; font-size: 13px; text-align: center; padding: 20px; }

.modal-overlay { position: fixed; inset: 0; background: rgba(2,6,23,.7); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 16px; }
.modal-card { width: min(480px, 100%); background: #1e293b; border-radius: 16px; padding: 22px; border: 1px solid rgba(148,163,184,.2); }
.modal-card h3 { margin: 0 0 14px; color: #f8fafc; }
.modal-card label { display: block; font-size: 11px; color: #94a3b8; text-transform: uppercase; margin: 10px 0 4px; }
.modal-card input, .modal-card select { width: 100%; padding: 10px 12px; border-radius: 10px; border: 1px solid rgba(148,163,184,.25); background: #0f172a; color: #f8fafc; box-sizing: border-box; }
.detail-grid { display: grid; gap: 10px; }
.detail-grid span { color: #f8fafc; font-size: 14px; }
.modal-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px; }
.btn-primary { padding: 10px 16px; border: none; border-radius: 10px; background: #f59e0b; color: #fff; cursor: pointer; font-weight: 600; }
.btn-secondary { padding: 10px 16px; border: none; border-radius: 10px; background: rgba(148,163,184,.15); color: #e2e8f0; cursor: pointer; }
.toast { position: fixed; right: 20px; bottom: 20px; padding: 12px 16px; border-radius: 12px; background: #0f172a; color: #f8fafc; border: 1px solid #38bdf8; z-index: 1100; }

@media (max-width: 768px) {
  .section-header { flex-direction: column; align-items: flex-start; }
  .inter-left { width: 100%; }
  .table { display: block; overflow-x: auto; white-space: nowrap; }
  .toast { left: 16px; right: 16px; bottom: 16px; }
}

@media (max-width: 480px) {
  .page-top h1 { font-size: 18px; }
}
</style>
