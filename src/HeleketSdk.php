<?php

namespace Parsecvpn\Heleket;

use Heleket\Api\Client;
use Heleket\Api\Payment;
use Heleket\Api\Payout;
use Heleket\Api\RequestBuilderException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class HeleketSdk
{
    public array $url;
    private string $merchant_uuid;
    private string $payment_key;
    private string $payout_key;
    public Payment $client_payment;
    public Payout $client_payout;

    public function __construct(string $merchant_uuid='', string $payment_key='', string $payout_key='')
    {
        $this->merchant_uuid = empty($merchant_uuid) ? Config::get('heleket.merchant_uuid') : $merchant_uuid;
        $this->payment_key = empty($payment_key) ? Config::get('heleket.payment_key') : $payment_key;
        $this->payout_key = empty($payout_key) ? Config::get('heleket.payout_key') : $payout_key;
        $this->client_payment = Client::payment($this->payment_key, $this->merchant_uuid);
        $this->client_payout = Client::payout($this->payout_key, $this->merchant_uuid);
    }

    public function convert_currency(float|int $amount=0, $currency='USD'): float|int|null
    {
        if ($currency == 'USD') {
            return $amount;
        }

        $prices = Http::get('https://api.heleket.com/v1/exchange-rate/USD/list')->json();

        foreach ($prices['result'] as $price) {
            if ($price['to'] == $currency) {
                return $amount * floatval(str_replace(',', '', $price['course']));
            }
        }

        return null;
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
    public function create_payment(string $invoice_number, float|int $amount, string $currency='USD', string $to_currency='', string $network='', string $return_url='', string $back_url='', string $success_url=''): string
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
            'course_source' => 'Binance'
        ];
        if ($to_currency) {
            $param['to_currency'] = $to_currency;
        }
        if ($network) {
            $param['network'] = $network;
        }
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
            'invoice_number' => ''
        ];
        if ($this->verify_result($param)) {
            if ($param['is_final'] && $param['order_id'] && in_array($param['status'], ['paid', 'paid_over'])) {
                $result['status'] = true;
                $result['init_amount'] = floatval($param['amount']);
                $result['payment_amount'] = floatval($param['payment_amount']);
                $result['currency'] = $param['payer_currency'];
                $result['invoice_number'] = $param['order_id'];
                $result['message'] = 'Payment successfully from Heleket';
            } else {
                $result['message'] = 'Payment error '.$param['status'].' from Heleket';
            }
        } else {
            $hacked = Session::get('heleket_hacked') ? Session::get('heleket_hacked') + 1 : 1;
            $result['message'] = 'Trying to fake payment result';
            Session::put('heleket_hacked', $hacked);
        }
        return $result;
    }

    public function check_payment(string $uuid, string $invoice_number): array
    {
        $result = [
            'status' => false,
            'init_amount' => 0,
            'payment_amount' => 0,
            'currency' => '',
            'invoice_number' => ''
        ];
        $data = $this->client_payment->info(['uuid' => $uuid, 'order_id' => $invoice_number]);
        if ($data['is_final'] && $data['order_id'] && in_array($data['status'], ['paid', 'paid_over'])) {
            $result['status'] = true;
            $result['init_amount'] = floatval($data['amount']);
            $result['payment_amount'] = floatval($data['payment_amount']);
            $result['currency'] = $data['payer_currency'];
            $result['invoice_number'] = $data['order_id'];
            $result['message'] = 'Payment successfully from Heleket';
        } else {
            $result['message'] = 'Payment error '.$data['status'].' from Heleket';
        }

        return $result;
    }
}