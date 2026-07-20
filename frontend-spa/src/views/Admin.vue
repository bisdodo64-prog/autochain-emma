<template>

  <div class="page">

    <h1><i class="fas fa-users-cog"></i> Administration</h1>

    <p>Gestion des utilisateurs et configuration du système</p>



    <div v-if="loading" class="loading-hint"><i class="fas fa-spinner fa-spin"></i> Chargement...</div>



    <div class="admin-grid">

      <div class="admin-card">

        <div class="card-icon-wrap" style="background:rgba(37,99,235,.15);color:#60a5fa;"><i class="fas fa-users"></i></div>

        <div><strong>{{ activeCount }}</strong><span>Utilisateurs actifs</span></div>

      </div>

      <div class="admin-card">

        <div class="card-icon-wrap" style="background:rgba(16,185,129,.15);color:#34d399;"><i class="fas fa-user-shield"></i></div>

        <div><strong>{{ adminCount }}</strong><span>Super Admins</span></div>

      </div>

      <div class="admin-card">

        <div class="card-icon-wrap" style="background:rgba(245,158,11,.15);color:#fbbf24;"><i class="fas fa-user-clock"></i></div>

        <div><strong>{{ inactiveCount }}</strong><span>Inactifs</span></div>

      </div>

    </div>



    <div class="section">

      <div class="section-top">

        <h3>Liste des utilisateurs <small v-if="dataSource" class="source-tag">{{ dataSource }}</small></h3>

        <button class="btn-add" @click="openAdd"><i class="fas fa-plus"></i> Ajouter</button>

      </div>

      <table class="table">

        <thead>

          <tr><th>Nom</th><th>Email</th><th>Rôle</th><th>Statut</th><th>Actions</th></tr>

        </thead>

        <tbody>

          <tr v-for="u in users" :key="u.id">

            <td>
              <div class="user-name-cell">
                <div class="user-avatar">
                  <img
                    v-if="u.avatar_url && !failedAvatars[u.id]"
                    :src="avatarSrc(u)"
                    :alt="u.name"
                    @error="markAvatarFailed(u.id)"
                  />
                  <span v-else>{{ initials(u.name) }}</span>
                </div>
                <strong>{{ u.name }}</strong>
              </div>
            </td>

            <td>{{ u.email }}</td>

            <td><span class="role-tag">{{ u.role }}</span></td>

            <td><span :class="'tag ' + (u.active ? 'active' : 'inactive')">{{ u.active ? 'Actif' : 'Inactif' }}</span></td>

            <td>

              <button class="btn-sm" title="Modifier" @click="openEdit(u)"><i class="fas fa-edit"></i></button>

              <button class="btn-sm danger" title="Supprimer" @click="askDelete(u)"><i class="fas fa-trash"></i></button>

            </td>

          </tr>

        </tbody>

      </table>

    </div>



    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">

      <div class="modal-card">

        <h3>{{ editing ? 'Modifier utilisateur' : 'Nouvel utilisateur' }}</h3>

        <label>Nom</label>

        <input v-model="form.name" />

        <label>Email</label>

        <input v-model="form.email" />

        <label>Rôle</label>

        <select v-model="form.role">

          <option>Super Admin</option>

          <option>Gestionnaire</option>

          <option>Chauffeur</option>

          <option>Garagiste</option>

          <option>Auditeur</option>

        </select>

        <label class="check"><input type="checkbox" v-model="form.active" /> Compte actif</label>

        <div class="modal-actions">

          <button class="btn-secondary" @click="closeModal">Annuler</button>

          <button class="btn-primary" @click="saveUser" :disabled="saving">{{ saving ? 'Enregistrement...' : 'Enregistrer' }}</button>

        </div>

      </div>

    </div>



    <div v-if="deleteModal.show" class="modal-overlay" @click.self="deleteModal.show = false">

      <div class="modal-card delete-card">

        <div class="delete-icon"><i class="fas fa-exclamation-triangle"></i></div>

        <h3>Confirmer la suppression</h3>

        <p>Voulez-vous vraiment supprimer <strong>{{ deleteModal.user?.name }}</strong> ? Cette action est irréversible.</p>

        <div class="modal-actions">

          <button class="btn-secondary" @click="deleteModal.show = false">Annuler</button>

          <button class="btn-danger" @click="confirmDelete">Supprimer définitivement</button>

        </div>

      </div>

    </div>



    <div v-if="toast.show" class="toast" :class="toast.type">{{ toast.message }}</div>

  </div>

</template>



<script>

import { computed, onMounted, ref } from 'vue'

import { fetchUsersHybrid, saveUserHybrid, deleteUserHybrid, SOURCE_LABELS } from '../js/utils/dataService'
import { getInitials, resolveAvatarUrl } from '../js/utils/avatarUrl'

export default {

  name: 'Admin',

  setup() {

    const showModal = ref(false)

    const editing = ref(false)

    const loading = ref(true)

    const saving = ref(false)

    const dataSource = ref('')

    const form = ref({ id: null, name: '', email: '', role: 'Chauffeur', active: true })

    const deleteModal = ref({ show: false, user: null })

    const toast = ref({ show: false, message: '', type: 'success' })

    const users = ref([])
    const failedAvatars = ref({})

    const avatarSrc = (u) => resolveAvatarUrl(u.avatar_url)
    const markAvatarFailed = (id) => {
      failedAvatars.value = { ...failedAvatars.value, [id]: true }
    }

    const loadUsers = async () => {

      loading.value = true

      const result = await fetchUsersHybrid()

      users.value = result.data
      failedAvatars.value = {}

      dataSource.value = SOURCE_LABELS[result.source] || ''

      loading.value = false

    }



    onMounted(loadUsers)



    const initials = (name) => getInitials(name, '?')



    const activeCount = computed(() => users.value.filter((u) => u.active).length)

    const adminCount = computed(() => users.value.filter((u) => u.role === 'Super Admin').length)

    const inactiveCount = computed(() => users.value.filter((u) => !u.active).length)



    const showToast = (message, type = 'success') => {

      toast.value = { show: true, message, type }

      setTimeout(() => (toast.value.show = false), 2400)

    }



    const openAdd = () => {

      editing.value = false

      form.value = { id: null, name: '', email: '', role: 'Chauffeur', active: true }

      showModal.value = true

    }



    const openEdit = (u) => {

      editing.value = true

      form.value = { ...u }

      showModal.value = true

    }



    const closeModal = () => {

      showModal.value = false

    }



    const saveUser = async () => {

      if (!form.value.name || !form.value.email) {

        showToast('Nom et email requis.', 'error')

        return

      }

      saving.value = true

      try {

        const result = await saveUserHybrid(form.value, editing.value)

        await loadUsers()

        showToast(editing.value ? 'Utilisateur modifié.' : 'Utilisateur ajouté.')

        if (!editing.value && result.source === 'api') {

          showToast('Mot de passe par défaut : password', 'success')

        }

        closeModal()

      } catch (e) {

        showToast('Erreur lors de l\'enregistrement.', 'error')

      } finally {

        saving.value = false

      }

    }



    const askDelete = (u) => {

      deleteModal.value = { show: true, user: u }

    }



    const confirmDelete = async () => {

      const id = deleteModal.value.user?.id

      await deleteUserHybrid(id)

      await loadUsers()

      deleteModal.value = { show: false, user: null }

      showToast('Utilisateur supprimé.', 'error')

    }



    return {

      users,

      initials,

      avatarSrc,

      failedAvatars,

      markAvatarFailed,

      activeCount,

      adminCount,

      inactiveCount,

      loading,

      saving,

      dataSource,

      showModal,

      editing,

      form,

      deleteModal,

      toast,

      openAdd,

      openEdit,

      closeModal,

      saveUser,

      askDelete,

      confirmDelete

    }

  }

}

</script>



<style scoped>

.page { max-width: 1000px; color: #e2e8f0; }

h1 { font-size: 22px; color: #f8fafc; margin: 0; }

h1 i { color: #f87171; margin-right: 8px; }

.page > p { color: #94a3b8; font-size: 13px; margin: 4px 0 20px; }

.loading-hint { color: #94a3b8; font-size: 13px; margin-bottom: 12px; }

.source-tag { font-size: 11px; color: #64748b; font-weight: normal; margin-left: 6px; }

.admin-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 20px; }

.admin-card { background: #1e293b; padding: 16px; border-radius: 12px; display: flex; align-items: center; gap: 14px; border: 1px solid rgba(148,163,184,.12); }

.card-icon-wrap { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; }

.admin-card strong { display: block; font-size: 20px; color: #f8fafc; }

.admin-card span { font-size: 12px; color: #94a3b8; }

.section { background: #1e293b; border-radius: 14px; padding: 20px; border: 1px solid rgba(148,163,184,.12); }

.section-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }

.section h3 { font-size: 15px; margin: 0; color: #f8fafc; }

.btn-add { padding: 8px 14px; border: none; border-radius: 8px; background: #2563eb; color: #fff; cursor: pointer; font-size: 13px; }

.table { width: 100%; border-collapse: collapse; }

.table th { text-align: left; padding: 10px; font-size: 11px; color: #94a3b8; text-transform: uppercase; border-bottom: 1px solid rgba(148,163,184,.2); }

.table td { padding: 10px; border-bottom: 1px solid rgba(148,163,184,.1); font-size: 13px; color: #e2e8f0; }

.table td strong { color: #f8fafc; }

.role-tag { background: rgba(37,99,235,.15); color: #60a5fa; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 500; }

.tag { padding: 3px 10px; border-radius: 12px; font-size: 11px; }

.tag.active { background: rgba(16,185,129,.15); color: #34d399; }

.tag.inactive { background: rgba(239,68,68,.15); color: #f87171; }

.btn-sm { padding: 6px 10px; border: none; border-radius: 6px; background: rgba(148,163,184,.12); color: #e2e8f0; cursor: pointer; margin-right: 4px; }

.btn-sm:hover { background: #2563eb; color: #fff; }

.btn-sm.danger:hover { background: #ef4444; }



.modal-overlay { position: fixed; inset: 0; background: rgba(2,6,23,.75); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 16px; }

.modal-card { width: min(440px, 100%); background: #1e293b; border-radius: 16px; padding: 22px; border: 1px solid rgba(148,163,184,.2); }

.modal-card h3 { margin: 0 0 12px; color: #f8fafc; }

.modal-card label { display: block; font-size: 11px; color: #94a3b8; text-transform: uppercase; margin: 10px 0 4px; }

.modal-card input, .modal-card select { width: 100%; padding: 10px 12px; border-radius: 10px; border: 1px solid rgba(148,163,184,.25); background: #0f172a; color: #f8fafc; box-sizing: border-box; }

.check { display: flex !important; align-items: center; gap: 8px; text-transform: none !important; font-size: 13px !important; color: #e2e8f0 !important; margin-top: 14px !important; }

.check input { width: auto; }

.modal-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px; }

.btn-primary { padding: 10px 16px; border: none; border-radius: 10px; background: #2563eb; color: #fff; cursor: pointer; font-weight: 600; }

.btn-secondary { padding: 10px 16px; border: none; border-radius: 10px; background: rgba(148,163,184,.15); color: #e2e8f0; cursor: pointer; }

.btn-danger { padding: 10px 16px; border: none; border-radius: 10px; background: #ef4444; color: #fff; cursor: pointer; font-weight: 600; }

.delete-card { text-align: center; }

.delete-icon { font-size: 36px; color: #fbbf24; margin-bottom: 8px; }

.delete-card p { color: #94a3b8; font-size: 14px; }

.delete-card strong { color: #f8fafc; }

.toast { position: fixed; right: 20px; bottom: 20px; padding: 12px 16px; border-radius: 12px; background: #0f172a; color: #f8fafc; border: 1px solid #38bdf8; z-index: 1100; }

.toast.error { border-color: #ef4444; }

.user-name-cell { display: flex; align-items: center; gap: 10px; }
.user-avatar {
  width: 36px; height: 36px; border-radius: 10px; overflow: hidden; flex-shrink: 0;
  background: linear-gradient(135deg, #0ea5e9, #6366f1); color: #fff;
  display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700;
}
.user-avatar img { width: 100%; height: 100%; object-fit: cover; }

@media (max-width: 768px) {
  .section { overflow-x: auto; }
  .table { min-width: 640px; }
  .section-top { flex-direction: column; align-items: flex-start; gap: 10px; }
  .toast { left: 16px; right: 16px; bottom: 16px; }
}

@media (max-width: 480px) {
  h1 { font-size: 18px; }
}

</style>

