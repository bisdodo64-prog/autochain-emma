<template>
  <div class="page">
    <h1><i class="fas fa-user-circle"></i> Mon Profil</h1>
    <p v-if="viewRoleLabel" class="role-banner">Espace {{ viewRoleLabel }}</p>
    <p v-if="dataSource" class="source-hint">{{ dataSource }}</p>

    <div class="profile-layout">
      <div class="profile-sidebar">
        <div class="avatar-section">
          <div class="avatar-wrap">
            <img v-if="avatarSrc" :src="avatarSrc" alt="Photo de profil" class="avatar-img" />
            <div v-else class="avatar-large">{{ userInitials }}</div>
            <label class="avatar-upload" title="Changer la photo">
              <i class="fas fa-camera"></i>
              <input type="file" accept="image/jpeg,image/png,image/webp" hidden @change="onAvatarSelected" />
            </label>
          </div>
          <p v-if="uploadingAvatar" class="avatar-hint">Envoi de la photo…</p>
          <h2>{{ profile.name }}</h2>
          <span class="role-badge">{{ userRole }}</span>
        </div>
        <div class="profile-menu">
          <button :class="{ active: tab === 'info' }" @click="tab = 'info'"><i class="fas fa-info-circle"></i> Informations</button>
          <button :class="{ active: tab === 'wallet' }" @click="tab = 'wallet'"><i class="fas fa-wallet"></i> Wallet</button>
          <button :class="{ active: tab === 'security' }" @click="tab = 'security'"><i class="fas fa-lock"></i> Sécurité</button>
          <button :class="{ active: tab === 'activity' }" @click="tab = 'activity'"><i class="fas fa-history"></i> Activité</button>
        </div>
      </div>

      <div class="profile-content">
        <div v-if="tab === 'info'" class="info-section">
          <h3>Informations personnelles</h3>
          <div v-if="!editing" class="info-grid">
            <div class="info-item"><label>Nom complet</label><span>{{ profile.name }}</span></div>
            <div class="info-item"><label>Email</label><span>{{ profile.email }}</span></div>
            <div class="info-item"><label>Rôle</label><span class="role-tag">{{ userRole }}</span></div>
            <div class="info-item"><label>Date d'inscription</label><span>{{ profile.joined }}</span></div>
            <div class="info-item"><label>Téléphone</label><span>{{ profile.phone }}</span></div>
            <div class="info-item"><label>Département</label><span>{{ profile.department }}</span></div>
          </div>
          <div v-else class="edit-grid">
            <label>Nom</label>
            <input v-model="editForm.name" />
            <label>Email</label>
            <input v-model="editForm.email" />
            <label>Téléphone</label>
            <input v-model="editForm.phone" />
            <label>Département</label>
            <input v-model="editForm.department" />
          </div>
          <div class="actions-row">
            <button v-if="!editing" class="btn-edit" @click="startEdit"><i class="fas fa-edit"></i> Modifier mes informations</button>
            <template v-else>
              <button class="btn-secondary" @click="editing = false">Annuler</button>
              <button class="btn-edit" @click="saveInfo" :disabled="saving">{{ saving ? 'Enregistrement...' : 'Enregistrer' }}</button>
            </template>
          </div>
        </div>

        <div v-if="tab === 'wallet'" class="wallet-section">
          <h3>Portefeuille Blockchain</h3>
          <div class="wallet-card" v-if="wallet.connected">
            <div class="wallet-icon"><i class="fas fa-wallet"></i></div>
            <div>
              <strong>MetaMask</strong>
              <span class="wallet-address">{{ wallet.address }}</span>
              <span class="wallet-status connected"><i class="fas fa-circle"></i> Connecté</span>
            </div>
            <button class="btn-disconnect" @click="disconnectWallet">Déconnecter</button>
          </div>
          <div class="wallet-card" v-else>
            <div class="wallet-icon"><i class="fas fa-wallet"></i></div>
            <div>
              <strong>Aucun wallet</strong>
              <span class="wallet-address">Connectez MetaMask pour signer</span>
            </div>
            <button class="btn-edit" @click="connectWallet">Connecter</button>
          </div>
        </div>

        <div v-if="tab === 'security'" class="security-section">
          <h3>Sécurité</h3>
          <div class="security-item">
            <div>
              <strong>Mot de passe</strong>
              <p>{{ passwordMsg }}</p>
            </div>
            <button class="btn-outline" @click="changePassword">Modifier</button>
          </div>
          <div class="security-item">
            <div>
              <strong>Authentification 2FA</strong>
              <p>{{ twoFA ? 'Activée' : 'Non activée' }}</p>
            </div>
            <button class="btn-outline" @click="toggle2FA">{{ twoFA ? 'Désactiver' : 'Activer' }}</button>
          </div>
        </div>

        <div v-if="tab === 'activity'" class="activity-section">
          <h3>Activité récente</h3>
          <div v-for="act in activities" :key="act.id" class="activity-item">
            <div class="act-icon"><i :class="act.icon"></i></div>
            <div>
              <strong>{{ act.action }}</strong>
              <small>{{ act.date }}</small>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="toast.show" class="toast">{{ toast.message }}</div>
  </div>
</template>

<script>
import { computed, onMounted, ref, watch } from 'vue'
import { useStore } from 'vuex'
import { useRoute } from 'vue-router'
import {
  fetchProfileHybrid,
  saveProfileHybrid,
  uploadAvatarHybrid,
  updateWalletHybrid,
  changePasswordHybrid,
  SOURCE_LABELS
} from '../js/utils/dataService'

export default {
  name: 'Profile',
  setup() {
    const store = useStore()
    const route = useRoute()
    const tab = ref('info')
    const editing = ref(false)
    const loading = ref(true)
    const saving = ref(false)
    const uploadingAvatar = ref(false)
    const dataSource = ref('')
    const twoFA = ref(false)
    const passwordMsg = ref('Derniere modification : il y a 3 mois')
    const toast = ref({ show: false, message: '' })
    const wallet = ref({ connected: false, address: '' })
    const avatarCacheBust = ref(Date.now())
    let loadingLock = false

    const roleMap = {
      super_admin: 'Super Admin',
      gestionnaire_parc: 'Gestionnaire',
      chauffeur: 'Chauffeur',
      garagiste_agree: 'Garagiste',
      auditeur: 'Auditeur'
    }

    const profile = ref({
      name: '',
      email: '',
      phone: '',
      department: '',
      joined: '',
      roleLabel: '',
      avatarUrl: null
    })
    const editForm = ref({ name: '', email: '', phone: '', department: '' })

    const viewRole = computed(() => store.state.auth?.user?.roles?.[0]?.name || route.query.role || '')
    const viewRoleLabel = computed(() => roleMap[viewRole.value] || '')
    const userRole = computed(() => roleMap[viewRole.value] || profile.value.roleLabel || 'Utilisateur')

    const avatarSrc = computed(() => {
      const url = profile.value.avatarUrl || store.state.auth?.user?.avatar_url
      if (!url) return null
      if (String(url).startsWith('data:')) return url
      const sep = url.includes('?') ? '&' : '?'
      return `${url}${sep}t=${avatarCacheBust.value}`
    })

    const applyWallet = (walletAddress) => {
      if (walletAddress) {
        const a = String(walletAddress)
        wallet.value = {
          connected: true,
          address: a.length > 14 ? `${a.slice(0, 6)}…${a.slice(-4)}` : a
        }
      } else {
        wallet.value = { connected: false, address: '' }
      }
    }

    const fallbackFromStore = () => {
      const u = store.state.auth?.user || {}
      profile.value = {
        name: u.name || 'Utilisateur',
        email: u.email || '',
        phone: u.phone || '',
        department: '',
        joined: 'N/A',
        roleLabel: roleMap[u.roles?.[0]?.name] || 'Utilisateur',
        avatarUrl: u.avatar_url || null
      }
      applyWallet(u.wallet_address || '')
    }

    const loadProfile = async () => {
      if (loadingLock) return
      loadingLock = true
      loading.value = true
      try {
        const result = await fetchProfileHybrid(store.state.auth?.user)
        profile.value = {
          name: result?.data?.name || store.state.auth?.user?.name || 'Utilisateur',
          email: result?.data?.email || store.state.auth?.user?.email || '',
          phone: result?.data?.phone || store.state.auth?.user?.phone || '',
          department: result?.data?.department || '',
          joined: result?.data?.joined || 'N/A',
          roleLabel: result?.data?.roleLabel || userRole.value,
          avatarUrl: result?.data?.avatarUrl || store.state.auth?.user?.avatar_url || null
        }
        dataSource.value = SOURCE_LABELS[result?.source] || ''
        applyWallet(result?.data?.walletAddress || store.state.auth?.user?.wallet_address || '')
      } catch (error) {
        console.warn('Chargement profil echoue', error)
        fallbackFromStore()
        dataSource.value = 'Profil local'
      } finally {
        loading.value = false
        loadingLock = false
      }
    }

    onMounted(loadProfile)

    watch(
      () => store.state.auth?.user?.roles?.[0]?.name,
      (next, prev) => {
        if (next && next !== prev && !editing.value) loadProfile()
      }
    )

    const userInitials = computed(() => {
      const name = String(profile.value.name || 'U').trim()
      return name.split(/\s+/).map((n) => n[0] || '').join('').toUpperCase().slice(0, 2) || 'U'
    })

    const activities = computed(() => {
      const role = viewRole.value
      if (role === 'garagiste_agree') {
        return [
          { id: 1, icon: 'fas fa-tools', action: 'Intervention demarree', date: 'Aujourd hui' },
          { id: 2, icon: 'fas fa-cubes', action: 'Maintenance certifiee blockchain', date: 'Hier' }
        ]
      }
      if (role === 'chauffeur') {
        return [
          { id: 1, icon: 'fas fa-route', action: 'Mission demarree', date: 'Aujourd hui' },
          { id: 2, icon: 'fas fa-tachometer-alt', action: 'Releve kilometrique', date: 'Hier' }
        ]
      }
      if (role === 'gestionnaire_parc') {
        return [
          { id: 1, icon: 'fas fa-car', action: 'Vehicule ajoute a la flotte', date: 'Aujourd hui' },
          { id: 2, icon: 'fas fa-user', action: 'Chauffeur assigne', date: 'Hier' }
        ]
      }
      return [
        { id: 1, icon: 'fas fa-sign-in-alt', action: 'Connexion au systeme', date: 'Aujourd hui' },
        { id: 2, icon: 'fas fa-car', action: 'Vehicule consulte', date: 'Hier' }
      ]
    })

    const showToast = (message) => {
      toast.value = { show: true, message }
      setTimeout(() => (toast.value.show = false), 2200)
    }

    const startEdit = () => {
      editForm.value = {
        name: profile.value.name,
        email: profile.value.email,
        phone: profile.value.phone,
        department: profile.value.department
      }
      editing.value = true
    }

    const persistUser = (user) => {
      if (!user) return
      const nextUser = store.state.auth?.user?.switchedFromAdmin
        ? { ...user, switchedFromAdmin: true }
        : user
      store.commit('auth/SET_USER', nextUser)
      localStorage.setItem('user', JSON.stringify(nextUser))
    }

    const saveInfo = async () => {
      saving.value = true
      try {
        const result = await saveProfileHybrid(editForm.value)
        profile.value = { ...profile.value, ...(result.data || {}) }
        persistUser(result.user)
        editing.value = false
        showToast('Informations mises a jour.')
      } catch {
        showToast('Erreur lors de la mise a jour.')
      } finally {
        saving.value = false
      }
    }

    const onAvatarSelected = async (event) => {
      const file = event.target.files?.[0]
      event.target.value = ''
      if (!file) return
      if (!file.type.startsWith('image/')) {
        showToast('Choisissez une image (JPG, PNG ou WebP).')
        return
      }
      if (file.size > 2 * 1024 * 1024) {
        showToast('Image trop lourde (max 2 Mo).')
        return
      }
      uploadingAvatar.value = true
      try {
        const result = await uploadAvatarHybrid(file)
        profile.value = { ...profile.value, ...(result.data || {}) }
        persistUser(result.user)
        avatarCacheBust.value = Date.now()
        showToast(result.message || 'Photo de profil mise a jour.')
      } catch (error) {
        const msg = error?.response?.data?.errors?.avatar?.[0]
          || error?.response?.data?.message
          || error?.message
          || 'Erreur lors de lenvoi de la photo.'
        showToast(msg)
      } finally {
        uploadingAvatar.value = false
      }
    }

    const disconnectWallet = async () => {
      await store.dispatch('blockchain/disconnectWallet')
      await updateWalletHybrid('')
      wallet.value = { connected: false, address: '' }
      showToast('Wallet deconnecte.')
    }

    const connectWallet = async () => {
      try {
        const address = await store.dispatch('blockchain/connectWallet')
        const short = address.length > 14 ? `${address.slice(0, 6)}…${address.slice(-4)}` : address
        wallet.value = { connected: true, address: short }
        await updateWalletHybrid(address)
        showToast(store.state.blockchain.mock ? 'Wallet simule.' : 'Wallet connecte.')
      } catch (e) {
        showToast(e.message || 'Connexion wallet impossible.')
      }
    }

    const changePassword = async () => {
      const next = window.prompt('Nouveau mot de passe (min. 6 caracteres) :')
      if (!next) return
      if (next.length < 6) {
        showToast('Mot de passe trop court.')
        return
      }
      const confirmPwd = window.prompt('Confirmez le mot de passe :')
      if (next !== confirmPwd) {
        showToast('Les mots de passe ne correspondent pas.')
        return
      }
      try {
        await changePasswordHybrid(next, confirmPwd)
        passwordMsg.value = 'Derniere modification : a l instant'
        showToast('Mot de passe modifie.')
      } catch {
        showToast('Erreur lors du changement de mot de passe.')
      }
    }

    const toggle2FA = () => {
      twoFA.value = !twoFA.value
      showToast(twoFA.value ? '2FA activee.' : '2FA desactivee.')
    }

    return {
      tab,
      profile,
      editing,
      editForm,
      loading,
      saving,
      uploadingAvatar,
      avatarSrc,
      dataSource,
      userRole,
      userInitials,
      viewRoleLabel,
      activities,
      wallet,
      twoFA,
      passwordMsg,
      toast,
      startEdit,
      saveInfo,
      onAvatarSelected,
      disconnectWallet,
      connectWallet,
      changePassword,
      toggle2FA
    }
  }
}
</script>

<style scoped>
.page { max-width: 900px; color: #e2e8f0; }
h1 { font-size: 22px; color: #f8fafc; margin: 0 0 8px; }
h1 i { color: #38bdf8; margin-right: 8px; }
.role-banner { display: inline-block; background: rgba(56,189,248,.15); color: #7dd3fc; padding: 6px 12px; border-radius: 999px; font-size: 12px; margin-bottom: 16px; }
.source-hint { font-size: 11px; color: #64748b; margin: -8px 0 16px; }

.profile-layout { display: flex; gap: 20px; }
.profile-sidebar { width: 240px; flex-shrink: 0; }
.avatar-section { text-align: center; background: #1e293b; border-radius: 16px; padding: 24px; margin-bottom: 12px; border: 1px solid rgba(148,163,184,.12); }
.avatar-wrap { position: relative; width: 88px; height: 88px; margin: 0 auto 12px; }
.avatar-large, .avatar-img { width: 88px; height: 88px; border-radius: 22px; object-fit: cover; display: flex; align-items: center; justify-content: center; }
.avatar-large { background: linear-gradient(135deg, #0ea5e9, #6366f1); color: #fff; font-weight: 700; font-size: 28px; }
.avatar-upload {
  position: absolute; right: -4px; bottom: -4px; width: 32px; height: 32px; border-radius: 50%;
  background: #0ea5e9; color: #fff; display: flex; align-items: center; justify-content: center;
  cursor: pointer; border: 2px solid #1e293b; font-size: 12px;
}
.avatar-upload:hover { background: #0284c7; }
.avatar-hint { font-size: 11px; color: #94a3b8; margin: -4px 0 8px; }
.avatar-section h2 { font-size: 16px; margin: 0 0 4px; color: #f8fafc; }
.role-badge { background: rgba(56,189,248,.15); color: #7dd3fc; padding: 3px 10px; border-radius: 12px; font-size: 11px; }
.profile-menu { background: #1e293b; border-radius: 16px; padding: 8px; border: 1px solid rgba(148,163,184,.12); display: flex; flex-direction: column; gap: 2px; }
.profile-menu button { text-align: left; padding: 10px 14px; border: none; border-radius: 10px; background: transparent; cursor: pointer; font-size: 13px; color: #94a3b8; }
.profile-menu button i { width: 18px; margin-right: 8px; }
.profile-menu button:hover, .profile-menu button.active { background: rgba(56,189,248,.12); color: #7dd3fc; }

.profile-content { flex: 1; background: #1e293b; border-radius: 16px; padding: 24px; border: 1px solid rgba(148,163,184,.12); }
.profile-content h3 { font-size: 16px; margin: 0 0 16px; color: #f8fafc; }

.info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
.info-item label { display: block; font-size: 11px; color: #94a3b8; text-transform: uppercase; margin-bottom: 2px; }
.info-item span { font-size: 14px; font-weight: 500; color: #f8fafc; }
.role-tag { background: rgba(56,189,248,.15); color: #7dd3fc; padding: 2px 8px; border-radius: 8px; font-size: 12px; }
.edit-grid label { display: block; font-size: 11px; color: #94a3b8; text-transform: uppercase; margin: 8px 0 4px; }
.edit-grid input { width: 100%; padding: 10px 12px; border-radius: 10px; border: 1px solid rgba(148,163,184,.25); background: #0f172a; color: #f8fafc; box-sizing: border-box; }
.actions-row { display: flex; gap: 8px; margin-top: 12px; }
.btn-edit { padding: 10px 20px; background: #0ea5e9; color: #fff; border: none; border-radius: 10px; cursor: pointer; font-weight: 500; }
.btn-secondary { padding: 10px 16px; border: none; border-radius: 10px; background: rgba(148,163,184,.15); color: #e2e8f0; cursor: pointer; }

.wallet-card { display: flex; align-items: center; gap: 16px; padding: 16px; background: #0f172a; border-radius: 12px; flex-wrap: wrap; }
.wallet-icon { font-size: 32px; color: #f59e0b; }
.wallet-card strong { display: block; color: #f8fafc; }
.wallet-address { display: block; font-family: ui-monospace, monospace; font-size: 13px; color: #94a3b8; }
.wallet-status.connected { color: #34d399; font-size: 11px; }
.btn-disconnect { margin-left: auto; padding: 8px 16px; border: 1px solid #ef4444; border-radius: 8px; background: transparent; color: #f87171; cursor: pointer; }

.security-item { display: flex; justify-content: space-between; align-items: center; padding: 16px; border-radius: 10px; background: #0f172a; margin-bottom: 8px; gap: 12px; }
.security-item strong { display: block; font-size: 14px; color: #f8fafc; }
.security-item p { font-size: 12px; color: #94a3b8; margin: 0; }
.btn-outline { padding: 8px 16px; border: 1px solid rgba(148,163,184,.3); border-radius: 8px; background: transparent; color: #e2e8f0; cursor: pointer; }

.activity-item { display: flex; align-items: center; gap: 12px; padding: 12px; border-radius: 10px; margin-bottom: 6px; background: #0f172a; }
.act-icon { width: 36px; height: 36px; border-radius: 10px; background: rgba(56,189,248,.12); display: flex; align-items: center; justify-content: center; color: #38bdf8; }
.activity-item strong { display: block; font-size: 13px; color: #f8fafc; }
.activity-item small { font-size: 11px; color: #94a3b8; }

.toast { position: fixed; right: 20px; bottom: 20px; padding: 12px 16px; border-radius: 12px; background: #0f172a; color: #f8fafc; border: 1px solid #38bdf8; z-index: 1100; }

@media (max-width: 768px) {
  .page { padding-bottom: 24px; }
  .profile-layout { flex-direction: column; }
  .profile-sidebar { width: 100%; }
  .profile-menu { flex-direction: row; flex-wrap: wrap; gap: 6px; }
  .profile-menu button { flex: 1 1 45%; min-width: 120px; font-size: 12px; padding: 10px; }
  .info-grid { grid-template-columns: 1fr; }
  .wallet-card, .security-item { flex-direction: column; align-items: flex-start; }
  .btn-disconnect { margin-left: 0; }
  .toast { left: 16px; right: 16px; bottom: 16px; }
}

@media (max-width: 480px) {
  h1 { font-size: 18px; }
  .profile-content { padding: 16px; }
  .profile-menu button { flex: 1 1 100%; }
}
</style>
