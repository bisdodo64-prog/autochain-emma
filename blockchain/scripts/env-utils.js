const fs = require('fs');
const path = require('path');
const Web3 = require('web3');

const ROOT = path.join(__dirname, '../..');
const BACKEND_ENV = path.join(ROOT, 'backend-laravel/toto/.env');
const FRONTEND_ENV = path.join(ROOT, 'frontend-spa/.env');
const PROFILES_DIR = path.join(ROOT, 'config/blockchain-profiles');
const LOCAL_PROFILE = path.join(PROFILES_DIR, 'local.json');
const SEPOLIA_PROFILE = path.join(PROFILES_DIR, 'sepolia.json');

function ensureDir(dir) {
  if (!fs.existsSync(dir)) {
    fs.mkdirSync(dir, { recursive: true });
  }
}

function readEnvFile(filePath) {
  if (!fs.existsSync(filePath)) {
    return {};
  }
  const content = fs.readFileSync(filePath, 'utf8');
  const values = {};
  content.split(/\r?\n/).forEach((line) => {
    if (!line || line.trim().startsWith('#')) return;
    const idx = line.indexOf('=');
    if (idx === -1) return;
    values[line.slice(0, idx).trim()] = line.slice(idx + 1).trim();
  });
  return values;
}

function updateEnvFile(filePath, updates) {
  if (!fs.existsSync(filePath)) {
    console.warn('[WARN] Fichier absent:', filePath);
    return false;
  }

  let content = fs.readFileSync(filePath, 'utf8');
  for (const [key, value] of Object.entries(updates)) {
    const re = new RegExp(`^${key}=.*$`, 'm');
    content = re.test(content)
      ? content.replace(re, `${key}=${value}`)
      : `${content.trim()}\n${key}=${value}\n`;
  }
  fs.writeFileSync(filePath, content);
  console.log('[OK]', path.relative(ROOT, filePath), '→', Object.keys(updates).join(', '));
  return true;
}

function deriveAddressFromPrivateKey(privateKey) {
  if (!privateKey || privateKey.includes('your_private_key')) {
    return null;
  }
  const web3 = new Web3();
  let pk = privateKey.trim();
  if (!pk.startsWith('0x')) {
    pk = `0x${pk}`;
  }
  return web3.eth.accounts.privateKeyToAccount(pk).address;
}

function normalizePrivateKey(privateKey) {
  if (!privateKey) return '';
  let pk = privateKey.trim();
  if (!pk.startsWith('0x')) {
    pk = `0x${pk}`;
  }
  return pk;
}

module.exports = {
  ROOT,
  BACKEND_ENV,
  FRONTEND_ENV,
  PROFILES_DIR,
  LOCAL_PROFILE,
  SEPOLIA_PROFILE,
  ensureDir,
  readEnvFile,
  updateEnvFile,
  deriveAddressFromPrivateKey,
  normalizePrivateKey,
};
