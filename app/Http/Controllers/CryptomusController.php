<?php

namespace App\Http\Controllers;

use Cryptomus\Api\RequestBuilderException;
use FunnyDev\Cryptomus\CryptomusSdk;
use Illuminate\Http\Request;

class CryptomusController
{
    /**
     * @throws RequestBuilderException
     */
    public function webhook(Request $request)
    {
        $cryptomus = new CryptomusSdk();
        $result = $cryptomus->read_result($request->all());

        /*
         * You could handle the response of transaction here like:
         * if ($result['status']) {approve order for use or email them...} else {notice them the $result['message']}
         * if $result['message'] is "Trying to fake payment result" then you should block your user!
         * You could get 2 integer variables Session::get('cryptomus_hacked') & Session::get('cryptomus_hacked') to decide what to do with your user.
         */

        return $result;
    }
}