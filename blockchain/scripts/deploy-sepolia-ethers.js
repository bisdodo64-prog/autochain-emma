/**
 * Deploiement Sepolia via ethers.js (plus fiable que Truffle HDWalletProvider).
 * Usage: node scripts/deploy-sepolia-ethers.js
 */
require('dotenv').config({ path: require('path').join(__dirname, '../.env') });

const fs = require('fs');
const path = require('path');
const { ethers } = require('ethers');
const {
  updateEnvFile,
  BACKEND_ENV,
  FRONTEND_ENV,
  SEPOLIA_PROFILE,
  PROFILES_DIR,
  ensureDir,
  deriveAddressFromPrivateKey,
  normalizePrivateKey,
} = require('./env-utils');

const RPC_CANDIDATES = [
  process.env.SEPOLIA_RPC_URL,
].filter((url) => url && !url.includes('YOUR_') && !url.includes('rpc2.sepolia') && !url.includes('1rpc.io'));

// Fallback publics seulement si Alchemy/Infura absent
const PUBLIC_FALLBACKS = [
  'https://ethereum-sepolia-rpc.publicnode.com',
  'https://rpc.ankr.com/eth_sepolia',
];

function loadArtifact() {
  const artifactPath = path.join(__dirname, '../build/contracts/VehicleRegistry.json');
  if (!fs.existsSync(artifactPath)) {
    throw new Error('Artifact introuvable. Lancez: npx truffle compile');
  }
  return require(artifactPath);
}

async function tryProvider(rpcUrl, privateKey) {
  const provider = new ethers.JsonRpcProvider(rpcUrl, 11155111);
  const network = await provider.getNetwork();
  if (Number(network.chainId) !== 11155111) {
    throw new Error(`Mauvaise chainId: ${network.chainId}`);
  }
  const wallet = new ethers.Wallet(privateKey, provider);
  const balance = await provider.getBalance(wallet.address);
  return { provider, wallet, balance, rpcUrl };
}

async function main() {
  const privateKey = normalizePrivateKey(process.env.PRIVATE_KEY || '');
  if (!privateKey || privateKey.length < 66) {
    throw new Error('PRIVATE_KEY manquante ou invalide dans blockchain/.env');
  }

  const candidates = RPC_CANDIDATES.length > 0
    ? RPC_CANDIDATES
    : PUBLIC_FALLBACKS;

  if (RPC_CANDIDATES.length === 0) {
    console.warn('[WARN] Pas d\'Alchemy/Infura dans blockchain/.env — RPC publics (instables)');
  } else {
    console.log('[OK] RPC configure:', candidates[0].replace(/\/v2\/.*/, '/v2/***'));
  }

  const artifact = loadArtifact();
  let lastError = null;

  for (const rpcUrl of [...new Set(candidates)]) {
    try {
      console.log('--- Tentative deploy via:', rpcUrl.includes('alchemy') || rpcUrl.includes('infura')
        ? rpcUrl.replace(/\/v2\/.*/, '/v2/***')
        : rpcUrl);

      const connected = await tryProvider(rpcUrl, privateKey);
      console.log('[OK] Compte:', connected.wallet.address);
      console.log('     Solde:', ethers.formatEther(connected.balance), 'ETH');

      if (connected.balance < ethers.parseEther('0.01')) {
        throw new Error('Solde trop faible (< 0.01 ETH)');
      }

      const factory = new ethers.ContractFactory(
        artifact.abi,
        artifact.bytecode,
        connected.wallet
      );

      console.log('Deploiement VehicleRegistry...');
      const feeData = await connected.provider.getFeeData();
      const maxFee = feeData.maxFeePerGas || feeData.gasPrice || ethers.parseUnits('2', 'gwei');
      const maxPriority = feeData.maxPriorityFeePerGas || ethers.parseUnits('1', 'gwei');

      const contract = await factory.deploy({
        maxFeePerGas: maxFee,
        maxPriorityFeePerGas: maxPriority,
      });

      console.log('Tx hash:', contract.deploymentTransaction()?.hash);
      await contract.waitForDeployment();
      const address = await contract.getAddress();

      console.log('');
      console.log('[OK] Contrat deploye:', address);
      console.log('Explorer: https://sepolia.etherscan.io/address/' + address);

      artifact.networks = artifact.networks || {};
      artifact.networks['11155111'] = {
        address,
        transactionHash: contract.deploymentTransaction()?.hash,
      };
      fs.writeFileSync(
        path.join(__dirname, '../build/contracts/VehicleRegistry.json'),
        JSON.stringify(artifact, null, 2)
      );

      const adminAddress = deriveAddressFromPrivateKey(privateKey);
      const backendUpdates = {
        BLOCKCHAIN_RPC_URL: connected.rpcUrl,
        BLOCKCHAIN_CONTRACT_ADDRESS: address,
        BLOCKCHAIN_CHAIN_ID: '11155111',
        BLOCKCHAIN_NETWORK: 'sepolia',
        BLOCKCHAIN_EXPLORER_TX_URL: 'https://sepolia.etherscan.io/tx/{hash}',
        BLOCKCHAIN_ADMIN_ADDRESS: adminAddress,
        ADMIN_PRIVATE_KEY: privateKey,
      };

      updateEnvFile(BACKEND_ENV, backendUpdates);
      updateEnvFile(FRONTEND_ENV, {
        VITE_APP_CONTRACT_ADDRESS: address,
        VITE_CHAIN_ID: '11155111',
      });
      updateEnvFile(path.join(__dirname, '../.env'), {
        SEPOLIA_RPC_URL: connected.rpcUrl,
      });

      ensureDir(PROFILES_DIR);
      fs.writeFileSync(SEPOLIA_PROFILE, JSON.stringify({
        saved_at: new Date().toISOString(),
        contract_address: address,
        admin_address: adminAddress,
        rpc_url: connected.rpcUrl,
        backend: backendUpdates,
        frontend: {
          VITE_APP_CONTRACT_ADDRESS: address,
          VITE_CHAIN_ID: '11155111',
        },
      }, null, 2));

      try {
        require('./update-admin-wallet.js');
      } catch (_) {}

      console.log('');
      console.log('=== Sepolia configure automatiquement ===');
      console.log('Redemarrez start-backend.bat et npm run dev');
      console.log('MetaMask sur Sepolia');
      return;
    } catch (error) {
      lastError = error;
      console.warn('[ECHEC]', error.message || error);
      console.log('');
    }
  }

  throw new Error(
    'Deploiement impossible. Verifiez Alchemy dans blockchain/.env\n' +
    'Derniere erreur: ' + (lastError?.message || 'inconnue')
  );
}

main().catch((error) => {
  console.error('[ERREUR]', error.message || error);
  process.exit(1);
});
