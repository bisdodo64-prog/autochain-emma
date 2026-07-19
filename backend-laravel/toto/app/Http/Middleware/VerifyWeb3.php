<?php

namespace App\Http\Middleware;

use App\Services\Blockchain\Web3SignatureService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWeb3
{
    public function __construct(protected Web3SignatureService $web3Signature)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if ($request->filled('signature') && $request->filled('message') && $request->filled('wallet_address')) {
            $walletAddress = $request->input('wallet_address');

            if (!$this->web3Signature->verify(
                $request->input('message'),
                $request->input('signature'),
                $walletAddress
            )) {
                return response()->json([
                    'message' => 'Signature MetaMask invalide',
                ], 401);
            }

            if (!$user->wallet_address || strtolower($user->wallet_address) !== strtolower($walletAddress)) {
                $user->wallet_address = $walletAddress;
                $user->save();
            }

            return $next($request);
        }

        if (app()->environment('local', 'testing')) {
            return $next($request);
        }

        if (!$user->wallet_address) {
            return response()->json([
                'message' => 'Connexion MetaMask requise pour cette action',
            ], 403);
        }

        return $next($request);
    }
}
