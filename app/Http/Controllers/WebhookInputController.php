<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookInputController extends Controller
{
    public function receiveWebhookInput(Request $request){
        $inputData = $request->all();
//        print_r($inputData);

        Log::debug($inputData);

        return "success";
    }
}
