const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');
const { SEPOLIA_PROFILE, ROOT } = require('./env-utils');

const phpCandidates = [
  process.env.LARAGON_PHP,
  'C:\\laragon\\bin\\php\\php-8.1.10-Win32-vs16-x64\\php.exe',
  'php',
].filter(Boolean);

function runArtisan(expression) {
  const backendPath = path.join(ROOT, 'backend-laravel/toto');
  let lastError = null;

  for (const php of phpCandidates) {
    try {
      execSync(`"${php}" artisan tinker --execute="${expression.replace(/"/g, '\\"')}"`, {
        cwd: backendPath,
        stdio: 'pipe',
      });
      return php;
    } catch (error) {
      lastError = error;
    }
  }

  throw lastError;
}

if (!fs.existsSync(SEPOLIA_PROFILE)) {
  console.warn('[WARN] Profil Sepolia absent — wallet admin non mis à jour.');
  process.exit(0);
}

const profile = JSON.parse(fs.readFileSync(SEPOLIA_PROFILE, 'utf8'));
const adminAddress = profile.admin_address;

if (!adminAddress) {
  console.warn('[WARN] Adresse admin absente du profil Sepolia.');
  process.exit(0);
}

try {
  runArtisan(`DB::table('users')->where('email','admin@autochain.com')->update(['wallet_address'=>'${adminAddress}']);`);
  console.log('[OK] Wallet admin lié en base →', adminAddress);
} catch (error) {
  console.warn('[WARN] Impossible de mettre à jour le wallet admin en base.');
  console.warn('       Faites-le manuellement : wallet_address =', adminAddress);
}
