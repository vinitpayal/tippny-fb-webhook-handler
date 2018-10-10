<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookInputController extends Controller
{
    public function receiveWebhookInput(Request $request){
        $input_data = $request->all();

        $payload_obj = $input_data['payload'];
        Log::info("Payload");
        Log::info($payload_obj);
	
	return "success";

        $ref_data = $payload_obj['optin']['ref'];

        Log::info("ref data");
        Log::info($ref_data);

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
            "user_ref" => $ref_data->user_ref,
            "payload" => json_encode($ref_data),
            "click_origin" => $click_origin,
            "message_sent" => 0
        ]);

    }
}
