<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Elliptic\EC;
use kornrunner\Keccak;

class VerifyWeb3Signature
{
    public function handle(Request $request, Closure $next)
    {
        $signature = $request->header('X-Web3-Signature');
        $address = $request->header('X-Web3-Address');
        $message = $request->header('X-Web3-Message');

        if (!$signature || !$address || !$message) {
            return response()->json(['error' => 'Missing Web3 authentication headers'], 401);
        }

        if (!$this->verifySignature($message, $signature, $address)) {
            return response()->json(['error' => 'Invalid Web3 signature'], 403);
        }

        return $next($request);
    }

    protected function verifySignature($message, $signature, $address)
    {
        try {
            $hash = Keccak::hash("\x19Ethereum Signed Message:\n" . strlen($message) . $message, 256);
            $ec = new EC('secp256k1');
            $sign = [
                'r' => substr($signature, 2, 64),
                's' => substr($signature, 66, 64)
            ];
            $v = hexdec(substr($signature, 130, 2));
            if ($v >= 27) $v -= 27;

            $pubKey = $ec->recoverPubKey($hash, $sign, $v);
            $recoveredAddress = '0x' . substr(Keccak::hash(substr(hex($pubKey->encode()), 2), 256), 24);

            return strtolower($recoveredAddress) === strtolower($address);
        } catch (\Exception $e) {
            return false;
        }
    }
}