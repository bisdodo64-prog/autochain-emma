<template>
  <div class="wallet-connect">
    <button v-if="!isConnected" @click="connectWallet" class="btn btn-primary" :disabled="loading">
      {{ loading ? 'Connexion...' : '🦊 MetaMask' }}
    </button>
    <div v-else class="wallet-info">
      <span class="address">{{ truncatedAddress }}</span>
      <span class="badge" :class="networkClass">{{ networkName }}</span>
      <span v-if="mock" class="badge network-mock">Mock</span>
      <button @click="disconnect" class="btn btn-danger btn-sm">Déco.</button>
    </div>
  </div>
</template>

<script>
import { mapGetters, mapActions } from 'vuex'

export default {
  name: 'WalletConnect',
  computed: {
    ...mapGetters('blockchain', ['isConnected', 'account', 'chainId', 'loading', 'mock']),
    truncatedAddress() {
      if (!this.account) return ''
      return `${this.account.substring(0, 6)}...${this.account.substring(this.account.length - 4)}`
    },
    networkClass() {
      const networks = { 1: 'network-mainnet', 5: 'network-goerli', 11155111: 'network-sepolia', 1337: 'network-local' }
      return networks[this.chainId] || 'network-unknown'
    },
    networkName() {
      const names = { 1: 'Mainnet', 5: 'Goerli', 11155111: 'Sepolia', 1337: 'Local' }
      return names[this.chainId] || `Chain ${this.chainId || '?'}`
    }
  },
  mounted() {
    this.checkConnection()
  },
  methods: {
    ...mapActions('blockchain', ['connectWallet', 'disconnectWallet', 'checkConnection']),
    async disconnect() {
      await this.disconnectWallet()
    }
  }
}
</script>

<style scoped>
.wallet-connect { display: inline-block; }
.wallet-info { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.address { font-family: monospace; background: rgba(148,163,184,.15); padding: 4px 8px; border-radius: 4px; color: #e2e8f0; }
.badge { padding: 2px 8px; border-radius: 12px; font-size: 12px; }
.network-local { background: #4CAF50; color: white; }
.network-sepolia, .network-goerli { background: #FF9800; color: white; }
.network-mainnet { background: #2196F3; color: white; }
.network-mock { background: #64748b; color: white; }
.btn-primary { background: #2563eb; color: white; border: none; padding: 8px 14px; border-radius: 8px; cursor: pointer; }
.btn-danger { background: #f44336; color: white; border: none; padding: 4px 12px; border-radius: 4px; cursor: pointer; }
</style>
