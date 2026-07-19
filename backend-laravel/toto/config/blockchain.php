<?php

$abiPath = env('BLOCKCHAIN_ABI_PATH');
if (!$abiPath) {
    $abiPath = realpath(base_path('../../blockchain/build/contracts/VehicleRegistry.json')) ?: null;
}

return [
    'rpc_url' => env('BLOCKCHAIN_RPC_URL', env('WEB3_RPC_URL', 'http://127.0.0.1:8545')),
    'contract_address' => env('BLOCKCHAIN_CONTRACT_ADDRESS', env('CONTRACT_ADDRESS')),
    'abi_path' => $abiPath,
    'chain_id' => (int) env('BLOCKCHAIN_CHAIN_ID', 1337),
    'network' => env('BLOCKCHAIN_NETWORK', 'local'),
    'gas_limit' => (int) env('BLOCKCHAIN_GAS_LIMIT', 600000),
    'gas_price' => env('BLOCKCHAIN_GAS_PRICE', '20000000000'),
    'admin_address' => env('BLOCKCHAIN_ADMIN_ADDRESS', env('ADMIN_WALLET_ADDRESS')),
    'garage_address' => env('BLOCKCHAIN_GARAGE_ADDRESS', env('GARAGE_WALLET_ADDRESS')),
    'admin_private_key' => env('ADMIN_PRIVATE_KEY'),
    'garage_private_key' => env('GARAGE_PRIVATE_KEY'),
    'explorer_tx_url' => env('BLOCKCHAIN_EXPLORER_TX_URL', ''),
];
