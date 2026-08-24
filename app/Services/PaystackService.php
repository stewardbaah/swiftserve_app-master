<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackService
{
    /**
     * The secret key for Paystack.
     */
    protected string $secretKey;

    /**
     * Create a new Paystack service instance.
     */
    public function __construct()
    {
        $this->secretKey = config('services.paystack.secret_key') ?? '';
    }

    /**
     * Initialize a transaction on Paystack.
     *
     * @param  array{order_id?: int, description?: string}  $metadata
     */
    public function initializeTransaction(
        string $email,
        int $amountInCents,
        string $reference,
        string $callbackUrl,
        array $metadata = []
    ): ?string {
        if (empty($this->secretKey)) {
            Log::error('Paystack Secret Key is not configured.');

            return null;
        }

        try {
            $response = Http::withToken($this->secretKey)
                ->timeout(10)
                ->connectTimeout(5)
                ->post('https://api.paystack.co/transaction/initialize', [
                    'email' => $email,
                    'amount' => $amountInCents,
                    'reference' => $reference,
                    'callback_url' => $callbackUrl,
                    'metadata' => $metadata,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (($data['status'] ?? false) === true) {
                    return $data['data']['authorization_url'] ?? null;
                }
            }

            Log::error('Paystack initialization failed: '.$response->body());
        } catch (\Exception $e) {
            Log::error('Paystack initialization exception: '.$e->getMessage());
        }

        return null;
    }

    /**
     * Verify a transaction on Paystack.
     *
     * @return array{status: string, amount: int, reference: string, metadata?: array{order_id?: int}}|null
     */
    public function verifyTransaction(string $reference): ?array
    {
        if (empty($this->secretKey)) {
            Log::error('Paystack Secret Key is not configured.');

            return null;
        }

        try {
            $response = Http::withToken($this->secretKey)
                ->timeout(10)
                ->connectTimeout(5)
                ->get("https://api.paystack.co/transaction/verify/{$reference}");

            if ($response->successful()) {
                $data = $response->json();
                if (($data['status'] ?? false) === true) {
                    return $data['data'] ?? null;
                }
            }

            Log::error("Paystack verification failed for reference {$reference}: ".$response->body());
        } catch (\Exception $e) {
            Log::error("Paystack verification exception for reference {$reference}: ".$e->getMessage());
        }

        return null;
    }
}
