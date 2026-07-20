<template>

  <div class="page">

    <h1><i class="fas fa-cubes"></i> Blockchain</h1>

    <p>Registre immuable et certifications des véhicules</p>

    <p v-if="dataSource" class="source-hint">{{ dataSource }}</p>
    <p v-if="localAccount" class="chain-status warn">
      <i class="fas fa-info-circle"></i>
      Session locale détectée — déconnectez-vous puis connectez-vous ou créez un compte via le formulaire.
    </p>
    <p v-if="chainLive !== null" class="chain-status" :class="chainLive ? 'live' : 'stub'">
      <i :class="chainLive ? 'fas fa-link' : 'fas fa-unlink'"></i>
      {{ chainLive ? (chainNetwork === 'sepolia' ? 'Nœud Sepolia connecté' : 'Nœud Ethereum connecté (Ganache)') : (chainNetwork === 'sepolia' ? 'Sepolia inaccessible — vérifiez RPC Infura' : 'Ganache non détecté — lancez start-blockchain.bat') }}
    </p>
    <p v-if="chainError" class="chain-status stub">{{ chainError }}</p>
    <p v-if="chainLive && chainNetwork" class="chain-hint">
      Réseau : <strong>{{ chainNetwork }}</strong>
      <span v-if="chainNetwork === 'local'"> — copiez le hash et cherchez-le dans Ganache → Transactions</span>
      <span v-else-if="chainNetwork === 'sepolia'"> — transactions visibles sur Sepolia Etherscan</span>
    </p>



    <div v-if="loading" class="loading-hint"><i class="fas fa-spinner fa-spin"></i> Chargement...</div>



    <div class="stats-row">

      <div class="bc-stat"><strong>{{ stats.tx }}</strong><span>Transactions</span></div>

      <div class="bc-stat"><strong>{{ stats.certified }}</strong><span>Véhicules certifiés</span></div>

      <div class="bc-stat"><strong>{{ stats.maintenance }}</strong><span>Maintenances enregistrées</span></div>

      <div class="bc-stat"><strong>{{ stats.anomalies }}</strong><span>Anomalies</span></div>

    </div>



    <div class="section">

      <div class="section-top">

        <h3>Dernières transactions</h3>

        <button class="btn-sync" @click="syncChain" :disabled="syncing">

          <i :class="syncing ? 'fas fa-spinner fa-spin' : 'fas fa-sync'"></i>

          {{ syncing ? 'Sync...' : 'Synchroniser' }}

        </button>

      </div>

      <p v-if="!transactions.length && !loading" class="empty-hint">Aucune transaction blockchain enregistrée.</p>

      <div

        v-for="tx in transactions"

        :key="tx.id"

        class="tx-card"

        @click="openTx(tx)"

      >

        <div class="tx-icon" :class="tx.type"><i :class="tx.icon"></i></div>

        <div class="tx-info">

          <strong>{{ tx.label }}</strong>

          <small>{{ tx.vehicle }}</small>

          <span class="tx-hash">{{ tx.hash }}</span>

        </div>

        <span class="tx-date">{{ tx.date }}</span>

      </div>

    </div>



    <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">

      <div class="modal-card">

        <h3>{{ selected?.label }}</h3>

        <div class="detail-grid">

          <div><label>Véhicule</label><span>{{ selected?.vehicle }}</span></div>

          <div><label>Date</label><span>{{ selected?.date }}</span></div>

          <div><label>Hash</label><span class="mono">{{ selected?.fullHash }}</span></div>

        </div>

        <div class="modal-actions">

          <a
            v-if="selected?.explorerUrl"
            :href="selected.explorerUrl"
            target="_blank"
            rel="noopener noreferrer"
            class="btn-explorer"
          >
            <i class="fas fa-external-link-alt"></i> Voir sur l'explorer
          </a>

          <button class="btn-secondary" @click="copyHash">Copier le hash</button>

          <button class="btn-primary" @click="showModal = false">Fermer</button>

        </div>

      </div>

    </div>



    <div v-if="toast.show" class="toast">{{ toast.message }}</div>

  </div>

</template>



<script>

import { onMounted, ref } from 'vue'
import api from '../js/api'
import { fetchBlockchainHybrid, syncBlockchainHybrid, SOURCE_LABELS, isLocalToken } from '../js/utils/dataService'

export default {
  name: 'Blockchain',
  setup() {
    const showModal = ref(false)
    const selected = ref(null)
    const toast = ref({ show: false, message: '' })
    const loading = ref(true)
    const syncing = ref(false)
    const dataSource = ref('')
    const chainLive = ref(null)
    const chainNetwork = ref('')
    const chainError = ref('')
    const localAccount = ref(isLocalToken())
    const transactions = ref([])
    const stats = ref({ tx: 0, certified: 0, maintenance: 0, anomalies: 0 })

    const loadData = async () => {
      loading.value = true
      localAccount.value = isLocalToken()
      const result = await fetchBlockchainHybrid()
      transactions.value = result.transactions
      stats.value = result.stats
      dataSource.value = SOURCE_LABELS[result.source] || ''
      try {
        const status = await api.getBlockchainStatus()
        chainLive.value = Boolean(status?.blockchain?.live)
        chainNetwork.value = status?.blockchain?.network || 'local'
        chainError.value = status?.blockchain?.error || ''
      } catch {
        chainLive.value = false
        chainNetwork.value = ''
        chainError.value = 'Impossible de joindre /api/blockchain/status'
      }
      loading.value = false
    }



    onMounted(loadData)



    const showToast = (message) => {

      toast.value = { show: true, message }

      setTimeout(() => (toast.value.show = false), 2200)

    }



    const openTx = (tx) => {

      selected.value = tx

      showModal.value = true

    }



    const copyHash = async () => {

      if (!selected.value?.fullHash) return

      try {

        await navigator.clipboard.writeText(selected.value.fullHash)

        showToast('Hash copié.')

      } catch {

        showToast('Copie impossible.')

      }

    }



    const syncChain = async () => {
      syncing.value = true
      try {
        showToast('Synchronisation en cours…')
        const result = await syncBlockchainHybrid()
        if (result.transactions) {
          transactions.value = result.transactions
          stats.value = result.stats
          dataSource.value = SOURCE_LABELS[result.source] || dataSource.value
        } else {
          await loadData()
        }
        showToast(result.message || 'Registre blockchain synchronisé.')
      } catch (error) {
        const msg = error?.response?.data?.message || error?.message || 'Synchronisation échouée'
        showToast(msg)
      } finally {
        syncing.value = false
      }
    }



    return { transactions, stats, showModal, selected, toast, loading, syncing, dataSource, chainLive, chainNetwork, chainError, localAccount, openTx, copyHash, syncChain }

  }

}

</script>



<style scoped>

.page { max-width: 1000px; color: #e2e8f0; }

h1 { font-size: 22px; color: #f8fafc; margin: 0; }

h1 i { color: #a78bfa; margin-right: 8px; }

.page > p { color: #94a3b8; font-size: 13px; margin: 4px 0 20px; }

.source-hint { font-size: 11px; color: #64748b; margin: -12px 0 16px; }
.chain-status { font-size: 12px; margin: -8px 0 16px; display: flex; align-items: center; gap: 6px; }
.chain-status.live { color: #34d399; }
.chain-status.stub { color: #fbbf24; }
.chain-status.warn { color: #7dd3fc; }
.chain-hint { font-size: 11px; color: #64748b; margin: -8px 0 16px; }
.chain-hint strong { color: #94a3b8; }

.loading-hint, .empty-hint { color: #94a3b8; font-size: 13px; margin-bottom: 12px; }

.stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 20px; }

.bc-stat { background: #1e293b; padding: 16px; border-radius: 12px; text-align: center; border: 1px solid rgba(148,163,184,.12); }

.bc-stat strong { display: block; font-size: 24px; color: #c4b5fd; }

.bc-stat span { font-size: 12px; color: #94a3b8; }

.section { background: #1e293b; border-radius: 14px; padding: 20px; border: 1px solid rgba(148,163,184,.12); }

.section-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; gap: 10px; }

.section h3 { font-size: 15px; margin: 0; color: #f8fafc; }

.btn-sync { padding: 8px 12px; border: none; border-radius: 8px; background: #7c3aed; color: #fff; cursor: pointer; font-size: 12px; }

.btn-sync:disabled { opacity: 0.7; cursor: wait; }

.tx-card { display: flex; align-items: center; gap: 14px; padding: 12px; border-radius: 10px; margin-bottom: 8px; background: #0f172a; cursor: pointer; border: 1px solid transparent; }

.tx-card:hover { border-color: rgba(167,139,250,.4); }

.tx-icon { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; }

.tx-icon.registration { background: rgba(16,185,129,.15); color: #34d399; }

.tx-icon.mileage { background: rgba(37,99,235,.15); color: #60a5fa; }

.tx-icon.maintenance { background: rgba(245,158,11,.15); color: #fbbf24; }

.tx-info { flex: 1; }

.tx-info strong { display: block; font-size: 13px; color: #f8fafc; }

.tx-info small { color: #94a3b8; font-size: 11px; }

.tx-hash { display: block; font-family: ui-monospace, monospace; font-size: 11px; color: #c4b5fd; }

.tx-date { font-size: 11px; color: #94a3b8; }

.modal-overlay { position: fixed; inset: 0; background: rgba(2,6,23,.7); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 16px; }

.modal-card { width: min(460px, 100%); background: #1e293b; border-radius: 16px; padding: 22px; border: 1px solid rgba(148,163,184,.2); }

.modal-card h3 { margin: 0 0 12px; color: #f8fafc; }

.detail-grid { display: grid; gap: 10px; }

.detail-grid label { display: block; font-size: 11px; color: #94a3b8; text-transform: uppercase; }

.detail-grid span { color: #f8fafc; font-size: 13px; }

.mono { font-family: ui-monospace, monospace; word-break: break-all; font-size: 11px !important; }

.modal-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px; }

.btn-primary { padding: 10px 16px; border: none; border-radius: 10px; background: #7c3aed; color: #fff; cursor: pointer; }

.btn-secondary { padding: 10px 16px; border: none; border-radius: 10px; background: rgba(148,163,184,.15); color: #e2e8f0; cursor: pointer; }

.btn-explorer { padding: 10px 16px; border-radius: 10px; background: rgba(52,211,153,.15); color: #34d399; text-decoration: none; font-size: 13px; display: inline-flex; align-items: center; gap: 6px; }

.toast { position: fixed; right: 20px; bottom: 20px; padding: 12px 16px; border-radius: 12px; background: #0f172a; color: #f8fafc; border: 1px solid #a78bfa; z-index: 1100; }

@media (max-width: 768px) {
  .section-top { flex-direction: column; align-items: flex-start; }
  .tx-card { flex-wrap: wrap; }
  .tx-hash { word-break: break-all; }
  .toast { left: 16px; right: 16px; bottom: 16px; }
}

@media (max-width: 480px) {
  .page-top h1 { font-size: 18px; }
  .bc-stat strong { font-size: 20px; }
}

</style>

