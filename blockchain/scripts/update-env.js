const fs = require('fs');
const path = require('path');

const contract = require('../build/contracts/VehicleRegistry.json');
const networks = Object.keys(contract.networks || {});
const networkId = networks[networks.length - 1];
const address = contract.networks?.[networkId]?.address;

if (!address) {
  console.error('[ERREUR] Adresse de contrat introuvable. Lancez truffle migrate d\'abord.');
  process.exit(1);
}

function updateEnvFile(filePath, updates) {
  if (!fs.existsSync(filePath)) {
    console.warn('[WARN] Fichier absent:', filePath);
    return;
  }

  let content = fs.readFileSync(filePath, 'utf8');
  for (const [key, value] of Object.entries(updates)) {
    const re = new RegExp(`^${key}=.*$`, 'm');
    content = re.test(content)
      ? content.replace(re, `${key}=${value}`)
      : `${content.trim()}\n${key}=${value}\n`;
  }
  fs.writeFileSync(filePath, content);
  console.log('[OK]', path.basename(filePath), '→', Object.keys(updates).join(', '));
}

const root = path.join(__dirname, '../..');
updateEnvFile(path.join(root, 'backend-laravel/toto/.env'), {
  BLOCKCHAIN_CONTRACT_ADDRESS: address,
});
updateEnvFile(path.join(root, 'frontend-spa/.env'), {
  VITE_APP_CONTRACT_ADDRESS: address,
});

console.log('');
console.log('BLOCKCHAIN_CONTRACT_ADDRESS=' + address);
