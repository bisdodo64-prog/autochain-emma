<template>
  <div class="page">
    <div class="page-top">
      <div>
        <h1><i class="fas fa-users"></i> Chauffeurs</h1>
        <p>Gestion des chauffeurs et affectations</p>
      </div>
      <button class="btn-add" @click="openAdd"><i class="fas fa-plus"></i> Ajouter un chauffeur</button>
    </div>

    <div class="drivers-grid">
      <div v-for="driver in drivers" :key="driver.id" class="driver-card">
        <div class="driver-top">
          <div
            class="driver-avatar"
            :style="{ background: showDriverAvatar(driver) ? 'transparent' : driver.color }"
          >
            <img
              v-if="showDriverAvatar(driver)"
              :src="driver.avatarUrl"
              :alt="driver.name"
              @error="markDriverAvatarFailed(driver.id)"
            />
            <template v-else>{{ driver.initials }}</template>
          </div>
          <div class="driver-status" :class="driver.active ? 'online' : 'offline'">
            <i class="fas fa-circle"></i> {{ driver.active ? 'En ligne' : 'Hors ligne' }}
          </div>
        </div>
        <h3>{{ driver.name }}</h3>
        <span class="driver-role">{{ driver.role }}</span>
        <div class="driver-info">
          <div><i class="fas fa-envelope"></i> {{ driver.email }}</div>
          <div><i class="fas fa-phone"></i> {{ driver.phone }}</div>
        </div>
        <div class="driver-vehicle" v-if="driver.vehicle">
          <i class="fas fa-car-side"></i>
          <span>{{ driver.vehicle }}</span>
        </div>
        <div class="driver-vehicle empty" v-else>
          <i class="fas fa-car-side"></i>
          <span>Aucun véhicule assigné</span>
        </div>
        <div class="driver-stats">
          <div><strong>{{ driver.missions }}</strong><small>Missions</small></div>
          <div><strong>{{ driver.km }}</strong><small>Parcourus</small></div>
          <div><strong>{{ driver.rating }}/5</strong><small>Note</small></div>
        </div>
        <div class="driver-actions">
          <button @click="openEdit(driver)"><i class="fas fa-edit"></i> Modifier</button>
          <button @click="openAssign(driver)"><i class="fas fa-car"></i> Assigner</button>
          <button @click="openHistory(driver)"><i class="fas fa-history"></i> Historique</button>
        </div>
      </div>
    </div>

    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal-card">
        <h3>
          {{ modalMode === 'add' ? 'Nouveau chauffeur' : modalMode === 'edit' ? 'Modifier le chauffeur' : modalMode === 'assign' ? 'Assigner un véhicule' : 'Historique' }}
        </h3>

        <template v-if="modalMode === 'add' || modalMode === 'edit'">
          <label>Nom</label>
          <input v-model="form.name" />
          <label>Email</label>
          <input v-model="form.email" />
          <label>Téléphone</label>
          <input v-model="form.phone" />
        </template>

        <template v-else-if="modalMode === 'assign'">
          <p class="hint">Assigner un véhicule à <strong>{{ selected?.name }}</strong></p>
          <label>Véhicule</label>
          <select v-model="form.vehicle">
            <option value="">Aucun</option>
            <option v-for="v in vehicleOptions" :key="v">{{ v }}</option>
          </select>
        </template>

        <template v-else>
          <div v-for="(item, idx) in selectedHistory" :key="idx" class="hist-item">
            <strong>{{ item.action }}</strong>
            <small>{{ item.date }}</small>
          </div>
        </template>

        <div class="modal-actions">
          <button class="btn-secondary" @click="closeModal">Fermer</button>
          <button v-if="modalMode !== 'history'" class="btn-primary" @click="saveModal">Enregistrer</button>
        </div>
      </div>
    </div>

    <div v-if="toast.show" class="toast">{{ toast.message }}</div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import api from '../js/api'
import { loadDrivers, saveDrivers } from '../js/utils/localData'
import { fetchDriversHybrid, fetchVehiclesHybrid } from '../js/utils/dataService'
import { resolveAvatarUrl } from '../js/utils/avatarUrl'

export default {
  name: 'Drivers',
  setup() {
    const showModal = ref(false)
    const modalMode = ref('add')
    const selected = ref(null)
    const toast = ref({ show: false, message: '' })
    const form = ref({ name: '', email: '', phone: '', vehicle: '' })
    const selectedHistory = ref([])

    const vehicleOptions = ref([
      'Renault Clio (CC-456-DD)',
      'BMW Serie 3 (II-345-JJ)',
      'Toyota Corolla (AA-123-BB)',
      'Peugeot 308 (EE-789-FF)',
      'Citroën C3 (GG-012-HH)'
    ])

    const drivers = ref(loadDrivers())
    const vehiclesList = ref([])
    const dataSource = ref('local')
    const failedAvatars = ref({})
    const persist = () => saveDrivers(drivers.value)

    const showDriverAvatar = (driver) =>
      Boolean(driver.avatarUrl) && !failedAvatars.value[driver.id]

    const markDriverAvatarFailed = (id) => {
      failedAvatars.value = { ...failedAvatars.value, [id]: true }
    }

    const loadData = async () => {
      const [driversResult, vehiclesResult] = await Promise.all([
        fetchDriversHybrid(),
        fetchVehiclesHybrid()
      ])
      drivers.value = (driversResult.data || []).map((d) => ({
        ...d,
        avatarUrl: resolveAvatarUrl(d.avatarUrl || d.avatar_url) || null
      }))
      vehiclesList.value = vehiclesResult.data
      dataSource.value = driversResult.source
      failedAvatars.value = {}
      vehicleOptions.value = vehiclesList.value.map(
        (v) => `${v.brand} ${v.model} (${v.licensePlate})`
      )
    }

    onMounted(loadData)

    const showToast = (message) => {
      toast.value = { show: true, message }
      setTimeout(() => (toast.value.show = false), 2200)
    }

    const initialsOf = (name) => name.split(' ').map((n) => n[0]).join('').toUpperCase().slice(0, 2)

    const openAdd = () => {
      modalMode.value = 'add'
      form.value = { name: '', email: '', phone: '', vehicle: '' }
      showModal.value = true
    }

    const openEdit = (driver) => {
      selected.value = driver
      modalMode.value = 'edit'
      form.value = { name: driver.name, email: driver.email, phone: driver.phone, vehicle: driver.vehicle || '' }
      showModal.value = true
    }

    const openAssign = (driver) => {
      selected.value = driver
      modalMode.value = 'assign'
      form.value = { name: driver.name, email: driver.email, phone: driver.phone, vehicle: driver.vehicle || '' }
      showModal.value = true
    }

    const openHistory = (driver) => {
      selected.value = driver
      modalMode.value = 'history'
      selectedHistory.value = [
        { action: `Mission effectuée — ${driver.vehicle || 'Véhicule libre'}`, date: 'Hier, 16:40' },
        { action: 'Relevé kilométrique enregistré', date: 'Il y a 2 jours' },
        { action: 'Connexion application chauffeur', date: 'Il y a 3 jours' }
      ]
      showModal.value = true
    }

    const closeModal = () => {
      showModal.value = false
      selected.value = null
    }

    const saveModal = async () => {
      if (modalMode.value === 'add') {
        if (!form.value.name || !form.value.email) {
          showToast('Nom et email requis.')
          return
        }
        try {
          await api.createDriver({
            name: form.value.name,
            email: form.value.email,
            phone: form.value.phone,
            password: 'password123'
          })
          await loadData()
          showToast('Chauffeur ajouté.')
        } catch {
          drivers.value.unshift({
            id: Date.now(),
            name: form.value.name,
            initials: initialsOf(form.value.name),
            role: 'Chauffeur',
            email: form.value.email,
            phone: form.value.phone || '—',
            vehicle: null,
            missions: 0,
            km: '0 km',
            rating: 5,
            active: true,
            color: '#0ea5e9'
          })
          persist()
          showToast('Chauffeur ajouté (local).')
        }
      } else if (modalMode.value === 'edit' && selected.value) {
        selected.value.name = form.value.name
        selected.value.email = form.value.email
        selected.value.phone = form.value.phone
        selected.value.initials = initialsOf(form.value.name)
        persist()
        showToast('Profil mis à jour.')
      } else if (modalMode.value === 'assign' && selected.value) {
        const vehicle = vehiclesList.value.find(
          (v) => `${v.brand} ${v.model} (${v.licensePlate})` === form.value.vehicle
        )
        try {
          if (vehicle && selected.value.id) {
            await api.assignDriver(vehicle.id, selected.value.id)
          }
          selected.value.vehicle = form.value.vehicle || null
          await loadData()
          showToast(form.value.vehicle ? 'Véhicule assigné.' : 'Affectation retirée.')
        } catch {
          selected.value.vehicle = form.value.vehicle || null
          persist()
          showToast(form.value.vehicle ? 'Véhicule assigné (local).' : 'Affectation retirée.')
        }
      }
      closeModal()
    }

    return {
      drivers,
      vehicleOptions,
      showModal,
      modalMode,
      selected,
      form,
      selectedHistory,
      toast,
      showDriverAvatar,
      markDriverAvatarFailed,
      openAdd,
      openEdit,
      openAssign,
      openHistory,
      closeModal,
      saveModal
    }
  }
}
</script>

<style scoped>
.page { max-width: 1100px; color: #e2e8f0; }
.page-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
.page-top h1 { font-size: 22px; color: #f8fafc; margin: 0; }
.page-top h1 i { color: #60a5fa; margin-right: 8px; }
.page-top p { color: #94a3b8; font-size: 13px; margin: 4px 0 0; }
.btn-add { padding: 10px 20px; background: #2563eb; color: #fff; border: none; border-radius: 10px; cursor: pointer; font-weight: 600; }

.drivers-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; }
.driver-card { background: #1e293b; border-radius: 16px; padding: 20px; border: 1px solid rgba(148,163,184,.12); }
.driver-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.driver-avatar { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; overflow: hidden; }
.driver-avatar img { width: 100%; height: 100%; object-fit: cover; }
.driver-status { font-size: 11px; padding: 4px 8px; border-radius: 999px; }
.driver-status.online { background: rgba(16,185,129,.15); color: #34d399; }
.driver-status.offline { background: rgba(148,163,184,.15); color: #94a3b8; }
.driver-card h3 { margin: 8px 0 4px; color: #f8fafc; font-size: 16px; }
.driver-role { font-size: 11px; color: #94a3b8; }
.driver-info { margin: 12px 0; font-size: 12px; color: #cbd5e1; display: grid; gap: 4px; }
.driver-vehicle { font-size: 12px; padding: 8px 10px; border-radius: 8px; background: rgba(59,130,246,.1); color: #93c5fd; margin-bottom: 12px; }
.driver-vehicle.empty { background: rgba(148,163,184,.08); color: #94a3b8; }
.driver-stats { display: flex; gap: 12px; margin-bottom: 14px; }
.driver-stats div { flex: 1; text-align: center; background: rgba(15,23,42,.5); padding: 8px; border-radius: 8px; }
.driver-stats strong { display: block; color: #f8fafc; font-size: 14px; }
.driver-stats small { color: #94a3b8; font-size: 10px; }
.driver-actions { display: flex; gap: 6px; flex-wrap: wrap; }
.driver-actions button { flex: 1; min-width: 80px; padding: 8px; border: none; border-radius: 8px; background: rgba(148,163,184,.12); color: #e2e8f0; cursor: pointer; font-size: 11px; }
.driver-actions button:hover { background: #2563eb; color: #fff; }

.modal-overlay { position: fixed; inset: 0; background: rgba(2,6,23,.7); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 16px; }
.modal-card { width: min(460px, 100%); background: #1e293b; border-radius: 16px; padding: 22px; border: 1px solid rgba(148,163,184,.2); }
.modal-card h3 { margin: 0 0 12px; color: #f8fafc; }
.modal-card label { display: block; font-size: 11px; color: #94a3b8; text-transform: uppercase; margin: 10px 0 4px; }
.modal-card input, .modal-card select { width: 100%; padding: 10px 12px; border-radius: 10px; border: 1px solid rgba(148,163,184,.25); background: #0f172a; color: #f8fafc; box-sizing: border-box; }
.hist-item { padding: 10px 0; border-bottom: 1px solid rgba(148,163,184,.1); }
.hist-item strong { display: block; color: #f8fafc; font-size: 13px; }
.hist-item small { color: #94a3b8; }
.modal-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px; }
.btn-primary { padding: 10px 16px; border: none; border-radius: 10px; background: #2563eb; color: #fff; cursor: pointer; font-weight: 600; }
.btn-secondary { padding: 10px 16px; border: none; border-radius: 10px; background: rgba(148,163,184,.15); color: #e2e8f0; cursor: pointer; }
.toast { position: fixed; right: 20px; bottom: 20px; padding: 12px 16px; border-radius: 12px; background: #0f172a; color: #f8fafc; border: 1px solid #34d399; z-index: 1100; }
.hint { color: #94a3b8; font-size: 13px; }

@media (max-width: 768px) {
  .drivers-grid { grid-template-columns: 1fr; }
  .toast { left: 16px; right: 16px; bottom: 16px; }
}

@media (max-width: 480px) {
  .page-top h1 { font-size: 18px; }
  .driver-actions button { flex: 1 1 100%; }
}
</style>
