require('dotenv').config({ path: require('path').join(__dirname, '../.env') });

const fs = require('fs');
const artifact = require('../build/contracts/VehicleRegistry.json');
const {
  ROOT,
  BACKEND_ENV,
  FRONTEND_ENV,
  SEPOLIA_PROFILE,
  PROFILES_DIR,
  ensureDir,
  updateEnvFile,
  deriveAddressFromPrivateKey,
  normalizePrivateKey,
} = require('./env-utils');

const sepoliaNetworkId = '11155111';
const address = artifact.networks?.[sepoliaNetworkId]?.address
  || artifact.networks[Object.keys(artifact.networks).pop()]?.address;

if (!address) {
  console.error('[ERREUR] Adresse Sepolia introuvable. Lancez deploy-sepolia.bat d\'abord.');
  process.exit(1);
}

const rpcUrl = (process.env.SEPOLIA_RPC_URL || '').trim();
const privateKey = normalizePrivateKey(process.env.PRIVATE_KEY || '');
const adminAddress = deriveAddressFromPrivateKey(privateKey);

if (!rpcUrl || rpcUrl.includes('YOUR_INFURA_KEY')) {
  console.error('[ERREUR] SEPOLIA_RPC_URL manquant dans blockchain/.env');
  process.exit(1);
}

if (!adminAddress) {
  console.error('[ERREUR] PRIVATE_KEY invalide dans blockchain/.env');
  process.exit(1);
}

const backendUpdates = {
  BLOCKCHAIN_RPC_URL: rpcUrl,
  BLOCKCHAIN_CONTRACT_ADDRESS: address,
  BLOCKCHAIN_CHAIN_ID: '11155111',
  BLOCKCHAIN_NETWORK: 'sepolia',
  BLOCKCHAIN_EXPLORER_TX_URL: 'https://sepolia.etherscan.io/tx/{hash}',
  BLOCKCHAIN_ADMIN_ADDRESS: adminAddress,
  ADMIN_PRIVATE_KEY: privateKey,
};

const frontendUpdates = {
  VITE_APP_CONTRACT_ADDRESS: address,
  VITE_CHAIN_ID: '11155111',
};

updateEnvFile(BACKEND_ENV, backendUpdates);
updateEnvFile(FRONTEND_ENV, frontendUpdates);

ensureDir(PROFILES_DIR);
fs.writeFileSync(SEPOLIA_PROFILE, JSON.stringify({
  saved_at: new Date().toISOString(),
  contract_address: address,
  admin_address: adminAddress,
  backend: backendUpdates,
  frontend: frontendUpdates,
}, null, 2));

try {
  require('./update-admin-wallet.js');
} catch (error) {
  console.warn('[WARN] Mise à jour wallet admin ignorée.');
}

console.log('');
console.log('=== Sepolia configuré automatiquement ===');
console.log('Contrat   :', address);
console.log('Admin     :', adminAddress);
console.log('Explorer  : https://sepolia.etherscan.io/address/' + address);
console.log('');
console.log('Prochaines étapes MANUELLES :');
console.log('1. MetaMask → réseau Sepolia (chainId 11155111)');
console.log('2. Redémarrer start-backend.bat et npm run dev');
console.log('3. Se reconnecter sur l\'application');
console.log('');
