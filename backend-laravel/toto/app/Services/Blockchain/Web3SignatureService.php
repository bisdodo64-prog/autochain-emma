<?php

namespace App\Services\Blockchain;

use Elliptic\EC;
use kornrunner\Keccak;

class Web3SignatureService
{
    public function verify(string $message, string $signature, string $expectedAddress): bool
    {
        $recovered = $this->recoverAddress($message, $signature);

        return $recovered !== null
            && strtolower($recovered) === strtolower($expectedAddress);
    }

    public function recoverAddress(string $message, string $signature): ?string
    {
        $signature = trim($signature);
        if (!str_starts_with($signature, '0x')) {
            $signature = '0x' . $signature;
        }

        if (strlen($signature) !== 132) {
            return null;
        }

        $msgLen = strlen($message);
        $hash = Keccak::hash("\x19Ethereum Signed Message:\n{$msgLen}{$message}", 256);

        $sign = [
            'r' => substr($signature, 2, 64),
            's' => substr($signature, 66, 64),
        ];

        $vByte = hexdec(substr($signature, 130, 2));
        $recid = $vByte >= 27 ? $vByte - 27 : $vByte;

        $ec = new EC('secp256k1');

        foreach ([$recid, $recid ^ 1] as $candidate) {
            if ($candidate < 0 || $candidate > 3) {
                continue;
            }

            try {
                $pubKey = $ec->recoverPubKey($hash, $sign, $candidate);
                $address = $this->publicKeyToAddress($pubKey);

                if ($address) {
                    return $address;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    protected function publicKeyToAddress($pubKey): ?string
    {
        $encoded = $pubKey->encode('hex');
        if (!is_string($encoded) || strlen($encoded) < 2) {
            return null;
        }

        $pubKeyHex = substr($encoded, 2);

        return '0x' . substr(Keccak::hash(hex2bin($pubKeyHex), 256), 24);
    }
}
