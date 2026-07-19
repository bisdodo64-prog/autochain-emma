<template>
  <div class="settings-page animate-fade-in-up">
    <div class="page-header">
      <h1 class="text-glow"><i class="fas fa-sliders-h"></i> Paramètres</h1>
      <p>Gérez l’expérience de votre espace AutoChain Emma+</p>
    </div>

    <div class="settings-grid">
      <div class="settings-card card">
        <div class="card-header">
          <h2><i class="fas fa-cog text-slate-400"></i> Préférences</h2>
        </div>
        <div class="settings-list">
          <div class="setting-item" v-for="item in settings" :key="item.key">
            <div class="setting-info">
              <i :class="item.icon"></i>
              <span>{{ item.label }}</span>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" v-model="item.value" />
              <span class="slider"></span>
            </label>
          </div>
          <div class="setting-item">
            <div class="setting-info">
              <i class="fas fa-language"></i>
              <span>Langue</span>
            </div>
            <select v-model="language" class="setting-select">
              <option>Français</option>
              <option>English</option>
              <option>Español</option>
            </select>
          </div>
        </div>
      </div>

      <div class="settings-card card">
        <div class="card-header">
          <h2><i class="fas fa-bell text-blue-400"></i> Notifications</h2>
        </div>
        <div class="notification-list">
          <div v-for="n in notificationPrefs" :key="n.key" class="notification-item">
            <span>{{ n.label }}</span>
            <button class="btn-secondary" @click="toggleNotification(n)">{{ n.enabled ? 'Activé' : 'Désactivé' }}</button>
          </div>
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
import { onMounted, reactive, ref, watch } from 'vue'

const STORAGE_KEY = 'autochain_settings'

export default {
  name: 'Settings',
  setup() {
    const saved = (() => {
      try { return JSON.parse(localStorage.getItem(STORAGE_KEY) || 'null') } catch { return null }
    })()

    const settings = reactive([
      { key: 'notifications', label: 'Notifications', icon: 'fas fa-bell', value: saved?.settings?.notifications ?? true },
      { key: 'emails', label: 'Emails', icon: 'fas fa-envelope', value: saved?.settings?.emails ?? true },
      { key: 'darkMode', label: 'Mode sombre', icon: 'fas fa-moon', value: saved?.settings?.darkMode ?? true }
    ])
    const notificationPrefs = reactive([
      { key: 'maintenance', label: 'Maintenance', enabled: saved?.notifications?.maintenance ?? true },
      { key: 'blockchain', label: 'Blockchain', enabled: saved?.notifications?.blockchain ?? true },
      { key: 'reports', label: 'Rapports', enabled: saved?.notifications?.reports ?? false }
    ])
    const language = ref(saved?.language || 'Français')
    const toast = ref({ show: false, message: '', type: 'success', icon: 'fas fa-check-circle' })

    const applyDarkMode = (enabled) => {
      document.documentElement.classList.toggle('theme-light', !enabled)
      document.body.classList.toggle('theme-light', !enabled)
    }

    const persist = () => {
      const payload = {
        language: language.value,
        settings: Object.fromEntries(settings.map((s) => [s.key, s.value])),
        notifications: Object.fromEntries(notificationPrefs.map((n) => [n.key, n.enabled]))
      }
      localStorage.setItem(STORAGE_KEY, JSON.stringify(payload))
      const dark = settings.find((s) => s.key === 'darkMode')
      applyDarkMode(dark ? dark.value : true)
    }

    const showToast = (message, type = 'success') => {
      toast.value = { show: true, message, type, icon: type === 'success' ? 'fas fa-check-circle' : type === 'error' ? 'fas fa-times-circle' : 'fas fa-info-circle' }
      setTimeout(() => (toast.value.show = false), 2400)
    }

    const toggleNotification = (item) => {
      item.enabled = !item.enabled
      persist()
      showToast(`${item.label} ${item.enabled ? 'activé' : 'désactivé'}.`, 'info')
    }

    watch(language, persist)
    watch(settings, persist, { deep: true })

    onMounted(() => {
      persist()
      showToast('Préférences chargées (enregistrées localement).', 'info')
    })

    return { settings, notificationPrefs, language, toast, toggleNotification }
  }
}
</script>

<style scoped>
.settings-page { padding: 0; }
.page-header { margin-bottom: 32px; }
.page-header h1 { font-size: 28px; font-weight: 700; color: #f8fafc; margin-bottom: 8px; }
.page-header h1 i { margin-right: 10px; color: #38bdf8; }
.page-header p { color: #94a3b8; }
.settings-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; }
.settings-card { padding: 24px; }
.card-header h2 { color: #f8fafc; font-size: 18px; margin-bottom: 18px; }
.settings-list, .notification-list { display: flex; flex-direction: column; gap: 12px; }
.setting-item, .notification-item { display: flex; justify-content: space-between; align-items: center; gap: 16px; padding: 12px 0; border-bottom: 1px solid rgba(148, 163, 184, 0.16); }
.setting-info { display: flex; align-items: center; gap: 10px; color: #e2e8f0; }
.toggle-switch { position: relative; width: 48px; height: 26px; }
.toggle-switch input { display: none; }
.slider { position: absolute; inset: 0; background: rgba(148, 163, 184, 0.3); border-radius: 999px; cursor: pointer; }
.slider::before { content: ''; position: absolute; width: 20px; height: 20px; left: 3px; top: 3px; border-radius: 50%; background: white; transition: all 0.2s; }
.toggle-switch input:checked + .slider { background: linear-gradient(135deg, #38bdf8, #818cf8); }
.toggle-switch input:checked + .slider::before { transform: translateX(22px); }
.setting-select { padding: 8px 12px; border-radius: 10px; border: 1px solid rgba(56, 189, 248, 0.2); background: rgba(15, 23, 42, 0.6); color: #f8fafc; }
.btn-secondary { padding: 8px 12px; border-radius: 10px; border: 1px solid rgba(56, 189, 248, 0.25); background: rgba(56, 189, 248, 0.12); color: #f8fafc; }
.toast { position: fixed; bottom: 24px; right: 24px; display: flex; align-items: center; gap: 10px; padding: 12px 16px; border-radius: 14px; color: white; z-index: 9999; }
.toast.success { background: linear-gradient(135deg, #10b981, #059669); }
.toast.error { background: linear-gradient(135deg, #ef4444, #dc2626); }
.toast.info { background: linear-gradient(135deg, #38bdf8, #0ea5e9); }
</style>
