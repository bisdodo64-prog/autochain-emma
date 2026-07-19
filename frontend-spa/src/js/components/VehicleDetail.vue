<template>
  <div class="vehicle-detail-page">
    <div class="detail-header" :style="{ background: headerGradient }">
      <button class="btn-back" @click="$router.push('/vehicles')">
        <i class="fas fa-arrow-left"></i>
      </button>
      <div class="header-inner">
        <div class="vehicle-title">
          <div class="vehicle-icon-large">
            <i class="fas fa-car"></i>
          </div>
          <div>
            <h1>{{ vehicle.brand }} {{ vehicle.model }}</h1>
            <div class="title-meta">
              <span class="vehicle-year">{{ vehicle.year }}</span>
              <span class="vehicle-plate">{{ vehicle.licensePlate }}</span>
            </div>
          </div>
        </div>
        <div class="header-actions">
          <span class="status-pill">
            <i :class="vehicle.statusIcon"></i> {{ vehicle.status }}
          </span>
          <span class="blockchain-badge" v-if="vehicle.blockchainVerified">
            <i class="fas fa-shield-alt"></i> Certifié Blockchain
          </span>
        </div>
      </div>
    </div>

    <div class="detail-content">
      <p v-if="dataSource" class="source-hint">{{ dataSource }}</p>
      <div v-if="loading" class="loading-hint"><i class="fas fa-spinner fa-spin"></i> Chargement du véhicule...</div>
      <div class="info-grid">
        <div class="info-card" v-for="card in infoCards" :key="card.label">
          <div class="info-icon" :style="{ background: card.bg, color: card.color }">
            <i :class="card.icon"></i>
          </div>
          <div class="info-text">
            <span class="info-label">{{ card.label }}</span>
            <span class="info-value">{{ card.value }}</span>
          </div>
        </div>
      </div>

      <div class="tabs">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          :class="{ active: activeTab === tab.id }"
          @click="activeTab = tab.id"
        >
          <i :class="tab.icon"></i> {{ tab.label }}
        </button>
      </div>

      <div class="tab-content">
        <!-- Blockchain -->
        <div v-if="activeTab === 'blockchain'" class="blockchain-timeline">
          <p v-if="!blockchainEvents.length" class="empty-hint">Aucun événement blockchain certifié pour ce véhicule.</p>
          <div class="timeline">
            <div
              v-for="(event, index) in blockchainEvents"
              :key="index"
              class="timeline-item clickable"
              @click="showEventDetails(event)"
            >
              <div class="timeline-dot" :class="event.type"></div>
              <div class="timeline-content">
                <div class="timeline-header">
                  <span class="event-type">{{ event.typeLabel }}</span>
                  <span class="event-date">{{ event.date }}</span>
                </div>
                <p class="event-desc">{{ event.description }}</p>
                <div class="event-details">
                  <span class="event-author"><i class="fas fa-user"></i> {{ event.author }}</span>
                  <span class="tx-hash" @click.stop="copyHash(event.txHash)">
                    <i class="fas fa-cube"></i> {{ shortenHash(event.txHash) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Maintenance -->
        <div v-if="activeTab === 'maintenance'" class="maintenance-section">
          <div class="panel-card">
            <h3><i class="fas fa-tools"></i> Prochains entretiens</h3>
            <div class="maintenance-list">
              <div v-for="item in upcomingMaintenance" :key="item.id" class="maintenance-item">
                <div class="maintenance-status" :class="item.urgency">
                  <i class="fas fa-circle"></i>
                </div>
                <div>
                  <strong>{{ item.type }}</strong>
                  <p>Prévu dans {{ item.dueIn }} km — {{ item.estimatedDate }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Documents -->
        <div v-if="activeTab === 'documents'" class="documents-section">
          <div class="documents-grid">
            <div v-for="doc in documents" :key="doc.id" class="document-card">
              <div class="doc-icon"><i :class="doc.icon"></i></div>
              <div class="doc-info">
                <strong>{{ doc.name }}</strong>
                <small>{{ doc.type }}</small>
                <span class="doc-date">Expire le {{ doc.expiryDate }}</span>
              </div>
              <div class="doc-actions">
                <button class="btn-doc" title="Voir" @click="viewDoc(doc)"><i class="fas fa-eye"></i></button>
                <button class="btn-doc" title="Télécharger PDF" @click="downloadDoc(doc)"><i class="fas fa-download"></i></button>
                <span v-if="doc.blockchainVerified" class="verified-badge"><i class="fas fa-check-circle"></i></span>
              </div>
            </div>
          </div>
        </div>

        <!-- Consommation -->
        <div v-if="activeTab === 'consumption'" class="consumption-section">
          <div class="stats-row">
            <div class="stat-box">
              <span class="stat-label">Moyenne</span>
              <span class="stat-value">{{ consumption.avg }} L/100km</span>
            </div>
            <div class="stat-box">
              <span class="stat-label">Ce mois</span>
              <span class="stat-value">{{ consumption.month }} L</span>
            </div>
            <div class="stat-box">
              <span class="stat-label">Coût estimé</span>
              <span class="stat-value">{{ formatFcfa(consumption.cost) }}</span>
            </div>
            <div class="stat-box">
              <span class="stat-label">Dernier plein</span>
              <span class="stat-value">{{ consumption.lastFill }}</span>
            </div>
          </div>
          <div class="panel-card">
            <h3><i class="fas fa-gas-pump"></i> Historique carburant</h3>
            <table class="data-table">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Litres</th>
                  <th>Prix</th>
                  <th>Station</th>
                  <th>Km</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in fuelHistory" :key="row.id">
                  <td>{{ row.date }}</td>
                  <td>{{ row.liters }} L</td>
                  <td>{{ formatMoney(row.price) }}</td>
                  <td>{{ row.station }}</td>
                  <td>{{ formatNumber(row.km) }} km</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal événement blockchain -->
    <div v-if="eventModal.show" class="modal-overlay" @click.self="eventModal.show = false">
      <div class="modal-card">
        <h3>{{ eventModal.event?.typeLabel }}</h3>
        <p>{{ eventModal.event?.description }}</p>
        <div class="modal-meta">
          <div><label>Date</label><span>{{ eventModal.event?.date }}</span></div>
          <div><label>Auteur</label><span>{{ eventModal.event?.author }}</span></div>
          <div><label>Transaction</label><span class="mono">{{ eventModal.event?.txHash }}</span></div>
        </div>
        <div class="modal-actions">
          <button class="btn-secondary" @click="copyHash(eventModal.event?.txHash)">Copier le hash</button>
          <button class="btn-primary" @click="eventModal.show = false">Fermer</button>
        </div>
      </div>
    </div>

    <!-- Modal document -->
    <div v-if="docModal.show" class="modal-overlay" @click.self="docModal.show = false">
      <div class="modal-card">
        <h3><i :class="docModal.doc?.icon"></i> {{ docModal.doc?.name }}</h3>
        <div class="modal-meta">
          <div><label>Type</label><span>{{ docModal.doc?.type }}</span></div>
          <div><label>Expiration</label><span>{{ docModal.doc?.expiryDate }}</span></div>
          <div><label>Véhicule</label><span>{{ vehicle.brand }} {{ vehicle.model }}</span></div>
          <div><label>Blockchain</label><span>{{ docModal.doc?.blockchainVerified ? 'Certifié' : 'Non certifié' }}</span></div>
        </div>
        <div class="modal-actions">
          <button class="btn-secondary" @click="downloadDoc(docModal.doc)">Télécharger PDF</button>
          <button class="btn-primary" @click="docModal.show = false">Fermer</button>
        </div>
      </div>
    </div>

    <div v-if="toast.show" class="toast" :class="toast.type">
      <i :class="toast.icon"></i>
      <span>{{ toast.message }}</span>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { downloadPdf } from '../utils/pdf'
import { fetchVehicleDetailHybrid, SOURCE_LABELS } from '../utils/dataService'
import { formatFcfa, toFcfa } from '../utils/currency'

export default {
  name: 'VehicleDetail',
  setup() {
    const route = useRoute()
    const activeTab = ref('blockchain')
    const loading = ref(true)
    const dataSource = ref('')
    const eventModal = ref({ show: false, event: null })
    const docModal = ref({ show: false, doc: null })
    const toast = ref({ show: false, message: '', type: 'success', icon: 'fas fa-check-circle' })

    const vehicle = ref({
      id: 1,
      brand: '—',
      model: '—',
      year: '—',
      licensePlate: '—',
      mileage: 0,
      fuelType: 'Hybride',
      hybridType: 'Hybride',
      serviceDate: '—',
      transmission: 'Automatique',
      owner: '—',
      status: 'Disponible',
      statusClass: 'available',
      statusIcon: 'fas fa-check-circle',
      blockchainVerified: false
    })

    const tabs = [
      { id: 'blockchain', label: 'Historique Blockchain', icon: 'fas fa-cubes' },
      { id: 'maintenance', label: 'Maintenance', icon: 'fas fa-tools' },
      { id: 'documents', label: 'Documents', icon: 'fas fa-file-alt' },
      { id: 'consumption', label: 'Consommation', icon: 'fas fa-gas-pump' }
    ]

    const blockchainEvents = ref([])
    const documents = ref([])
    const fuelHistory = ref([])
    const maintenanceRecords = ref([])

    const consumption = computed(() => ({
      avg: '5.2',
      month: '125',
      cost: 153270,
      lastFill: fuelHistory.value[0]?.date || '—'
    }))

    const loadVehicle = async () => {
      loading.value = true
      const result = await fetchVehicleDetailHybrid(route.params.id)
      vehicle.value = result.vehicle
      documents.value = result.documents
      fuelHistory.value = result.fuelHistory
      blockchainEvents.value = result.blockchainEvents
      maintenanceRecords.value = result.maintenances || []
      dataSource.value = SOURCE_LABELS[result.source] || ''
      loading.value = false
    }

    const infoCards = computed(() => [
      { label: 'Kilométrage', value: `${formatNumber(vehicle.value.mileage)} km`, icon: 'fas fa-tachometer-alt', bg: '#dbeafe', color: '#2563eb' },
      { label: 'Prix d\'achat', value: vehicle.value.purchasePrice ? formatFcfa(vehicle.value.purchasePrice) : '—', icon: 'fas fa-coins', bg: '#fef3c7', color: '#f59e0b' },
      { label: 'Carburant', value: vehicle.value.fuelType, icon: 'fas fa-gas-pump', bg: '#d1fae5', color: '#10b981' },
      { label: 'Mise en service', value: vehicle.value.serviceDate, icon: 'fas fa-calendar-alt', bg: '#fef3c7', color: '#f59e0b' },
      { label: 'Transmission', value: vehicle.value.transmission, icon: 'fas fa-cog', bg: '#ede9fe', color: '#7c3aed' },
      { label: 'Propriétaire', value: vehicle.value.owner, icon: 'fas fa-user', bg: '#e0f2fe', color: '#0284c7' }
    ])

    const upcomingMaintenance = computed(() => {
      if (maintenanceRecords.value.length) {
        return maintenanceRecords.value.slice(0, 3).map((record, index) => ({
          id: record.id || index,
          type: record.type || record.description?.slice(0, 32) || 'Maintenance',
          dueIn: record.mileage_at_maintenance || 0,
          estimatedDate: record.performed_at
            ? new Date(record.performed_at).toLocaleDateString('fr-FR')
            : '—',
          urgency: index === 0 ? 'warning' : 'info'
        }))
      }
      const mileage = Number(vehicle.value.mileage || 0)
      return [
        { id: 1, type: 'Vidange moteur', dueIn: Math.max(0, 5000 - (mileage % 5000)), estimatedDate: 'Août 2026', urgency: 'warning' },
        { id: 2, type: 'Contrôle technique', dueIn: Math.max(0, 15000 - (mileage % 15000)), estimatedDate: 'Décembre 2026', urgency: 'info' }
      ]
    })

    const headerGradient = computed(() => {
      const gradients = {
        available: 'linear-gradient(135deg, #0ea5e9 0%, #0369a1 100%)',
        mission: 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)',
        maintenance: 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)'
      }
      return gradients[vehicle.value.statusClass] || 'linear-gradient(135deg, #334155 0%, #0f172a 100%)'
    })

    const showToast = (message, type = 'info') => {
      toast.value = {
        show: true,
        message,
        type,
        icon: type === 'success' ? 'fas fa-check-circle' : type === 'error' ? 'fas fa-times-circle' : 'fas fa-info-circle'
      }
      setTimeout(() => (toast.value.show = false), 2500)
    }

    const formatNumber = (num) => String(num || 0).replace(/\B(?=(\d{3})+(?!\d))/g, ' ')
    const formatMoney = (amount) => formatFcfa(toFcfa(amount))
    const shortenHash = (hash) => (hash ? hash.slice(0, 8) + '…' + hash.slice(-6) : '')
    const copyHash = async (hash) => {
      if (!hash) return
      try {
        await navigator.clipboard.writeText(hash)
        showToast('Hash copié dans le presse-papiers.', 'success')
      } catch {
        showToast('Impossible de copier le hash.', 'error')
      }
    }

    const showEventDetails = (event) => {
      eventModal.value = { show: true, event }
    }

    const viewDoc = (doc) => {
      docModal.value = { show: true, doc: { ...doc, vehicle: `${vehicle.value.brand} ${vehicle.value.model}` } }
    }

    const downloadDoc = (doc) => {
      if (!doc) return
      downloadPdf(
        {
          name: doc.name,
          vehicle: doc.vehicle || `${vehicle.value.brand} ${vehicle.value.model}`,
          type: doc.type,
          expiry: doc.expiryDate,
          blockchainVerified: doc.blockchainVerified
        },
        `${doc.name}.pdf`
      )
      showToast(`Téléchargement de ${doc.name}.pdf`, 'success')
    }

    onMounted(loadVehicle)

    return {
      vehicle,
      loading,
      dataSource,
      activeTab,
      tabs,
      blockchainEvents,
      upcomingMaintenance,
      documents,
      fuelHistory,
      consumption,
      infoCards,
      headerGradient,
      formatNumber,
      formatFcfa,
      formatMoney,
      shortenHash,
      copyHash,
      showEventDetails,
      viewDoc,
      downloadDoc,
      eventModal,
      docModal,
      toast
    }
  }
}
</script>

<style scoped>
.vehicle-detail-page {
  min-height: 100vh;
  background: transparent;
  color: #e2e8f0;
}

.detail-header {
  padding: 36px 28px 48px;
  color: #fff;
  border-radius: 18px;
  margin-bottom: 0;
}

.header-inner {
  max-width: 1100px;
  margin: 0 auto;
}

.btn-back {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: 1px solid rgba(255, 255, 255, 0.35);
  background: rgba(255, 255, 255, 0.12);
  color: #fff;
  cursor: pointer;
  margin-bottom: 16px;
}

.vehicle-title {
  display: flex;
  align-items: center;
  gap: 18px;
}

.vehicle-icon-large {
  width: 72px;
  height: 72px;
  background: rgba(255, 255, 255, 0.18);
  border-radius: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 30px;
}

.vehicle-title h1 {
  margin: 0;
  font-size: 26px;
  color: #fff;
}

.title-meta {
  display: flex;
  gap: 8px;
  margin-top: 8px;
}

.vehicle-year,
.vehicle-plate {
  padding: 4px 12px;
  border-radius: 999px;
  font-size: 13px;
  background: rgba(255, 255, 255, 0.18);
}

.vehicle-plate {
  font-family: ui-monospace, monospace;
  letter-spacing: 1px;
}

.header-actions {
  display: flex;
  gap: 10px;
  margin-top: 16px;
  flex-wrap: wrap;
}

.status-pill,
.blockchain-badge {
  padding: 8px 14px;
  border-radius: 999px;
  font-size: 13px;
  background: rgba(255, 255, 255, 0.16);
}

.blockchain-badge {
  background: rgba(16, 185, 129, 0.35);
  border: 1px solid rgba(16, 185, 129, 0.5);
}

.detail-content {
  max-width: 1100px;
  margin: -28px auto 0;
  padding: 0 8px 40px;
  position: relative;
  z-index: 1;
}

.source-hint, .loading-hint, .empty-hint {
  font-size: 12px;
  color: #94a3b8;
  margin-bottom: 12px;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 14px;
  margin-bottom: 20px;
}

.info-card {
  background: #1e293b;
  border: 1px solid rgba(148, 163, 184, 0.15);
  border-radius: 16px;
  padding: 18px;
  display: flex;
  align-items: center;
  gap: 14px;
}

.info-icon {
  width: 46px;
  height: 46px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  flex-shrink: 0;
}

.info-label {
  display: block;
  font-size: 11px;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.info-value {
  display: block;
  font-size: 16px;
  font-weight: 700;
  color: #f8fafc;
  margin-top: 2px;
}

.tabs {
  display: flex;
  gap: 6px;
  background: #1e293b;
  border: 1px solid rgba(148, 163, 184, 0.15);
  border-radius: 14px;
  padding: 6px;
  margin-bottom: 18px;
  flex-wrap: wrap;
}

.tabs button {
  flex: 1;
  min-width: 140px;
  padding: 11px 14px;
  border: none;
  background: transparent;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  color: #94a3b8;
}

.tabs button.active {
  background: #0ea5e9;
  color: #fff;
}

.panel-card,
.timeline-content,
.document-card,
.stat-box {
  background: #1e293b;
  border: 1px solid rgba(148, 163, 184, 0.15);
  border-radius: 14px;
}

.panel-card {
  padding: 20px;
}

.panel-card h3 {
  margin: 0 0 16px;
  font-size: 15px;
  color: #f8fafc;
}

.panel-card h3 i {
  color: #38bdf8;
  margin-right: 8px;
}

.timeline {
  position: relative;
  padding-left: 28px;
}

.timeline::before {
  content: '';
  position: absolute;
  left: 7px;
  top: 0;
  bottom: 0;
  width: 2px;
  background: rgba(148, 163, 184, 0.25);
}

.timeline-item {
  position: relative;
  margin-bottom: 16px;
  cursor: pointer;
}

.timeline-dot {
  position: absolute;
  left: -25px;
  top: 16px;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  border: 2px solid #0f172a;
}

.timeline-dot.registration { background: #10b981; }
.timeline-dot.mileage { background: #38bdf8; }
.timeline-dot.maintenance { background: #f59e0b; }

.timeline-content {
  padding: 14px 16px;
  transition: border-color 0.2s;
}

.timeline-item:hover .timeline-content {
  border-color: #38bdf8;
}

.timeline-header {
  display: flex;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 6px;
}

.event-type {
  font-weight: 600;
  font-size: 14px;
  color: #f8fafc;
}

.event-date {
  font-size: 12px;
  color: #94a3b8;
}

.event-desc {
  font-size: 13px;
  color: #cbd5e1;
  margin: 0 0 8px;
}

.event-details {
  display: flex;
  gap: 14px;
  flex-wrap: wrap;
  font-size: 12px;
}

.event-author {
  color: #94a3b8;
}

.tx-hash {
  font-family: ui-monospace, monospace;
  color: #38bdf8;
  cursor: pointer;
}

.tx-hash:hover {
  text-decoration: underline;
}

.maintenance-item {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px;
  border-radius: 12px;
  background: rgba(15, 23, 42, 0.55);
  margin-bottom: 10px;
}

.maintenance-item strong {
  display: block;
  color: #f8fafc;
  font-size: 14px;
}

.maintenance-item p {
  margin: 2px 0 0;
  font-size: 12px;
  color: #94a3b8;
}

.maintenance-status.warning { color: #f59e0b; }
.maintenance-status.info { color: #38bdf8; }
.maintenance-status.success { color: #10b981; }

.documents-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 12px;
}

.document-card {
  padding: 16px;
  display: flex;
  align-items: center;
  gap: 12px;
}

.doc-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: rgba(56, 189, 248, 0.15);
  color: #38bdf8;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
}

.doc-info {
  flex: 1;
}

.doc-info strong {
  display: block;
  color: #f8fafc;
  font-size: 14px;
}

.doc-info small,
.doc-date {
  display: block;
  color: #94a3b8;
  font-size: 11px;
}

.btn-doc {
  width: 34px;
  height: 34px;
  border-radius: 8px;
  border: none;
  background: rgba(148, 163, 184, 0.15);
  color: #e2e8f0;
  cursor: pointer;
}

.btn-doc:hover {
  background: #0ea5e9;
  color: #fff;
}

.verified-badge {
  color: #10b981;
}

.stats-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 12px;
  margin-bottom: 16px;
}

.stat-box {
  padding: 16px;
}

.stat-label {
  display: block;
  font-size: 11px;
  color: #94a3b8;
  text-transform: uppercase;
}

.stat-value {
  display: block;
  margin-top: 6px;
  font-size: 18px;
  font-weight: 700;
  color: #f8fafc;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th {
  text-align: left;
  padding: 10px;
  font-size: 11px;
  color: #94a3b8;
  text-transform: uppercase;
  border-bottom: 1px solid rgba(148, 163, 184, 0.2);
}

.data-table td {
  padding: 10px;
  border-bottom: 1px solid rgba(148, 163, 184, 0.12);
  font-size: 13px;
  color: #e2e8f0;
}

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(2, 6, 23, 0.72);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 16px;
}

.modal-card {
  width: min(520px, 100%);
  background: #1e293b;
  border: 1px solid rgba(148, 163, 184, 0.2);
  border-radius: 16px;
  padding: 22px;
  color: #e2e8f0;
}

.modal-card h3 {
  margin: 0 0 10px;
  color: #f8fafc;
}

.modal-card p {
  color: #cbd5e1;
  margin: 0 0 16px;
}

.modal-meta {
  display: grid;
  gap: 10px;
  margin-bottom: 18px;
}

.modal-meta label {
  display: block;
  font-size: 11px;
  color: #94a3b8;
  text-transform: uppercase;
}

.modal-meta span {
  color: #f8fafc;
  font-size: 13px;
}

.mono {
  font-family: ui-monospace, monospace;
  word-break: break-all;
  font-size: 11px !important;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}

.btn-primary,
.btn-secondary {
  padding: 10px 16px;
  border-radius: 10px;
  border: none;
  cursor: pointer;
  font-weight: 600;
}

.btn-primary {
  background: #0ea5e9;
  color: #fff;
}

.btn-secondary {
  background: rgba(148, 163, 184, 0.15);
  color: #e2e8f0;
}

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

@media (max-width: 768px) {
  .vehicle-title { flex-direction: column; align-items: flex-start; }
  .tabs button { flex: auto; }
}
</style>
