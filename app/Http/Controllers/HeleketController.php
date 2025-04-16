<?php

namespace App\Http\Controllers;

use Heleket\Api\RequestBuilderException;
use Parsecvpn\Heleket\HeleketSdk;
use Illuminate\Http\Request;

class HeleketController
{
    /**
     * @throws RequestBuilderException
     */
    public function webhook(Request $request)
    {
        $heleket = new HeleketSdk();
        $result = $heleket->read_result($request->all());

        /*
         * You could handle the response of transaction here like:
         * if ($result['status']) {approve order for use or email them...} else {notice them the $result['message']}
         * if $result['message'] is "Trying to fake payment result" then you should block your user!
         * You could get 2 integer variables Session::get('heleket_hacked') & Session::get('heleket_hacked') to decide what to do with your user.
         */

        return $result;
    }
}