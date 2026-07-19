import store from '../store'

export function buildSignMessage(action, payload = {}) {
  const lines = [
    'AutoChain Emma+',
    `Action: ${action}`,
    `Timestamp: ${Date.now()}`
  ]
  Object.entries(payload).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') {
      lines.push(`${key}: ${value}`)
    }
  })
  return lines.join('\n')
}

export function isMetaMaskSigningAvailable() {
  return store.getters['blockchain/isMetaMaskAvailable'] && !store.getters['blockchain/mock']
}

export async function signSensitiveAction(action, payload = {}) {
  if (!store.getters['blockchain/isMetaMaskAvailable']) {
    return {}
  }

  if (!store.getters['blockchain/isConnected']) {
    await store.dispatch('blockchain/connectWallet')
  }

  await store.dispatch('blockchain/ensureLocalNetwork')

  const wallet_address = store.getters['blockchain/account']
  if (!wallet_address) {
    throw new Error('Wallet MetaMask non connecté')
  }

  const message = buildSignMessage(action, payload)
  const signature = await store.dispatch('blockchain/signMessage', message)

  return { wallet_address, signature, message }
}

export async function withWeb3Proof(action, payload, apiCall) {
  try {
    const proof = await signSensitiveAction(action, payload)
    return apiCall(proof)
  } catch (error) {
    if (error?.code === 4001 || /rejected|refused|denied/i.test(error?.message || '')) {
      throw new Error('Signature MetaMask annulée')
    }
    if (store.getters['blockchain/isMetaMaskAvailable']) {
      throw error
    }
    return apiCall({})
  }
}
