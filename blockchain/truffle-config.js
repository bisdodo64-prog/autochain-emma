require('dotenv').config();

const HDWalletProvider = require('@truffle/hdwallet-provider');

/** Truffle HDWalletProvider attend 64 hex sans prefixe 0x */
function normalizePrivateKeyForTruffle(key) {
  if (!key) return '';
  let normalized = String(key).trim();
  if (normalized.startsWith('0x') || normalized.startsWith('0X')) {
    normalized = normalized.slice(2);
  }
  return normalized;
}

function sepoliaProvider() {
  const privateKey = normalizePrivateKeyForTruffle(process.env.PRIVATE_KEY);
  const rpcUrl = process.env.SEPOLIA_RPC_URL
    || 'https://ethereum-sepolia-rpc.publicnode.com';

  if (!privateKey || privateKey.length !== 64) {
    throw new Error('PRIVATE_KEY invalide dans blockchain/.env (64 caracteres hex attendus)');
  }

  return new HDWalletProvider({
    privateKeys: [privateKey],
    providerOrUrl: rpcUrl,
    pollingInterval: 8000,
  });
}

module.exports = {
  networks: {
    development: {
      host: '127.0.0.1',
      port: 8545,
      network_id: '*',
    },
    sepolia: {
      provider: sepoliaProvider,
      network_id: 11155111,
      // Limite haute pour estimation; le cout reel sera bien plus bas
      gas: 3500000,
      // ~2 gwei : 3.5M * 2 gwei ≈ 0.007 ETH (compatible avec 0.05 ETH)
      gasPrice: 2000000000,
      confirmations: 2,
      timeoutBlocks: 200,
      networkCheckTimeout: 120000,
      deploymentPollingInterval: 8000,
      skipDryRun: true,
    },
    goerli: {
      provider: () => new HDWalletProvider(
        normalizePrivateKeyForTruffle(process.env.PRIVATE_KEY),
        process.env.GOERLI_RPC_URL || 'https://goerli.infura.io/v3/YOUR_INFURA_KEY'
      ),
      network_id: 5,
      gas: 5500000,
      confirmations: 2,
      timeoutBlocks: 200,
      skipDryRun: true,
    },
  },

  compilers: {
    solc: {
      version: '0.8.19',
      settings: {
        optimizer: {
          enabled: true,
          runs: 200,
        },
      },
    },
  },

  plugins: [
    'truffle-plugin-verify',
  ],

  api_keys: {
    etherscan: process.env.ETHERSCAN_API_KEY,
  },
};
