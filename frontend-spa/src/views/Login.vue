<template>
  <div class="login-page">
    <div class="animated-bg">
      <div class="orb orb-1"></div>
      <div class="orb orb-2"></div>
      <div class="orb orb-3"></div>
      <div class="grid-overlay"></div>
    </div>

    <div class="login-shell">
      <div class="brand-panel">
        <div class="brand-badge animate-float">
          <i class="fas fa-car"></i>
        </div>
        <h1>AutoChain <span>Emma+</span></h1>
        <p>Pilotez votre flotte avec une traçabilité blockchain fiable et fluide.</p>
        <ul>
          <li><i class="fas fa-shield-alt"></i> Certifications on-chain</li>
          <li><i class="fas fa-tachometer-alt"></i> Suivi kilométrique</li>
          <li><i class="fas fa-users"></i> Rôles multi-profils</li>
        </ul>
      </div>

      <div class="form-panel">
        <div class="mode-tabs">
          <button type="button" :class="{ active: mode === 'login' }" @click="mode = 'login'">Connexion</button>
          <button type="button" :class="{ active: mode === 'register' }" @click="mode = 'register'">Créer un compte</button>
        </div>

        <form v-if="mode === 'login'" @submit.prevent="handleLogin" class="auth-form">
          <h2>Bon retour</h2>
          <p class="subtitle">Connectez-vous avec votre email et mot de passe</p>

          <label>Email</label>
          <div class="field">
            <i class="fas fa-envelope"></i>
            <input v-model="loginForm.email" type="email" required placeholder="Votre adresse email" />
          </div>

          <label>Mot de passe</label>
          <div class="field">
            <i class="fas fa-lock"></i>
            <input v-model="loginForm.password" :type="showPass ? 'text' : 'password'" required placeholder="Votre mot de passe" />
            <button type="button" class="eye" @click="showPass = !showPass">
              <i :class="showPass ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
            </button>
          </div>

          <button type="submit" class="btn-submit" :disabled="loading">
            <span v-if="!loading"><i class="fas fa-sign-in-alt"></i> Se connecter</span>
            <span v-else><i class="fas fa-spinner fa-spin"></i> Connexion...</span>
          </button>
        </form>

        <form v-else @submit.prevent="handleRegister" class="auth-form">
          <h2>Créer votre compte</h2>
          <p class="subtitle">Créez un compte avec n'importe quel email — données synchronisées avec l'API</p>

          <label>Nom complet</label>
          <div class="field">
            <i class="fas fa-user"></i>
            <input v-model="registerForm.name" type="text" required placeholder="Votre nom" />
          </div>

          <label>Email</label>
          <div class="field">
            <i class="fas fa-envelope"></i>
            <input v-model="registerForm.email" type="email" required placeholder="Votre email professionnel" />
          </div>

          <label>Mot de passe</label>
          <div class="field">
            <i class="fas fa-lock"></i>
            <input v-model="registerForm.password" :type="showPass ? 'text' : 'password'" required minlength="6" placeholder="Min. 6 caractères" />
            <button type="button" class="eye" @click="showPass = !showPass">
              <i :class="showPass ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
            </button>
          </div>

          <label>Confirmer le mot de passe</label>
          <div class="field">
            <i class="fas fa-lock"></i>
            <input v-model="registerForm.confirm" type="password" required placeholder="Répétez le mot de passe" />
          </div>

          <button type="submit" class="btn-submit" :disabled="loading">
            <span v-if="!loading"><i class="fas fa-user-plus"></i> Créer mon compte</span>
            <span v-else><i class="fas fa-spinner fa-spin"></i> Création...</span>
          </button>
        </form>

        <div v-if="error" class="alert">
          <i class="fas fa-exclamation-circle"></i> {{ error }}
        </div>

        <div class="web3-row">
          <button type="button" class="btn-web3" @click="connectWallet" :disabled="walletLoading">
            <span v-if="!walletLoading">🦊 Connexion MetaMask</span>
            <span v-else><i class="fas fa-spinner fa-spin"></i> Connexion wallet...</span>
          </button>
        </div>

        <p class="footer-note"><i class="fas fa-shield-alt"></i> Données sécurisées • Conforme RGPD</p>
      </div>
    </div>
  </div>
</template>

<script>
import { reactive, ref } from 'vue'
import { useStore } from 'vuex'
import { useRouter } from 'vue-router'

export default {
  name: 'Login',
  setup() {
    const store = useStore()
    const router = useRouter()
    const mode = ref('login')
    const loading = ref(false)
    const walletLoading = ref(false)
    const error = ref(null)
    const showPass = ref(false)

    const loginForm = reactive({ email: '', password: '' })
    const registerForm = reactive({ name: '', email: '', password: '', confirm: '' })

    const handleLogin = async () => {
      loading.value = true
      error.value = null
      try {
        await store.dispatch('auth/login', {
          email: loginForm.email.trim(),
          password: loginForm.password
        })
        router.push('/dashboard')
      } catch (err) {
        error.value = err.message || 'Email ou mot de passe incorrect'
      } finally {
        loading.value = false
      }
    }

    const handleRegister = async () => {
      loading.value = true
      error.value = null
      try {
        if (registerForm.password !== registerForm.confirm) {
          throw new Error('Les mots de passe ne correspondent pas')
        }
        await store.dispatch('auth/register', {
          name: registerForm.name.trim(),
          email: registerForm.email.trim(),
          password: registerForm.password
        })
        router.push('/dashboard')
      } catch (err) {
        error.value = err.message || 'Impossible de créer le compte'
      } finally {
        loading.value = false
      }
    }

    const connectWallet = async () => {
      walletLoading.value = true
      error.value = null
      try {
        if (typeof window === 'undefined' || typeof window.ethereum === 'undefined') {
          throw new Error('Ouvre cette page dans le navigateur MetaMask (mobile) ou installe l’extension.')
        }
        await store.dispatch('blockchain/connectWallet')
        const address = store.state.blockchain.account
        if (!address) {
          throw new Error('Adresse MetaMask introuvable')
        }
        const message = `AutoChain Emma+ Authentication\nWallet: ${address.toLowerCase()}\nTimestamp: ${Date.now()}`
        const signature = await store.dispatch('blockchain/signMessage', message)
        await store.dispatch('auth/web3Login', {
          walletAddress: address.toLowerCase(),
          signature,
          message
        })
        router.push('/dashboard')
      } catch (err) {
        const msg = err?.response?.data?.message || err.message || 'Erreur de connexion wallet'
        if (/signature/i.test(msg)) {
          error.value = 'Signature MetaMask invalide — réessaie, ou utilise email/mot de passe.'
        } else {
          error.value = msg
        }
      } finally {
        walletLoading.value = false
      }
    }

    return {
      mode,
      loading,
      walletLoading,
      error,
      showPass,
      loginForm,
      registerForm,
      handleLogin,
      handleRegister,
      connectWallet
    }
  }
}
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  background: #020617;
  position: relative;
  overflow: hidden;
  font-family: 'Segoe UI', system-ui, sans-serif;
}

.animated-bg { position: absolute; inset: 0; }
.orb {
  position: absolute; border-radius: 50%; filter: blur(70px); opacity: 0.35;
  animation: drift 18s ease-in-out infinite;
}
.orb-1 { width: 420px; height: 420px; background: #0ea5e9; top: -80px; right: -60px; }
.orb-2 { width: 320px; height: 320px; background: #22c55e; bottom: -60px; left: -40px; animation-delay: -6s; }
.orb-3 { width: 260px; height: 260px; background: #6366f1; top: 40%; left: 40%; animation-delay: -12s; }
.grid-overlay {
  position: absolute; inset: 0;
  background-image: linear-gradient(rgba(148,163,184,.06) 1px, transparent 1px),
    linear-gradient(90deg, rgba(148,163,184,.06) 1px, transparent 1px);
  background-size: 40px 40px;
  mask-image: radial-gradient(circle at center, black, transparent 75%);
}

.login-shell {
  position: relative; z-index: 1; width: min(980px, 100%);
  display: grid; grid-template-columns: 1.05fr 1fr;
  border-radius: 28px; overflow: hidden;
  border: 1px solid rgba(148,163,184,.2);
  background: rgba(15, 23, 42, 0.72);
  backdrop-filter: blur(18px);
  box-shadow: 0 30px 80px rgba(0,0,0,.45);
  animation: rise 0.6s ease;
}

.brand-panel {
  padding: 48px 40px;
  background: linear-gradient(160deg, rgba(14,165,233,.25), rgba(2,6,23,.9));
  color: #e2e8f0;
}
.brand-badge {
  width: 64px; height: 64px; border-radius: 18px;
  display: flex; align-items: center; justify-content: center;
  background: linear-gradient(135deg, #0ea5e9, #22c55e);
  color: #fff; font-size: 28px; margin-bottom: 22px;
  box-shadow: 0 12px 30px rgba(14,165,233,.35);
}
.brand-panel h1 { margin: 0; font-size: 34px; color: #f8fafc; }
.brand-panel h1 span { color: #4ade80; }
.brand-panel p { color: #94a3b8; margin: 12px 0 28px; line-height: 1.5; }
.brand-panel ul { list-style: none; padding: 0; margin: 0; display: grid; gap: 12px; }
.brand-panel li {
  display: flex; align-items: center; gap: 10px; color: #cbd5e1; font-size: 14px;
  padding: 10px 12px; border-radius: 12px; background: rgba(15,23,42,.45);
}
.brand-panel li i { color: #38bdf8; width: 18px; }

.form-panel { padding: 36px 34px; background: rgba(15,23,42,.88); }
.mode-tabs {
  display: grid; grid-template-columns: 1fr 1fr; gap: 6px;
  background: rgba(2,6,23,.55); padding: 6px; border-radius: 14px; margin-bottom: 22px;
}
.mode-tabs button {
  border: none; background: transparent; color: #94a3b8; padding: 11px;
  border-radius: 10px; cursor: pointer; font-weight: 600;
}
.mode-tabs button.active {
  background: linear-gradient(135deg, #0ea5e9, #0284c7);
  color: #fff; box-shadow: 0 8px 20px rgba(14,165,233,.3);
}

.auth-form h2 { margin: 0; color: #f8fafc; font-size: 22px; }
.subtitle { color: #94a3b8; font-size: 13px; margin: 6px 0 18px; }
.auth-form label {
  display: block; font-size: 11px; text-transform: uppercase; letter-spacing: .06em;
  color: #94a3b8; margin: 12px 0 6px;
}
.field {
  position: relative; display: flex; align-items: center;
}
.field i:first-child {
  position: absolute; left: 14px; color: #64748b;
}
.field input {
  width: 100%; box-sizing: border-box;
  padding: 13px 42px 13px 40px;
  border-radius: 12px; border: 1px solid rgba(148,163,184,.25);
  background: #0f172a; color: #f8fafc; font-size: 14px;
}
.field input:focus {
  outline: none; border-color: #38bdf8;
  box-shadow: 0 0 0 3px rgba(56,189,248,.15);
}
.eye {
  position: absolute; right: 10px; border: none; background: transparent;
  color: #64748b; cursor: pointer;
}

.btn-submit {
  width: 100%; margin-top: 18px; padding: 13px; border: none; border-radius: 12px;
  background: linear-gradient(135deg, #0ea5e9, #22c55e); color: #fff;
  font-weight: 700; cursor: pointer; transition: transform .2s, box-shadow .2s;
}
.btn-submit:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 10px 24px rgba(14,165,233,.35);
}
.btn-submit:disabled { opacity: .7; cursor: not-allowed; }

.alert {
  margin-top: 14px; padding: 12px; border-radius: 12px;
  background: rgba(239,68,68,.12); border: 1px solid rgba(239,68,68,.35);
  color: #fca5a5; font-size: 13px;
}

.web3-row { margin-top: 16px; }
.btn-web3 {
  width: 100%; padding: 12px; border-radius: 12px; cursor: pointer;
  border: 1px solid rgba(245,158,11,.4); background: rgba(245,158,11,.08);
  color: #fde68a; font-weight: 600;
}
.footer-note {
  margin: 18px 0 0; text-align: center; color: #64748b; font-size: 11px;
}
.footer-note i { color: #34d399; }

@keyframes drift {
  0%, 100% { transform: translate(0,0) scale(1); }
  50% { transform: translate(30px,-20px) scale(1.08); }
}
@keyframes rise {
  from { opacity: 0; transform: translateY(18px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-float { animation: float 4s ease-in-out infinite; }
@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-8px); }
}

@media (max-width: 860px) {
  .login-shell { grid-template-columns: 1fr; }
  .brand-panel { display: none; }
}
</style>
