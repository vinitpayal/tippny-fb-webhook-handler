<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookInputController extends Controller
{
    public function receiveWebhookInput(Request $request){
        $input_data = $request->all();

        $payload_obj = json_decode(json_decode($input_data['text'], true));

        $brand_access_token = null;

        if(property_exists($payload_obj, 'access_token')){
            $brand_access_token = $payload_obj->access_token;
        }

        $brands_list_with_access_token = \App\Model\Brand::where('active', 1)
            ->where('brand_access_token', $brand_access_token)
            ->get(['id']);

        $brand_id = $brands_list_with_access_token[0]->id;


        $fb_payload_obj = \App\Model\WebhookDump::insert([
            "brand_id" => $brand_id,
            "user_ref" => $payload_obj->user_ref,
            "payload" => json_encode($payload_obj),
            "message_sent" => 0
        ]);

    }
}
