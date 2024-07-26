<?php

namespace FunnyDev\Cryptomus;

use App\Http\Controllers\System\BinanceSystem;
use Cryptomus\Api\Client;
use Cryptomus\Api\Payment;
use Cryptomus\Api\Payout;
use Cryptomus\Api\RequestBuilderException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;

class CryptomusSdk
{
    public array $url;
    private string $merchant_uuid;
    private string $payment_key;
    private string $payout_key;
    public Payment $client_payment;
    public Payout $client_payout;

    public function __construct(string $merchant_uuid='', string $payment_key='', string $payout_key='')
    {
        $this->merchant_uuid = empty($merchant_uuid) ? Config::get('cryptomus.merchant_uuid') : $merchant_uuid;
        $this->payment_key = empty($payment_key) ? Config::get('cryptomus.payment_key') : $payment_key;
        $this->payout_key = empty($payout_key) ? Config::get('cryptomus.payout_key') : $payout_key;
        $this->client_payment = Client::payment($this->payment_key, $this->merchant_uuid);
        $this->client_payout = Client::payout($this->payout_key, $this->merchant_uuid);
    }

    public function convert_array($data): array
    {
        if (! $data) {
            return [];
        }
        $tmp = json_decode(json_encode($data, true), true);
        if (! is_array($tmp)) {
            $tmp = json_decode($tmp, true);
        }
        return $tmp;
    }

    /**
     * @throws RequestBuilderException
     */
    public function balance(): float
    {
        $balance = 0;
        $data = $this->client_payment->balance();
        foreach ($data[0]['balance']['merchant'] as $x) {
            $balance += floatval($x['balance_usd']);
        }
        foreach ($data[0]['balance']['user'] as $x) {
            $balance += floatval($x['balance_usd']);
        }
        return $balance;
    }

    /**
     * @throws RequestBuilderException
     */
    public function create_payment(string $invoice_number, float|int $amount, string $currency='USDT', string $network='BSC', string $description='', string $return_url='', string $back_url='', string $success_url=''): string
    {
        $param = [
            'amount' => strval($amount),
            'currency' => $currency,
            'network' => $network,
            'order_id' => $invoice_number,
            'additional_data' => $invoice_number,
            'url_return' => $return_url,
            'url_success' => $success_url,
            'url_callback' => $back_url,
            'is_payment_multiple' => true,
            'lifetime' => '3600',
            'is_refresh' => true,
            'to_currency' => $currency,
            'course_source' => 'Binance'
        ];
        $response = $this->client_payment->create($param);
        return $response['url'];
    }

    /**
     * @throws RequestBuilderException
     */
    public function ipn(string $invoice_number=''): mixed
    {
        return $this->client_payment->reSendNotifications(['order_id' => $invoice_number]);
    }

    public function verify_result(array $param): bool
    {
        $sign = $param['sign'];
        unset($param['sign']);
        $hash = md5(base64_encode(json_encode($param, JSON_UNESCAPED_UNICODE)) . $this->payment_key);
        if (hash_equals($hash, $sign)) {
            return true;
        }
        return false;
    }

    public function read_result(array $param): array
    {
        $result = [
            'status' => false,
            'init_amount' => 0,
            'payment_amount' => 0,
            'currency' => '',
            'invoice_number' => '',
            'message' => 'Unknown error',
            'description' => ''
        ];
        if ($this->verify_result($param)) {
            if ($param['is_final'] && $param['order_id'] && in_array($param['status'], ['paid', 'paid_over'])) {
                $result['status'] = true;
                $result['init_amount'] = floatval($result['amount']);
                $result['payment_amount'] = floatval($result['payment_amount']);
                $result['currency'] = $result['payer_currency'];
                $result['invoice_number'] = $param['order_id'];
            } else {
                $result['message'] = $param['status'];
            }
        } else {
            $hacked = Session::get('cryptomus_hacked') ? Session::get('cryptomus_hacked') + 1 : 1;
            $result['message'] = 'Trying to fake payment result';
            Session::put('cryptomus_hacked', $hacked);
        }
        return $result;
    }
}