<?php

namespace App\Services\Blockchain;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Web3\Contract;
use Web3\Providers\HttpProvider;
use Web3\Web3;
use Web3p\EthereumTx\Transaction;

class EthereumService
{
    protected StubEthereumService $stub;
    protected ?Web3 $web3 = null;
    protected ?Contract $contract = null;
    protected ?Client $http = null;
    protected bool $live = false;
    protected ?string $bootstrapError = null;
    protected string $adminAddress;
    protected string $garageAddress;
    protected string $contractAddress;
    protected string $rpcUrl;

    public function __construct(StubEthereumService $stub)
    {
        $this->stub = $stub;
        $this->adminAddress = (string) config('blockchain.admin_address');
        $this->garageAddress = (string) config('blockchain.garage_address');
        $this->contractAddress = (string) config('blockchain.contract_address');
        $this->rpcUrl = trim((string) config('blockchain.rpc_url'));
        $this->bootstrap();
    }

    public function isLive(): bool
    {
        if (!$this->live) {
            $this->bootstrap();
        }

        return $this->live;
    }

    public function getStatus(): array
    {
        if (!$this->live) {
            $this->bootstrap();
        }

        $rpc = $this->rpcUrl;
        $rpcPublic = preg_replace('#(/v2/)[^/?\s]+#', '$1***', $rpc);
        $abiPath = (string) config('blockchain.abi_path');

        return [
            'live' => $this->live,
            'network' => config('blockchain.network'),
            'rpc_url' => $rpcPublic,
            'contract_address' => $this->contractAddress ?: null,
            'chain_id' => config('blockchain.chain_id'),
            'admin_address' => $this->adminAddress ?: null,
            'explorer_tx_url' => config('blockchain.explorer_tx_url') ?: null,
            'abi_loaded' => $abiPath !== '' && is_file($abiPath),
            'error' => $this->bootstrapError,
            'deploy_marker' => 'sepolia-v2',
        ];
    }

    protected function bootstrap(): void
    {
        if ($this->rpcUrl === '') {
            $this->bootstrapError = 'BLOCKCHAIN_RPC_URL manquant';
            return;
        }

        // Corriger un collage du type "BLOCKCHAIN_RPC_URL=https://..."
        if (str_contains($this->rpcUrl, '=')) {
            $this->rpcUrl = trim((string) preg_replace('/^[A-Z0-9_]+=/i', '', $this->rpcUrl));
        }

        try {
            $this->http = new Client(['timeout' => 45, 'connect_timeout' => 15]);
            $response = $this->http->post($this->rpcUrl, [
                'json' => [
                    'jsonrpc' => '2.0',
                    'method' => 'eth_blockNumber',
                    'params' => [],
                    'id' => 1,
                ],
            ]);

            $payload = json_decode((string) $response->getBody(), true);
            if ($response->getStatusCode() !== 200 || empty($payload['result'])) {
                $this->bootstrapError = 'RPC Sepolia sans réponse valide (HTTP ' . $response->getStatusCode() . ')';
                return;
            }

            // RPC OK → considéré live pour l'UI
            $this->live = true;

            if ($this->contractAddress === '') {
                $this->bootstrapError = 'BLOCKCHAIN_CONTRACT_ADDRESS manquant';
                return;
            }

            try {
                $abi = $this->loadAbi();
                $this->web3 = new Web3(new HttpProvider($this->rpcUrl, 30));
                $this->contract = new Contract($this->web3->provider, $abi);
                $this->contract->at($this->contractAddress);
                $this->bootstrapError = null;
            } catch (\Throwable $contractError) {
                // RPC OK mais contrat/ABI KO — l'UI reste "connecté", les writes iront en stub
                $this->bootstrapError = 'RPC OK, contrat: ' . $contractError->getMessage();
                Log::warning($this->bootstrapError);
            }
        } catch (\Throwable $e) {
            $this->live = false;
            $this->bootstrapError = $e->getMessage();
            Log::warning('Blockchain indisponible, mode stub actif: ' . $e->getMessage(), [
                'contract' => $this->contractAddress,
                'abi_path' => config('blockchain.abi_path'),
            ]);
        }
    }

    protected function loadAbi(): array
    {
        $path = (string) config('blockchain.abi_path');
        if ($path === '' || !is_file($path)) {
            throw new \RuntimeException('ABI VehicleRegistry introuvable: ' . ($path ?: '(vide)'));
        }

        $json = json_decode((string) file_get_contents($path), true);
        if (!isset($json['abi']) || !is_array($json['abi'])) {
            throw new \RuntimeException('ABI invalide dans ' . $path);
        }

        return $json['abi'];
    }

    protected function sendTx(string $from, string $method, array $params = []): string
    {
        $privateKey = $this->privateKeyForAddress($from);
        $isLocal = ((int) config('blockchain.chain_id') === 1337)
            || config('blockchain.network') === 'local';

        if ($privateKey) {
            return $this->sendSignedTx($from, $privateKey, $method, $params);
        }

        if (!$isLocal) {
            throw new \RuntimeException(
                'Clé privée manquante pour signer la transaction Sepolia (adresse ' . $from . ')'
            );
        }

        $txHash = null;
        $error = null;

        $callback = function ($err, $result) use (&$txHash, &$error) {
            if ($err !== null) {
                $error = is_object($err) ? $err->getMessage() : (string) $err;
                return;
            }
            $txHash = $result;
        };

        $options = [
            'from' => $from,
            'gas' => (int) config('blockchain.gas_limit', 600000),
        ];

        call_user_func_array(
            [$this->contract, 'send'],
            array_merge([$method], $params, [$options, $callback])
        );

        if ($error) {
            throw new \RuntimeException($error);
        }
        if (!$txHash) {
            throw new \RuntimeException('Transaction blockchain sans hash retourné');
        }

        return $txHash;
    }

    protected function privateKeyForAddress(string $from): ?string
    {
        $from = strtolower($from);
        $adminKey = $this->normalizePrivateKey($this->adminPrivateKey());
        $garageKey = $this->normalizePrivateKey($this->garagePrivateKey());

        if ($adminKey && strtolower($this->adminAddress) === $from) {
            return $adminKey;
        }

        if ($garageKey && strtolower($this->garageAddress) === $from) {
            return $garageKey;
        }

        // Sur Sepolia, si clé garage absente, réutiliser la clé admin pour l'adresse garage
        if ($adminKey && strtolower($this->garageAddress) === $from) {
            return $adminKey;
        }

        return null;
    }

    protected function normalizePrivateKey(string $key): string
    {
        $key = trim($key);
        if ($key === '') {
            return '';
        }
        if (str_starts_with($key, '0x') || str_starts_with($key, '0X')) {
            $key = substr($key, 2);
        }
        return $key;
    }

    protected function rpc(string $method, array $params = [])
    {
        if (!$this->http) {
            throw new \RuntimeException('Client HTTP blockchain indisponible');
        }

        $response = $this->http->post($this->rpcUrl, [
            'json' => [
                'jsonrpc' => '2.0',
                'method' => $method,
                'params' => $params,
                'id' => 1,
            ],
        ]);

        $body = json_decode($response->getBody()->getContents(), true);
        if (isset($body['error'])) {
            $message = is_array($body['error'])
                ? ($body['error']['message'] ?? json_encode($body['error']))
                : (string) $body['error'];
            throw new \RuntimeException('RPC ' . $method . ': ' . $message);
        }

        return $body['result'] ?? null;
    }

    protected function sendSignedTx(string $from, string $privateKey, string $method, array $params): string
    {
        $data = $this->contract->getData($method, ...$params);
        if (!$data) {
            throw new \RuntimeException('Impossible d\'encoder l\'appel ' . $method);
        }
        if (!str_starts_with($data, '0x')) {
            $data = '0x' . $data;
        }

        $nonce = $this->rpc('eth_getTransactionCount', [$from, 'pending']);
        $gasPrice = $this->rpc('eth_gasPrice', []);
        $chainId = (int) config('blockchain.chain_id', 11155111);
        $gasLimit = '0x' . dechex((int) config('blockchain.gas_limit', 600000));

        // Plafonner le gasPrice pour rester dans un petit solde Sepolia
        $gasPriceInt = hexdec(is_string($gasPrice) ? $gasPrice : '0x0');
        $maxGasPrice = 5_000_000_000; // 5 gwei
        if ($gasPriceInt > $maxGasPrice || $gasPriceInt === 0) {
            $gasPrice = '0x' . dechex($maxGasPrice);
        }

        $transaction = new Transaction([
            'nonce' => $nonce,
            'from' => $from,
            'to' => $this->contractAddress,
            'gas' => $gasLimit,
            'gasPrice' => $gasPrice,
            'value' => '0x0',
            'data' => $data,
            'chainId' => $chainId,
        ]);

        $signed = '0x' . $transaction->sign($privateKey);
        $txHash = $this->rpc('eth_sendRawTransaction', [$signed]);

        if (!$txHash) {
            throw new \RuntimeException('eth_sendRawTransaction n\'a pas retourné de hash');
        }

        return $txHash;
    }

    protected function waitForReceipt(string $txHash, int $maxAttempts = 60): array
    {
        if (!$this->http) {
            throw new \RuntimeException('Client HTTP blockchain indisponible');
        }

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            usleep(1000000);
            $result = $this->rpc('eth_getTransactionReceipt', [$txHash]);
            if (!empty($result)) {
                if (($result['status'] ?? '0x1') === '0x0') {
                    throw new \RuntimeException('Transaction blockchain échouée (revert): ' . $txHash);
                }
                return $result;
            }
        }

        throw new \RuntimeException('Transaction non confirmée: ' . $txHash);
    }

    protected function adminPrivateKey(): string
    {
        return (string) config('blockchain.admin_private_key', env('ADMIN_PRIVATE_KEY', ''));
    }

    protected function garagePrivateKey(): string
    {
        return (string) config('blockchain.garage_private_key', env('GARAGE_PRIVATE_KEY', ''));
    }

    protected function callContract(string $method, array $params = [])
    {
        $result = null;
        $error = null;

        $callback = function ($err, $data) use (&$result, &$error) {
            if ($err !== null) {
                $error = is_object($err) ? $err->getMessage() : (string) $err;
                return;
            }
            $result = $data;
        };

        call_user_func_array(
            [$this->contract, 'call'],
            array_merge([$method], $params, [$callback])
        );

        if ($error) {
            throw new \RuntimeException($error);
        }

        return $result;
    }

    protected function adminFrom(string $privateKey): string
    {
        if ($this->adminAddress) {
            return $this->adminAddress;
        }

        return $this->garageAddress ?: '0x0000000000000000000000000000000000000001';
    }

    protected function garageFrom(string $privateKey): string
    {
        if ($this->garageAddress) {
            return $this->garageAddress;
        }

        return $this->adminFrom($privateKey);
    }

    public function isGarageAuthorized(string $address): bool
    {
        if (!$this->live) {
            return true;
        }

        try {
            $result = $this->callContract('authorizedGarages', [$address]);
            return filter_var($result, FILTER_VALIDATE_BOOLEAN);
        } catch (\Throwable $e) {
            Log::warning('isGarageAuthorized: ' . $e->getMessage());
            return false;
        }
    }

    public function ensureGarageAuthorized(): ?array
    {
        if (!$this->live || !$this->garageAddress) {
            return null;
        }

        if ($this->isGarageAuthorized($this->garageAddress)) {
            return null;
        }

        return $this->authorizeGarage($this->garageAddress, true, $this->adminPrivateKey());
    }

    public function registerVehicle(string $vin, int $initialMileage, string $privateKey): array
    {
        $initialMileage = max(0, (int) $initialMileage);

        if (!$this->live) {
            return $this->stub->registerVehicle($vin, $initialMileage, $privateKey);
        }

        $txHash = $this->sendTx($this->adminFrom($privateKey), 'registerVehicle', [$vin, $initialMileage]);
        $this->waitForReceipt($txHash);
        $vehicleId = $this->getVehicleCount();

        return [
            'vehicleId' => $vehicleId,
            'vin' => $vin,
            'initialMileage' => $initialMileage,
            'transactionHash' => $txHash,
            'status' => 'registered',
            'live' => true,
        ];
    }

    public function updateMileage(int $vehicleId, int $newMileage, string $privateKey): array
    {
        $newMileage = max(0, (int) $newMileage);

        if (!$this->live) {
            return $this->stub->updateMileage($vehicleId, $newMileage, $privateKey);
        }

        $txHash = $this->sendTx($this->adminFrom($privateKey), 'updateMileage', [$vehicleId, $newMileage]);
        $this->waitForReceipt($txHash);

        return [
            'vehicleId' => $vehicleId,
            'newMileage' => $newMileage,
            'transactionHash' => $txHash,
            'status' => 'updated',
            'live' => true,
        ];
    }

    public function recordMaintenance(int $vehicleId, string $description, string $partsChanged, string $privateKey): array
    {
        if (!$this->live) {
            return $this->stub->recordMaintenance($vehicleId, $description, $partsChanged, $privateKey);
        }

        $this->ensureGarageAuthorized();

        $txHash = $this->sendTx(
            $this->garageFrom($privateKey),
            'recordMaintenance',
            [$vehicleId, $description, $partsChanged]
        );
        $this->waitForReceipt($txHash);

        return [
            'vehicleId' => $vehicleId,
            'description' => $description,
            'partsChanged' => $partsChanged,
            'transactionHash' => $txHash,
            'status' => 'recorded',
            'live' => true,
        ];
    }

    public function authorizeGarage(string $address, bool $status, string $privateKey): array
    {
        if (!$this->live) {
            return $this->stub->authorizeGarage($address, $status, $privateKey);
        }

        $txHash = $this->sendTx($this->adminFrom($privateKey), 'authorizeGarage', [$address, $status]);
        $this->waitForReceipt($txHash);

        return [
            'address' => $address,
            'status' => $status,
            'transactionHash' => $txHash,
            'status_label' => 'authorized',
            'live' => true,
        ];
    }

    public function getVehicleData(?int $vehicleId): ?array
    {
        if (!$vehicleId) {
            return null;
        }

        if (!$this->live) {
            return $this->stub->getVehicleData($vehicleId);
        }

        try {
            $result = $this->callContract('getVehicle', [$vehicleId]);
            if (!$result) {
                return null;
            }

            return [
                'vin' => $result[0] ?? '',
                'mileage' => isset($result[1]) ? (string) $result[1] : '0',
                'lastUpdate' => isset($result[2]) ? (string) $result[2] : '0',
                'isActive' => (bool) ($result[3] ?? false),
            ];
        } catch (\Throwable $e) {
            Log::error('getVehicleData blockchain: ' . $e->getMessage());
            return $this->stub->getVehicleData($vehicleId);
        }
    }

    public function getMaintenanceHistory(?int $vehicleId): array
    {
        if (!$vehicleId || !$this->live) {
            return $this->stub->getMaintenanceHistory($vehicleId);
        }

        try {
            $result = $this->callContract('getMaintenanceHistory', [$vehicleId]);
            if (!$result) {
                return [];
            }

            return array_map(function ($item) {
                return [
                    'vehicleId' => isset($item['vehicleId']) ? (string) $item['vehicleId'] : '',
                    'description' => $item['description'] ?? '',
                    'timestamp' => isset($item['timestamp']) ? (string) $item['timestamp'] : '',
                    'mechanic' => $item['mechanic'] ?? '',
                    'partsChanged' => $item['partsChanged'] ?? '',
                ];
            }, $result);
        } catch (\Throwable $e) {
            Log::error('getMaintenanceHistory blockchain: ' . $e->getMessage());
            return [];
        }
    }

    public function getMileageHistory(?int $vehicleId): array
    {
        if (!$vehicleId || !$this->live) {
            return $this->stub->getMileageHistory($vehicleId);
        }

        try {
            $result = $this->callContract('getMileageHistory', [$vehicleId]);
            if (!$result) {
                return [];
            }

            return array_map(function ($item) {
                return [
                    'vehicleId' => isset($item['vehicleId']) ? (string) $item['vehicleId'] : '',
                    'mileage' => isset($item['mileage']) ? (string) $item['mileage'] : '',
                    'timestamp' => isset($item['timestamp']) ? (string) $item['timestamp'] : '',
                    'recorder' => $item['recorder'] ?? '',
                ];
            }, $result);
        } catch (\Throwable $e) {
            Log::error('getMileageHistory blockchain: ' . $e->getMessage());
            return [];
        }
    }

    public function getVehicleCount(): int
    {
        if (!$this->live) {
            return 0;
        }

        try {
            $result = $this->callContract('getVehicleCount');
            return $result ? (int) (string) $result : 0;
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
