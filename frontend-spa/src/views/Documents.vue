<template>
  <div class="page">
    <div class="page-top">
      <div>
        <h1><i class="fas fa-file-alt"></i> Documents</h1>
        <p>Cartes grises, assurances, factures et documents administratifs</p>
      </div>
      <button class="btn-add" @click="openUpload"><i class="fas fa-upload"></i> Téléverser</button>
    </div>

    <div class="filters-bar">
      <input v-model="search" placeholder="Rechercher un document..." class="search-input" />
      <div class="type-chips">
        <button
          v-for="t in types"
          :key="t.value"
          :class="{ active: filterType === t.value }"
          @click="filterType = t.value"
        >
          {{ t.label }}
        </button>
      </div>
    </div>

    <div class="docs-grid">
      <div v-for="doc in filteredDocuments" :key="doc.id" class="doc-card">
        <div class="doc-icon" :style="{ background: doc.color }">
          <i :class="doc.icon"></i>
        </div>
        <div class="doc-info">
          <strong>{{ doc.name }}</strong>
          <small>{{ doc.vehicle }}</small>
          <span class="doc-type">{{ doc.type }}</span>
        </div>
        <div class="doc-meta">
          <span :class="'expiry ' + doc.expiryClass">
            <i class="far fa-calendar-alt"></i> {{ doc.expiry }}
          </span>
          <span v-if="doc.blockchainVerified" class="bc-badge"><i class="fas fa-shield-alt"></i> Certifié</span>
        </div>
        <div class="doc-actions">
          <button title="Voir" @click="viewDoc(doc)"><i class="fas fa-eye"></i></button>
          <button title="Télécharger PDF" @click="downloadDoc(doc)"><i class="fas fa-download"></i></button>
          <button v-if="!doc.blockchainVerified" title="Certifier Blockchain" :disabled="certifyingId === doc.id" @click="certifyDoc(doc)">
            <i class="fas fa-cubes"></i>
          </button>
        </div>
      </div>
    </div>
    <p v-if="filteredDocuments.length === 0" class="empty">Aucun document pour ce filtre.</p>

    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal-card">
        <h3>{{ modalMode === 'upload' ? 'Téléverser un document' : 'Détail du document' }}</h3>
        <template v-if="modalMode === 'upload'">
          <label>Nom</label>
          <input v-model="form.name" />
          <label>Véhicule</label>
          <select v-model="form.vehicleId">
            <option disabled value="">Choisir un véhicule</option>
            <option v-for="v in vehiclesList" :key="v.id" :value="v.id">
              {{ v.brand }} {{ v.model }} ({{ v.licensePlate }})
            </option>
          </select>
          <label>Type</label>
          <select v-model="form.type">
            <option>Carte grise</option>
            <option>Assurance</option>
            <option>Contrôle technique</option>
            <option>Facture</option>
            <option>Autre</option>
          </select>
          <label>Fichier</label>
          <input type="file" accept=".pdf,.png,.jpg,.jpeg" @change="onFile" />
        </template>
        <template v-else>
          <div class="detail-grid">
            <div><label>Nom</label><span>{{ selected?.name }}</span></div>
            <div><label>Véhicule</label><span>{{ selected?.vehicle }}</span></div>
            <div><label>Type</label><span>{{ selected?.type }}</span></div>
            <div><label>Expiration</label><span>{{ selected?.expiry }}</span></div>
            <div><label>Blockchain</label><span>{{ selected?.blockchainVerified ? 'Certifié' : 'Non certifié' }}</span></div>
          </div>
        </template>
        <div class="modal-actions">
          <button class="btn-secondary" @click="closeModal">Fermer</button>
          <button v-if="modalMode === 'upload'" class="btn-primary" @click="saveUpload">Enregistrer</button>
          <button v-else class="btn-primary" @click="downloadDoc(selected)">Télécharger PDF</button>
        </div>
      </div>
    </div>

    <div v-if="toast.show" class="toast">{{ toast.message }}</div>
  </div>
</template>

<script>
import { computed, ref, onMounted } from 'vue'
import { downloadPdf } from '../js/utils/pdf'
import api from '../js/api'
import { loadDocuments, saveDocuments } from '../js/utils/localData'
import { fetchDocumentsHybrid, fetchVehiclesHybrid, certifyDocumentHybrid } from '../js/utils/dataService'

export default {
  name: 'Documents',
  setup() {
    const search = ref('')
    const filterType = ref('')
    const showModal = ref(false)
    const modalMode = ref('upload')
    const selected = ref(null)
    const toast = ref({ show: false, message: '' })
    const form = ref({ name: '', vehicleId: '', type: 'Facture' })
    const selectedFile = ref(null)
    const certifyingId = ref(null)
    const uploading = ref(false)

    const types = [
      { label: 'Tous', value: '' },
      { label: 'Assurance', value: 'Assurance' },
      { label: 'Contrôle technique', value: 'Contrôle technique' },
      { label: 'Carte grise', value: 'Carte grise' },
      { label: 'Facture', value: 'Facture' },
      { label: 'Autre', value: 'Autre' }
    ]

    const documents = ref(loadDocuments())
    const vehiclesList = ref([])
    const dataSource = ref('local')
    const persist = () => saveDocuments(documents.value)

    const loadData = async () => {
      const [docsResult, vehiclesResult] = await Promise.all([
        fetchDocumentsHybrid(),
        fetchVehiclesHybrid()
      ])
      documents.value = docsResult.data
      vehiclesList.value = vehiclesResult.data
      dataSource.value = docsResult.source
    }

    onMounted(loadData)

    const showToast = (message) => {
      toast.value = { show: true, message }
      setTimeout(() => (toast.value.show = false), 2200)
    }

    const filteredDocuments = computed(() => {
      const q = search.value.toLowerCase()
      return documents.value.filter((doc) => {
        const matchSearch = !q || doc.name.toLowerCase().includes(q) || doc.vehicle.toLowerCase().includes(q) || doc.type.toLowerCase().includes(q)
        const matchType = !filterType.value || doc.type === filterType.value
        return matchSearch && matchType
      })
    })

    const openUpload = () => {
      modalMode.value = 'upload'
      form.value = {
        name: '',
        vehicleId: vehiclesList.value[0]?.id || '',
        type: 'Facture'
      }
      selectedFile.value = null
      showModal.value = true
    }

    const closeModal = () => {
      showModal.value = false
      selected.value = null
    }

    const onFile = (e) => {
      const file = e.target.files?.[0]
      if (!file) return
      selectedFile.value = file
      if (!form.value.name) form.value.name = file.name.replace(/\.[^/.]+$/, '')
      showToast(`Fichier ${file.name} prêt.`)
    }

    const saveUpload = async () => {
      if (!form.value.name || !form.value.vehicleId) {
        showToast('Nom et véhicule requis.')
        return
      }
      if (!selectedFile.value) {
        showToast('Choisissez un fichier.')
        return
      }

      const vehicle = vehiclesList.value.find((v) => String(v.id) === String(form.value.vehicleId))
      const typeMap = {
        'Carte grise': 'registration',
        Assurance: 'insurance',
        'Contrôle technique': 'tech_control',
        Facture: 'facture',
        Autre: 'autre'
      }

      uploading.value = true
      try {
        const formData = new FormData()
        formData.append('file', selectedFile.value)
        formData.append('document_type', typeMap[form.value.type] || 'facture')
        await api.uploadDocument(vehicle.id, formData)
        await loadData()
        closeModal()
        showToast('Document téléversé sur le serveur.')
      } catch (err) {
        const msg = err?.response?.data?.errors
          ? Object.values(err.response.data.errors).flat()[0]
          : err?.response?.data?.message || err.message || 'Upload échoué'
        showToast(msg)
        const localDoc = {
          id: Date.now(),
          name: form.value.name,
          vehicle: vehicle ? `${vehicle.brand} ${vehicle.model}` : 'Véhicule',
          type: form.value.type,
          expiry: 'N/A',
          expiryClass: 'ok',
          icon: 'fas fa-file-alt',
          color: 'rgba(56,189,248,.2)',
          blockchainVerified: false
        }
        documents.value.unshift(localDoc)
        persist()
        closeModal()
        showToast('Document enregistré en local (API indisponible).')
      } finally {
        uploading.value = false
      }
    }

    const viewDoc = (doc) => {
      selected.value = doc
      modalMode.value = 'view'
      showModal.value = true
    }

    const downloadDoc = async (doc) => {
      if (!doc) return
      try {
        if (doc.id && !(typeof doc.id === 'number' && doc.id > 1e12)) {
          const blob = await api.downloadDocument(doc.id)
          const url = URL.createObjectURL(blob)
          const a = document.createElement('a')
          a.href = url
          a.download = doc.name || 'document'
          a.click()
          URL.revokeObjectURL(url)
          showToast(`Téléchargement de ${doc.name}`)
          return
        }
      } catch {
        // fallback PDF factice
      }
      downloadPdf(doc, `${doc.name || 'document'}.pdf`)
      showToast(`PDF généré pour ${doc.name}`)
    }

    const certifyDoc = async (doc) => {
      if (typeof doc.id === 'number' && doc.id > 1e12) {
        doc.blockchainVerified = true
        persist()
        showToast(`${doc.name} certifié (local).`)
        return
      }
      certifyingId.value = doc.id
      try {
        const result = await certifyDocumentHybrid(doc)
        if (result.source === 'api') {
          await loadData()
        } else {
          const idx = documents.value.findIndex((d) => d.id === doc.id)
          if (idx !== -1) documents.value[idx] = result.doc
          persist()
        }
        showToast(result.message || `${doc.name} certifié.`)
      } catch (err) {
        showToast(err?.response?.data?.message || err.message || 'Certification échouée')
      } finally {
        certifyingId.value = null
      }
    }

    return {
      search,
      filterType,
      types,
      filteredDocuments,
      vehiclesList,
      showModal,
      modalMode,
      selected,
      form,
      toast,
      openUpload,
      closeModal,
      onFile,
      saveUpload,
      viewDoc,
      downloadDoc,
      certifyDoc,
      certifyingId,
      uploading
    }
  }
}
</script>

<style scoped>
.page { max-width: 1100px; color: #e2e8f0; }
.page-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
.page-top h1 { font-size: 22px; color: #f8fafc; margin: 0; }
.page-top h1 i { color: #34d399; margin-right: 8px; }
.page-top p { color: #94a3b8; font-size: 13px; margin: 4px 0 0; }
.btn-add { padding: 10px 20px; background: #10b981; color: #fff; border: none; border-radius: 10px; cursor: pointer; font-weight: 600; }

.filters-bar { display: flex; flex-direction: column; gap: 12px; margin-bottom: 16px; }
.search-input { padding: 10px 16px; border: 1px solid rgba(148,163,184,.25); border-radius: 10px; font-size: 14px; background: #1e293b; color: #f8fafc; }
.type-chips { display: flex; flex-wrap: wrap; gap: 8px; }
.type-chips button { padding: 8px 12px; border-radius: 999px; border: 1px solid rgba(148,163,184,.2); background: #1e293b; color: #94a3b8; cursor: pointer; font-size: 12px; }
.type-chips button.active { background: #10b981; color: #fff; border-color: #10b981; }

.docs-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 12px; }
.doc-card { background: #1e293b; border-radius: 14px; padding: 18px; display: flex; align-items: center; gap: 14px; border: 1px solid rgba(148,163,184,.12); flex-wrap: wrap; }
.doc-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; color: #38bdf8; flex-shrink: 0; }
.doc-info { flex: 1; min-width: 120px; }
.doc-info strong { display: block; font-size: 14px; color: #f8fafc; }
.doc-info small { color: #94a3b8; font-size: 11px; }
.doc-type { display: inline-block; font-size: 10px; padding: 2px 8px; border-radius: 10px; background: rgba(148,163,184,.12); color: #cbd5e1; margin-top: 4px; }
.doc-meta { display: flex; flex-direction: column; gap: 4px; align-items: flex-end; }
.expiry { font-size: 11px; padding: 3px 8px; border-radius: 8px; }
.expiry.ok { background: rgba(16,185,129,.15); color: #34d399; }
.expiry.warning { background: rgba(245,158,11,.15); color: #fbbf24; }
.bc-badge { font-size: 10px; padding: 2px 8px; border-radius: 8px; background: rgba(16,185,129,.15); color: #34d399; }
.doc-actions { display: flex; gap: 4px; }
.doc-actions button { width: 30px; height: 30px; border-radius: 6px; border: none; background: rgba(148,163,184,.12); color: #e2e8f0; cursor: pointer; font-size: 12px; }
.doc-actions button:hover { background: #2563eb; color: #fff; }
.empty { color: #94a3b8; text-align: center; padding: 24px; }

.modal-overlay { position: fixed; inset: 0; background: rgba(2,6,23,.7); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 16px; }
.modal-card { width: min(460px, 100%); background: #1e293b; border-radius: 16px; padding: 22px; border: 1px solid rgba(148,163,184,.2); }
.modal-card h3 { margin: 0 0 12px; color: #f8fafc; }
.modal-card label { display: block; font-size: 11px; color: #94a3b8; text-transform: uppercase; margin: 10px 0 4px; }
.modal-card input, .modal-card select { width: 100%; padding: 10px 12px; border-radius: 10px; border: 1px solid rgba(148,163,184,.25); background: #0f172a; color: #f8fafc; box-sizing: border-box; }
.detail-grid { display: grid; gap: 10px; }
.detail-grid span { color: #f8fafc; font-size: 14px; }
.modal-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px; }
.btn-primary { padding: 10px 16px; border: none; border-radius: 10px; background: #10b981; color: #fff; cursor: pointer; font-weight: 600; }
.btn-secondary { padding: 10px 16px; border: none; border-radius: 10px; background: rgba(148,163,184,.15); color: #e2e8f0; cursor: pointer; }
.toast { position: fixed; right: 20px; bottom: 20px; padding: 12px 16px; border-radius: 12px; background: #0f172a; color: #f8fafc; border: 1px solid #34d399; z-index: 1100; }

@media (max-width: 768px) {
  .docs-grid { grid-template-columns: 1fr; }
  .doc-meta { align-items: flex-start; width: 100%; }
  .toast { left: 16px; right: 16px; bottom: 16px; }
}

@media (max-width: 480px) {
  .page-top h1 { font-size: 18px; }
}
</style>
