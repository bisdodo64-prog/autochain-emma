import { ethers } from 'ethers';

const CONTRACT_ADDRESS = import.meta.env.VITE_APP_CONTRACT_ADDRESS;
const CHAIN_ID = Number(import.meta.env.VITE_CHAIN_ID || 1337);

const VEHICLE_REGISTRY_ABI = [
  'function registerVehicle(string _vin, uint256 _initialMileage) public',
  'function updateMileage(uint256 _vehicleId, uint256 _newMileage) public',
  'function recordMaintenance(uint256 _vehicleId, string _description, string _partsChanged) public',
  'function authorizeGarage(address _garage, bool _status) public',
  'function getVehicle(uint256 _vehicleId) public view returns (string vin, uint256 mileage, uint256 lastUpdate, bool isActive)',
  'function getMaintenanceHistory(uint256 _vehicleId) public view returns (tuple(uint256 vehicleId, string description, uint256 timestamp, address mechanic, string partsChanged)[] memory)',
  'function getMileageHistory(uint256 _vehicleId) public view returns (tuple(uint256 vehicleId, uint256 mileage, uint256 timestamp, address recorder)[] memory)',
  'function getVehicleCount() public view returns (uint256)',
  'event VehicleRegistered(uint256 indexed id, string vin, address registrant)',
  'event MileageUpdated(uint256 indexed vehicleId, uint256 newMileage, address recorder)',
  'event MaintenanceRecorded(uint256 indexed vehicleId, string description, address mechanic)'
];

class Web3Service {
  constructor() {
    this.provider = null;
    this.signer = null;
    this.contract = null;
    this.readContract = null;
    this.account = null;
    this.chainId = null;
    this.mock = false;
  }

  isMetaMaskAvailable() {
    return typeof window !== 'undefined' && typeof window.ethereum !== 'undefined';
  }

  hasContract() {
    return Boolean(CONTRACT_ADDRESS);
  }

  async init() {
    if (!this.isMetaMaskAvailable()) {
      this.mock = true;
      this.account = '0x' + '0'.repeat(40);
      this.chainId = CHAIN_ID;
      return this.account;
    }

    this.provider = new ethers.BrowserProvider(window.ethereum);
    await this.provider.send('eth_requestAccounts', []);
    this.signer = await this.provider.getSigner();
    this.account = await this.signer.getAddress();
    const network = await this.provider.getNetwork();
    this.chainId = Number(network.chainId);

    if (CONTRACT_ADDRESS) {
      this.contract = new ethers.Contract(CONTRACT_ADDRESS, VEHICLE_REGISTRY_ABI, this.signer);
      this.readContract = this.contract;
    }

    window.ethereum.on('accountsChanged', async (accounts) => {
      if (!accounts.length) {
        this.account = null;
        this.signer = null;
        this.contract = null;
        return;
      }
      this.account = accounts[0];
      this.signer = await this.provider.getSigner();
      if (CONTRACT_ADDRESS) {
        this.contract = new ethers.Contract(CONTRACT_ADDRESS, VEHICLE_REGISTRY_ABI, this.signer);
        this.readContract = this.contract;
      }
    });

    window.ethereum.on('chainChanged', () => {
      window.location.reload();
    });

    await this.ensureLocalNetwork();

    return this.account;
  }

  async ensureLocalNetwork() {
    if (this.mock || !window.ethereum) {
      return;
    }

    const targetHex = '0x' + CHAIN_ID.toString(16);
    const currentHex = await window.ethereum.request({ method: 'eth_chainId' });
    if (currentHex?.toLowerCase() === targetHex.toLowerCase()) {
      this.chainId = CHAIN_ID;
      return;
    }

    const networkLabel = CHAIN_ID === 11155111 ? 'Sepolia' : 'Ganache local (1337)';

    try {
      await window.ethereum.request({
        method: 'wallet_switchEthereumChain',
        params: [{ chainId: targetHex }]
      });
    } catch (error) {
      if (error?.code !== 4902) {
        throw new Error(`Basculez MetaMask sur le réseau ${networkLabel}`);
      }

      const addChainParams = CHAIN_ID === 11155111
        ? {
            chainId: targetHex,
            chainName: 'Sepolia',
            rpcUrls: ['https://rpc.sepolia.org'],
            nativeCurrency: { name: 'Sepolia ETH', symbol: 'ETH', decimals: 18 },
            blockExplorerUrls: ['https://sepolia.etherscan.io']
          }
        : {
            chainId: targetHex,
            chainName: 'Ganache AutoChain',
            rpcUrls: ['http://127.0.0.1:8545'],
            nativeCurrency: { name: 'Ether', symbol: 'ETH', decimals: 18 }
          };

      await window.ethereum.request({
        method: 'wallet_addEthereumChain',
        params: [addChainParams]
      });
    }

    this.chainId = CHAIN_ID;
  }

  async signMessage(message) {
    if (this.mock) {
      return '0x' + 'mock'.repeat(16);
    }
    if (!this.signer) {
      throw new Error('Wallet non connecté');
    }
    return this.signer.signMessage(message);
  }

  getContract() {
    if (!this.contract) {
      throw new Error('Contrat non initialisé. Vérifiez VITE_APP_CONTRACT_ADDRESS');
    }
    return this.contract;
  }

  async registerVehicle(vin, initialMileage) {
    const contract = this.getContract();
    const tx = await contract.registerVehicle(vin, initialMileage);
    const receipt = await tx.wait();
    return receipt;
  }

  async updateMileage(vehicleId, newMileage) {
    const contract = this.getContract();
    const tx = await contract.updateMileage(vehicleId, newMileage);
    return tx.wait();
  }

  async recordMaintenance(vehicleId, description, partsChanged) {
    const contract = this.getContract();
    const tx = await contract.recordMaintenance(vehicleId, description, partsChanged);
    return tx.wait();
  }

  async authorizeGarage(garageAddress, status) {
    const contract = this.getContract();
    const tx = await contract.authorizeGarage(garageAddress, status);
    return tx.wait();
  }

  async getVehicle(vehicleId) {
    const contract = this.readContract || this.getContract();
    const vehicle = await contract.getVehicle(vehicleId);
    return {
      vin: vehicle.vin ?? vehicle[0],
      mileage: vehicle.mileage ?? vehicle[1],
      lastUpdate: vehicle.lastUpdate ?? vehicle[2],
      isActive: vehicle.isActive ?? vehicle[3]
    };
  }

  async getVehicleCount() {
    const contract = this.readContract || this.getContract();
    return contract.getVehicleCount();
  }

  disconnect() {
    this.provider = null;
    this.signer = null;
    this.contract = null;
    this.readContract = null;
    this.account = null;
    this.chainId = null;
    this.mock = false;
  }
}

const web3Service = new Web3Service();

export default web3Service;
