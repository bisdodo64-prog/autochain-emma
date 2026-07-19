<?php

namespace App\Services\Blockchain;

use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class IPFSService
{
    protected $client;
    protected $gateway;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => config('ipfs.api_url'),
            'timeout' => 30
        ]);
        $this->gateway = config('ipfs.gateway_url');
    }

    public function upload($filePath)
    {
        try {
            $content = Storage::disk('private')->get($filePath);
            
            $response = $this->client->post('/api/v0/add', [
                'multipart' => [
                    [
                        'name' => 'file',
                        'contents' => $content,
                        'filename' => basename($filePath)
                    ]
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            return $data['Hash'] ?? null;
        } catch (\Exception $e) {
            Log::error("IPFS upload error: " . $e->getMessage());
            throw $e;
        }
    }

    public function verify($hash, $filePath)
    {
        try {
            $localContent = Storage::disk('private')->get($filePath);
            $localHash = hash('sha256', $localContent);

            // Get from IPFS
            $response = $this->client->get('/api/v0/cat?arg=' . $hash);
            $ipfsContent = $response->getBody()->getContents();
            $ipfsHash = hash('sha256', $ipfsContent);

            return $localHash === $ipfsHash;
        } catch (\Exception $e) {
            Log::error("IPFS verify error: " . $e->getMessage());
            return false;
        }
    }

    public function getUrl($hash)
    {
        return $this->gateway . '/ipfs/' . $hash;
    }
}