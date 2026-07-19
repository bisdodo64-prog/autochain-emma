const fs = require('fs');
const {
  BACKEND_ENV,
  FRONTEND_ENV,
  LOCAL_PROFILE,
  updateEnvFile,
} = require('./env-utils');

if (!fs.existsSync(LOCAL_PROFILE)) {
  console.error('[ERREUR] Profil local absent. Lancez setup-sepolia.bat une fois ou restaurez manuellement.');
  process.exit(1);
}

const profile = JSON.parse(fs.readFileSync(LOCAL_PROFILE, 'utf8'));

if (profile.backend) {
  updateEnvFile(BACKEND_ENV, profile.backend);
}
if (profile.frontend) {
  updateEnvFile(FRONTEND_ENV, profile.frontend);
}

console.log('');
console.log('=== Ganache local restauré ===');
console.log('Contrat   :', profile.backend?.BLOCKCHAIN_CONTRACT_ADDRESS || '(voir .env)');
console.log('');
console.log('Redémarrez start-blockchain.bat, start-backend.bat et npm run dev');
console.log('');
