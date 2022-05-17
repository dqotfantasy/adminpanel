<?php

namespace App;

use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class Razorpay
{
    public $key;
    public $webhookSecret;
    public $accountNumber;
    private $secret;

    public function __construct()
    {
        $token = Redis::get('razorpay');
        if (isset($token) && !is_null($token)) {
            $data = json_decode($token);
            $this->key = collect($data)->get('access_key');
            $this->secret = collect($data)->get('secret_key');
            $this->accountNumber = collect($data)->get('account_number');
            $this->webhookSecret = collect($data)->get('webhook_secret');
        }
    }

    public function payment($id)
    {
        $reponse = $this->get('payments/' . $id);
        $data = $reponse->json();

        if (isset($data['error'])) {
            return null;
        }

        return $reponse->json();
    }

    public function get($url, $params = []): Response
    {
        return Http::get('https://' . $this->key . ':' . $this->secret . '@api.razorpay.com/v1/' . $url, $params);
    }

    public function capture($id, $amount)
    {
        $reponse = $this->post('payments/' . $id . '/capture', [
            'amount' => $amount,
            'currency' => 'INR'
        ]);

        $data = $reponse->json();

        if (isset($data['error'])) {
            return null;
        }

        return $reponse->json();
    }

    public function post($url, $data = []): Response
    {
        return Http::post('https://' . $this->key . ':' . $this->secret . '@api.razorpay.com/v1/' . $url, $data);
    }

    public function withdraw($account)
    {
        $reponse = $this->post('payouts', $account);
        $data = $reponse->json();

        if (isset($data['error']) && $data['error']['description'] != null) {
            return null;
        }
        return $reponse->json();
    }


    public function verifySignature(Request $request)
    {
        try {
            $payload = $request->getContent();
            $signature = $request->header('X-Razorpay-Signature');
            $expectedSignature = hash_hmac('sha256', $payload, $this->webhookSecret);

            // Use lang's built-in hash_equals if exists to mitigate timing attacks
            if (function_exists('hash_equals')) {
                $verified = hash_equals($expectedSignature, $signature);
            } else {
                $verified = $this->hashEquals($expectedSignature, $signature);
            }

            return $verified;
        } catch (\Exception $exception) {
            return false;
        }
    }

    private function hashEquals($expectedSignature, $actualSignature)
    {
        if (strlen($expectedSignature) === strlen($actualSignature)) {
            $res = $expectedSignature ^ $actualSignature;
            $return = 0;

            for ($i = strlen($res) - 1; $i >= 0; $i--) {
                $return |= ord($res[$i]);
            }

            return ($return === 0);
        }

        return false;
    }
}
