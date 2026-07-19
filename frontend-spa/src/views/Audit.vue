<template>

  <div class="page">

    <h1><i class="fas fa-search"></i> Audit</h1>

    <p>Consultation et vérification de l'historique certifié</p>



    <div class="verify-box">

      <input v-model="query" placeholder="VIN, plaque ou ID véhicule..." class="v-input" @keyup.enter="search" />

      <button @click="search" class="btn-search" :disabled="loading">

        <i :class="loading ? 'fas fa-spinner fa-spin' : 'fas fa-search'"></i>

        {{ loading ? 'Vérification...' : 'Vérifier' }}

      </button>

    </div>



    <p v-if="dataSource" class="source-hint">{{ dataSource }}</p>



    <div v-if="result" class="result-card" :class="result.valid ? 'valid' : 'invalid'">

      <i :class="result.valid ? 'fas fa-check-circle' : 'fas fa-times-circle'"></i>

      <div>

        <strong>{{ result.vehicle }}</strong>

        <p>{{ result.message }}</p>

        <div v-if="result.valid" class="details">

          <div><i class="fas fa-tachometer-alt"></i> Kilométrage: {{ result.mileage }} km</div>

          <div><i class="fas fa-calendar"></i> Dernière maintenance: {{ result.lastMaintenance }}</div>

          <div><i class="fas fa-cubes"></i> Transactions on-chain: {{ result.txCount }}</div>

        </div>

      </div>

    </div>

  </div>

</template>



<script>

import { ref } from 'vue'

import { verifyVehicleHybrid, SOURCE_LABELS } from '../js/utils/dataService'



export default {

  name: 'Audit',

  setup() {

    const query = ref('')

    const result = ref(null)

    const loading = ref(false)

    const dataSource = ref('')



    const search = async () => {

      if (!query.value.trim()) return

      loading.value = true

      result.value = null

      const response = await verifyVehicleHybrid(query.value.trim())

      result.value = response.data

      dataSource.value = SOURCE_LABELS[response.source] || ''

      loading.value = false

    }



    return { query, result, loading, dataSource, search }

  }

}

</script>



<style scoped>

.page { max-width: 700px; color: #e2e8f0; }

h1 { font-size: 22px; color: #f8fafc; margin: 0; }

h1 i { color: #38bdf8; margin-right: 8px; }

.page > p { color: #94a3b8; font-size: 13px; margin: 4px 0 20px; }

.source-hint { font-size: 11px; color: #64748b; margin: -12px 0 16px; }

.verify-box { display: flex; gap: 10px; margin-bottom: 20px; }

.v-input { flex: 1; padding: 12px 16px; border: 1px solid rgba(148,163,184,.25); border-radius: 12px; font-size: 14px; background: #1e293b; color: #f8fafc; }

.v-input:focus { outline: none; border-color: #38bdf8; }

.btn-search { padding: 12px 24px; background: #0ea5e9; color: white; border: none; border-radius: 12px; font-weight: 600; cursor: pointer; }

.btn-search:disabled { opacity: 0.7; cursor: wait; }

.result-card { display: flex; gap: 16px; padding: 20px; border-radius: 14px; border: 1px solid rgba(148,163,184,.15); }

.result-card.valid { background: rgba(16,185,129,.12); color: #6ee7b7; }

.result-card.invalid { background: rgba(239,68,68,.12); color: #fca5a5; }

.result-card i { font-size: 28px; }

.result-card strong { display: block; font-size: 15px; color: #f8fafc; }

.result-card p { font-size: 13px; margin: 4px 0; color: #cbd5e1; }

.details { display: flex; gap: 16px; margin-top: 10px; font-size: 12px; flex-wrap: wrap; color: #94a3b8; }

.details i { font-size: 12px; margin-right: 4px; color: #38bdf8; }

</style>

