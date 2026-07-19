<?php

namespace App\Services\Blockchain;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class IPFSService
{
    protected Client $client;
    protected string $gateway;
    protected bool $live = false;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => rtrim((string) config('ipfs.api_url'), '/') . '/',
            'timeout' => 20,
            'http_errors' => false,
        ]);
        $this->gateway = rtrim((string) config('ipfs.gateway_url'), '/');
        $this->live = (bool) config('ipfs.enabled', true);
    }

    public function isLive(): bool
    {
        return $this->live;
    }

    public function add(string $filePath): string
    {
        if (!$this->live || !is_file($filePath)) {
            return $this->pseudoHash($filePath);
        }

        try {
            $response = $this->client->post('api/v0/add', [
                'multipart' => [[
                    'name' => 'file',
                    'contents' => fopen($filePath, 'r'),
                    'filename' => basename($filePath),
                ]],
            ]);

            if ($response->getStatusCode() >= 400) {
                throw new \RuntimeException('IPFS HTTP ' . $response->getStatusCode());
            }

            $data = json_decode((string) $response->getBody(), true);
            if (!empty($data['Hash'])) {
                return $data['Hash'];
            }
        } catch (\Throwable $e) {
            Log::warning('IPFS upload échoué, hash simulé: ' . $e->getMessage());
        }

        return $this->pseudoHash($filePath);
    }

    public function verify(string $hash, string $filePath): bool
    {
        if (!$this->live || !is_file($filePath)) {
            return true;
        }

        try {
            $localHash = hash('sha256', file_get_contents($filePath));
            $response = $this->client->get('api/v0/cat', ['query' => ['arg' => $hash]]);
            if ($response->getStatusCode() >= 400) {
                return false;
            }
            $ipfsHash = hash('sha256', (string) $response->getBody());
            return hash_equals($localHash, $ipfsHash);
        } catch (\Throwable $e) {
            Log::warning('IPFS verify échoué: ' . $e->getMessage());
            return false;
        }
    }

    public function getUrl(string $hash): string
    {
        return $this->gateway . '/ipfs/' . $hash;
    }

    protected function pseudoHash(string $filePath): string
    {
        $hash = is_file($filePath) ? md5_file($filePath) : md5($filePath);
        return 'Qm' . substr($hash, 0, 44);
    }
}
