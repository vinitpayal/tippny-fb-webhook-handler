<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookInputController extends Controller
{
    public function receiveWebhookInput(Request $request){
        $input_data = $request->all();

        $payload_obj = json_decode(json_decode($input_data['payload'], true));

        Logging::info("Payload");
        Logging::info($payload_obj);

        $ref_data = $payload_obj['optin']['ref'];

        Logging::info("ref data");
        Logging::info($ref_data);

        $brand_access_token = null;
        $brand_id = null;

        $click_origin = 'add-to-cart';

        if(property_exists($ref_data, 'access_token')){
            $brand_access_token = $ref_data->access_token;

            $brands_list_with_access_token = \App\Model\Brand::where('active', 1)
                ->where('brand_access_token', $brand_access_token)
                ->get(['id']);

            $brand_id = $brands_list_with_access_token[0]->id;
        }

        if(property_exists($ref_data, 'origin')){
            $click_origin = $ref_data->origin;
        }

        $fb_payload_obj = \App\Model\WebhookDump::insert([
            "brand_id" => $brand_id,
            "fb_user_id" => $payload_obj['sender']['id'],
            "payload" => json_encode($ref_data),
            "click_origin" => $click_origin,
            "message_sent" => 0
        ]);

    }
}
