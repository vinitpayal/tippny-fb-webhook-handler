<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;


class SendMessage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'message:cart-product';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Will send messages to users who added item in cart';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $delay_in_msg_sending = env('DELAY_IN_CARD_ADD_AND_SEND_MESSSAGE', 60);
        $users_list_to_send_message = \App\Model\WebhookDump::whereNotNull('brand_id')
            ->where('message_sent', 0)
            ->whereRaw('created_at <= now() - interval ? second', [$delay_in_msg_sending])
            ->limit(200)
            ->get();

        $genericPayload = [
            "recipient" => null,
            "message" => [
                "attachment" => [
                    "type" => "template",
    		        "payload" => [
    		            "template_type" => "generic",
    			        "elements" => []
                    ]
                ]
            ]
        ];

        foreach ($users_list_to_send_message as $fb_webhook_response){

            $payload = json_decode($fb_webhook_response['payload']);

            $curr_msg_payload = $this->get_payload_data($payload, $genericPayload);

            $client = new Client();

            $access_token = env("FB_PAGE_ACCESS_TOKEN");

            $fb_page_url = 'https://graph.facebook.com/v2.6/me/messages?access_token='.$access_token;

            $response = $client->request('POST', $fb_page_url, ['json' => $curr_msg_payload]);

            if($response->getStatusCode() == 200){
                $send_res = json_decode($response->getBody());
                $message_id = $send_res->message_id;

                $fb_webhook_response->message_sent = 1;
                $fb_webhook_response->sent_message_id = $message_id;

                $fb_webhook_response->save();
            }



        }

    }

    public function get_payload_data($fbPayloadObj, $genericPayload){

        $curr_msg_payload = $genericPayload;

        $user_ref = $fbPayloadObj->user_ref;
        $product_url = $fbPayloadObj->product_url;
        $add_to_cart_url = $fbPayloadObj->product_add_to_cart_url;
        $product_image = $fbPayloadObj->product_image;


        $recepient_obj = new \stdClass();
        $recepient_obj->user_ref = $user_ref;

        $curr_msg_payload['recipient'] = $recepient_obj;

        $curr_msg_payload['message']['attachment']['payload']['elements'][0]['image_url'] = $product_image;
        $curr_msg_payload['message']['attachment']['payload']['elements'][0]['default_action'][0]['url'] = $product_url;
        $curr_msg_payload['message']['attachment']['payload']['elements'][0]['buttons'][0]['url'] = $product_url;
        $curr_msg_payload['message']['attachment']['payload']['elements'][0]['buttons'][1]['url'] = $add_to_cart_url;

        $element = new \stdClass();
        $default_action = new \stdClass();

        $element->title = "Last Chance to claim discount";
        $element->image_url = $product_image;
        $element->subtitle = "iPhone";

        $default_action->type = "web_url";
        $default_action->url = $product_url;
        $default_action->messenger_extensions = "TRUE";
        $default_action->webview_height_ratio = "FULL";

        $element->default_action = $default_action;

        $buy_btn = new \stdClass();
        $buy_btn->type = "web_url";
        $buy_btn->url = $product_url;
        $buy_btn->title = "Buy Now";

        $add_to_cart_btn = new \stdClass();
        $add_to_cart_btn->type = 'web_url';
        $add_to_cart_btn->url = $add_to_cart_url;
        $add_to_cart_btn->title = 'Add To Cart';

        $element->buttons = [$buy_btn, $add_to_cart_btn];

        $curr_msg_payload['message']['attachment']['payload']['elements'][0] = $element;

        return $curr_msg_payload;

    }
}
