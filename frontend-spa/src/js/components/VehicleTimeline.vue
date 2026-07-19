<template>
  <div class="vehicle-timeline page">
    <div class="page-top">
      <div>
        <button class="btn-back" @click="$router.push('/vehicles')">
          <i class="fas fa-arrow-left"></i> Retour
        </button>
        <h1><i class="fas fa-history"></i> Timeline véhicule</h1>
        <p v-if="vehicleLabel">{{ vehicleLabel }}</p>
      </div>
      <button class="btn-refresh" @click="loadTimeline" :disabled="loading">
        <i class="fas fa-sync-alt" :class="{ 'fa-spin': loading }"></i>
        {{ loading ? 'Chargement…' : 'Actualiser' }}
      </button>
    </div>

    <div v-if="loading" class="loading">
      <i class="fas fa-spinner fa-spin"></i> Chargement de la timeline…
    </div>

    <div v-else-if="hasData" class="timeline-container">
      <div class="timeline-section">
        <h3><i class="fas fa-cubes"></i> Événements blockchain</h3>
        <div v-if="!blockchainEvents.length" class="empty">Aucun événement certifié.</div>
        <div v-for="item in blockchainEvents" :key="item.id" class="timeline-item blockchain">
          <div class="timeline-marker"></div>
          <div class="timeline-content">
            <div class="timeline-date">{{ formatDateTime(item.timestamp) }}</div>
            <div class="timeline-title">{{ item.type }}</div>
            <div class="timeline-description">{{ item.description }}</div>
            <div v-if="item.hash" class="timeline-hash">TX : {{ shortHash(item.hash) }}</div>
          </div>
        </div>
      </div>

      <div class="timeline-section">
        <h3><i class="fas fa-clipboard-list"></i> Historique administratif</h3>
        <div v-if="!adminEvents.length" class="empty">Aucun enregistrement administratif.</div>
        <div v-for="item in adminEvents" :key="item.id" class="timeline-item admin">
          <div class="timeline-marker admin"></div>
          <div class="timeline-content">
            <div class="timeline-date">{{ formatDateTime(item.date) }}</div>
            <div class="timeline-title">{{ item.type }}</div>
            <div class="timeline-description">{{ item.description }}</div>
            <div v-if="item.user" class="timeline-user">Par {{ item.user }}</div>
          </div>
        </div>
      </div>

      <div class="timeline-section">
        <h3><i class="fas fa-file-alt"></i> Documents</h3>
        <div v-if="!documents.length" class="empty">Aucun document.</div>
        <div v-for="doc in documents" :key="doc.id" class="timeline-item document">
          <div class="timeline-marker document"></div>
          <div class="timeline-content">
            <div class="timeline-date">{{ formatDate(doc.uploaded_at) }}</div>
            <div class="timeline-title">{{ doc.document_type || doc.type || 'Document' }}</div>
            <div class="timeline-description">{{ doc.file_name || doc.name }}</div>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="no-data">
      <i class="fas fa-inbox"></i>
      <p>Aucune donnée de timeline pour ce véhicule.</p>
      <button class="btn-refresh" @click="loadTimeline">Réessayer</button>
    </div>

    <div v-if="toast.show" class="toast">{{ toast.message }}</div>
  </div>
</template>

<script>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useStore } from 'vuex'
import { fetchVehiclesHybrid } from '../utils/dataService'

export default {
  name: 'VehicleTimeline',
  setup() {
    const route = useRoute()
    const router = useRouter()
    const store = useStore()
    const loading = ref(false)
    const timelineData = ref(null)
    const vehicleLabel = ref('')
    const toast = ref({ show: false, message: '' })

    const vehicleId = computed(() => {
      const raw = route.params.id
      const n = Number(raw)
      return Number.isFinite(n) ? n : raw
    })

    const blockchainEvents = computed(() => {
      const bc = timelineData.value?.blockchain
      if (!bc) return []
      const events = []
      ;(bc.maintenances || bc.maintenance || []).forEach((m, i) => {
        events.push({
          id: `bc-m-${m.id || i}`,
          type: m.type || 'Maintenance',
          description: m.description || 'Intervention certifiée',
          timestamp: m.performed_at || m.timestamp || m.date,
          hash: m.blockchain_tx_hash || m.hash || m.tx_hash
        })
      })
      ;(bc.mileage || bc.mileage_records || []).forEach((m, i) => {
        events.push({
          id: `bc-km-${m.id || i}`,
          type: 'Kilométrage',
          description: `Relevé : ${m.mileage || m.new_mileage || '?'} km`,
          timestamp: m.recorded_at || m.timestamp || m.date,
          hash: m.blockchain_tx_hash || m.hash
        })
      })
      ;(bc.events || []).forEach((e, i) => {
        events.push({
          id: `bc-e-${e.id || i}`,
          type: e.type || e.typeLabel || 'Événement',
          description: e.description || '',
          timestamp: e.timestamp || e.date,
          hash: e.hash || e.txHash
        })
      })
      return events.sort((a, b) => new Date(b.timestamp || 0) - new Date(a.timestamp || 0))
    })

    const adminEvents = computed(() => {
      const local = timelineData.value?.local || timelineData.value?.admin || {}
      const list = local.events || local.records || []
      if (Array.isArray(list) && list.length) {
        return list.map((item, i) => ({
          id: item.id || `admin-${i}`,
          type: item.type || 'Événement',
          description: item.description || '',
          date: item.date || item.created_at,
          user: item.user || item.author
        }))
      }
      // Fallback depuis maintenances / fuel locaux
      const events = []
      ;(local.maintenances || []).forEach((m, i) => {
        events.push({
          id: `loc-m-${m.id || i}`,
          type: 'Maintenance',
          description: m.description || m.type || 'Intervention',
          date: m.performed_at || m.date,
          user: m.garage?.name || m.mechanic
        })
      })
      ;(local.fuel || local.fuel_records || []).forEach((f, i) => {
        events.push({
          id: `loc-f-${f.id || i}`,
          type: 'Carburant',
          description: `${f.fuel_amount || f.liters || '?'} L — ${f.mileage || f.km || '?'} km`,
          date: f.refueled_at || f.date,
          user: f.recorded_by
        })
      })
      return events.sort((a, b) => new Date(b.date || 0) - new Date(a.date || 0))
    })

    const documents = computed(() => {
      const docs = timelineData.value?.local?.documents || timelineData.value?.documents || []
      return Array.isArray(docs) ? docs : []
    })

    const hasData = computed(
      () => blockchainEvents.value.length || adminEvents.value.length || documents.value.length
    )

    const showToast = (message) => {
      toast.value = { show: true, message }
      setTimeout(() => { toast.value.show = false }, 2500)
    }

    const formatDate = (date) => {
      if (!date) return 'N/A'
      return new Date(date).toLocaleDateString('fr-FR')
    }

    const formatDateTime = (date) => {
      if (!date) return 'N/A'
      const d = new Date(date)
      if (Number.isNaN(d.getTime())) return String(date)
      return d.toLocaleString('fr-FR')
    }

    const shortHash = (hash) => {
      if (!hash) return ''
      const s = String(hash)
      return s.length > 18 ? `${s.slice(0, 10)}…${s.slice(-6)}` : s
    }

    const resolveVehicleLabel = async () => {
      try {
        const result = await fetchVehiclesHybrid()
        const found = (result.data || []).find((v) => String(v.id) === String(vehicleId.value))
        if (found) {
          vehicleLabel.value = `${found.brand} ${found.model} — ${found.licensePlate}`
        }
      } catch {
        vehicleLabel.value = `Véhicule #${vehicleId.value}`
      }
    }

    const loadTimeline = async () => {
      if (!vehicleId.value) {
        showToast('Identifiant véhicule manquant')
        return
      }
      loading.value = true
      try {
        timelineData.value = await store.dispatch('vehicles/getVehicleTimeline', vehicleId.value)
        if (!timelineData.value) {
          timelineData.value = { blockchain: {}, local: {} }
        }
      } catch (error) {
        console.error(error)
        timelineData.value = null
        showToast('Impossible de charger la timeline')
      } finally {
        loading.value = false
      }
    }

    onMounted(async () => {
      if (!route.params.id) {
        router.push('/vehicles')
        return
      }
      await resolveVehicleLabel()
      await loadTimeline()
    })

    watch(() => route.params.id, async () => {
      await resolveVehicleLabel()
      await loadTimeline()
    })

    return {
      loading,
      vehicleLabel,
      blockchainEvents,
      adminEvents,
      documents,
      hasData,
      toast,
      loadTimeline,
      formatDate,
      formatDateTime,
      shortHash
    }
  }
}
</script>

<style scoped>
.page { max-width: 900px; color: #e2e8f0; }
.page-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
.btn-back { background: transparent; border: none; color: #94a3b8; cursor: pointer; margin-bottom: 8px; padding: 0; }
.btn-back:hover { color: #38bdf8; }
h1 { font-size: 22px; color: #f8fafc; margin: 0; }
h1 i { color: #a78bfa; margin-right: 8px; }
.page-top p { color: #94a3b8; margin: 6px 0 0; font-size: 13px; }
.btn-refresh { padding: 10px 14px; border: none; border-radius: 10px; background: #7c3aed; color: #fff; cursor: pointer; font-weight: 600; }
.btn-refresh:disabled { opacity: 0.7; cursor: wait; }
.loading, .no-data, .empty { text-align: center; padding: 28px; color: #94a3b8; }
.no-data i { font-size: 36px; margin-bottom: 10px; display: block; color: #64748b; }
.timeline-section { background: #1e293b; border-radius: 14px; padding: 18px; margin-bottom: 14px; border: 1px solid rgba(148,163,184,.12); }
.timeline-section h3 { margin: 0 0 14px; font-size: 15px; color: #f8fafc; }
.timeline-item { display: flex; gap: 12px; padding: 12px 0; border-bottom: 1px solid rgba(148,163,184,.08); }
.timeline-item:last-child { border-bottom: none; }
.timeline-marker { width: 12px; height: 12px; border-radius: 50%; margin-top: 6px; background: #34d399; box-shadow: 0 0 10px rgba(52,211,153,.5); flex-shrink: 0; }
.timeline-marker.admin { background: #38bdf8; box-shadow: 0 0 10px rgba(56,189,248,.5); }
.timeline-marker.document { background: #fbbf24; box-shadow: 0 0 10px rgba(251,191,36,.4); }
.timeline-date { font-size: 11px; color: #94a3b8; }
.timeline-title { font-weight: 600; color: #f8fafc; margin: 2px 0; }
.timeline-description { font-size: 13px; color: #cbd5e1; }
.timeline-hash, .timeline-user { font-size: 11px; color: #a78bfa; margin-top: 4px; }
.toast { position: fixed; right: 20px; bottom: 20px; padding: 12px 16px; border-radius: 12px; background: #0f172a; color: #f8fafc; border: 1px solid #a78bfa; z-index: 1100; }
@media (max-width: 768px) {
  .page-top { flex-direction: column; }
}
</style>
