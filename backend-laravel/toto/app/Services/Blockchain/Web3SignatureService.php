<?php

namespace App\Services\Blockchain;

use Elliptic\EC;
use kornrunner\Keccak;

class Web3SignatureService
{
    public function verify(string $message, string $signature, string $expectedAddress): bool
    {
        $expected = strtolower(trim($expectedAddress));
        if ($expected === '' || !str_starts_with($expected, '0x')) {
            return false;
        }

        foreach ($this->messageCandidates($message) as $candidate) {
            $recovered = $this->recoverAddress($candidate, $signature);
            if ($recovered !== null && strtolower($recovered) === $expected) {
                return true;
            }
        }

        return false;
    }

    /**
     * MetaMask mobile peut normaliser les fins de ligne / espaces.
     *
     * @return list<string>
     */
    protected function messageCandidates(string $message): array
    {
        $normalized = str_replace("\r\n", "\n", $message);
        $candidates = [
            $message,
            $normalized,
            trim($normalized),
            str_replace("\n", "\r\n", $normalized),
        ];

        return array_values(array_unique(array_filter($candidates, fn ($m) => $m !== '')));
    }

    public function recoverAddress(string $message, string $signature): ?string
    {
        $signature = strtolower(trim($signature));
        if (!str_starts_with($signature, '0x')) {
            $signature = '0x' . $signature;
        }

        // EIP-2098 compact (64 bytes) → expand to 65 bytes
        if (strlen($signature) === 130) {
            $signature = $this->expandCompactSignature($signature);
        }

        if (strlen($signature) !== 132) {
            return null;
        }

        $msgLen = strlen($message);
        $prefixed = "\x19Ethereum Signed Message:\n{$msgLen}{$message}";
        $hash = Keccak::hash($prefixed, 256);

        $sign = [
            'r' => substr($signature, 2, 64),
            's' => substr($signature, 66, 64),
        ];

        $vByte = hexdec(substr($signature, 130, 2));
        $recid = $vByte >= 27 ? $vByte - 27 : $vByte;

        $ec = new EC('secp256k1');

        foreach ([$recid, $recid ^ 1, 0, 1] as $candidate) {
            $candidate = (int) $candidate;
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

    protected function expandCompactSignature(string $compact): string
    {
        // 0x + r(64) + yParityAndS(64)
        $r = substr($compact, 2, 64);
        $ys = substr($compact, 66, 64);
        $ysBin = hex2bin($ys);
        if ($ysBin === false || strlen($ysBin) !== 32) {
            return $compact;
        }

        $first = ord($ysBin[0]);
        $yParity = ($first & 0x80) ? 1 : 0;
        $ysBin[0] = chr($first & 0x7f);
        $s = bin2hex($ysBin);
        $v = dechex(27 + $yParity);
        if (strlen($v) === 1) {
            $v = '0' . $v;
        }

        return '0x' . $r . $s . $v;
    }

    protected function publicKeyToAddress($pubKey): ?string
    {
        // Force uncompressed public key (04 || x || y)
        $encoded = null;
        if (is_object($pubKey) && method_exists($pubKey, 'encode')) {
            try {
                $encoded = $pubKey->encode('hex', false);
            } catch (\Throwable) {
                $encoded = $pubKey->encode('hex');
            }
        }

        if (!is_string($encoded) || strlen($encoded) < 128) {
            return null;
        }

        if (str_starts_with($encoded, '04')) {
            $pubKeyHex = substr($encoded, 2);
        } else {
            $pubKeyHex = $encoded;
        }

        if (strlen($pubKeyHex) % 2 === 1) {
            return null;
        }

        $binary = @hex2bin($pubKeyHex);
        if ($binary === false) {
            return null;
        }

        return '0x' . substr(Keccak::hash($binary, 256), 24);
    }
}
