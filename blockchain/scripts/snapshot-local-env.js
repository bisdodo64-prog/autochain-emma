const fs = require('fs');
const {
  BACKEND_ENV,
  FRONTEND_ENV,
  LOCAL_PROFILE,
  PROFILES_DIR,
  ensureDir,
  readEnvFile,
} = require('./env-utils');

const BACKEND_KEYS = [
  'BLOCKCHAIN_RPC_URL',
  'BLOCKCHAIN_CONTRACT_ADDRESS',
  'BLOCKCHAIN_CHAIN_ID',
  'BLOCKCHAIN_NETWORK',
  'BLOCKCHAIN_EXPLORER_TX_URL',
  'BLOCKCHAIN_ADMIN_ADDRESS',
  'BLOCKCHAIN_GARAGE_ADDRESS',
  'ADMIN_PRIVATE_KEY',
  'GARAGE_PRIVATE_KEY',
];

const FRONTEND_KEYS = [
  'VITE_APP_CONTRACT_ADDRESS',
  'VITE_CHAIN_ID',
];

function pick(values, keys) {
  const out = {};
  keys.forEach((key) => {
    if (values[key] !== undefined) {
      out[key] = values[key];
    }
  });
  return out;
}

ensureDir(PROFILES_DIR);

const snapshot = {
  saved_at: new Date().toISOString(),
  backend: pick(readEnvFile(BACKEND_ENV), BACKEND_KEYS),
  frontend: pick(readEnvFile(FRONTEND_ENV), FRONTEND_KEYS),
};

fs.writeFileSync(LOCAL_PROFILE, JSON.stringify(snapshot, null, 2));
console.log('[OK] Profil local sauvegardé → config/blockchain-profiles/local.json');
