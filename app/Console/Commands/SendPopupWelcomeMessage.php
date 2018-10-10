<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;


class SendPopupWelcomeMessage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'message:popup-welcome';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Will send messages to users who subscribed using popup';

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
        $try_count = 0;
        $delay_in_msg_sending = env('DELAY_IN_MESSAGE_SENDING', 60);
        $users_list_to_send_message = \App\Model\WebhookDump::whereNotNull('brand_id')
            ->where('message_sent', 0)
            ->where('click_origin', 'popup')
            ->whereRaw('created_at <= now() - interval ? second', [$delay_in_msg_sending])
            ->limit(200)
            ->orderBy('created_at')
            ->get();

        $genericPayload = [
            "recipient" => null,
            "message" => [
                "attachment" => [
                    "type" => "image",
    		        "payload" => [
    		            "url" => "https://tippny.com/images/logo_red.png",
                        "is_reusable" => 'true'
                    ]
                ],
                "quick_replies" => [
                    0 => [
                        "content_type" => "text",
                        "title" => "❤ Add To Favorites",
                        "payload" => "tippny_subscribe_now"
                    ],
                    1 => [
                        "content_type" => "text",
                        "title" => "Not Now",
                        "payload" => "tippny_dont_subscribe"
                    ]
                ]
            ]
        ];

        foreach ($users_list_to_send_message as $fb_webhook_response) {
            
            $brand_id = $fb_webhook_response['brand_id'];
            $intro_msg_sent = $this->send_introduction_message($brand_id, $fb_webhook_response->user_ref);

            // sent promotional msg only after sending intro msg
            if ($intro_msg_sent) {

                $payload = json_decode($fb_webhook_response['payload']);

                $brand_suscription_payload = ';brandid:'.fb_webhook_response['brand_id'];

                if(property_exists($payload, 'brand_location_id')){
                    $brand_suscription_payload .= ";locationid:".$payload->brand_location_id;
                }

                $genericPayload['recipient'] = ["user_ref" => $payload->user_ref];
                $genericPayload['message']['quick_replies'][0]['payload'] .= $brand_suscription_payload;
                $genericPayload['message']['quick_replies'][1]['payload'] .= $brand_suscription_payload;

                Log::info('generic payload');
                Log::info($genericPayload);

                $client = new Client();

                $access_token = env("FB_PAGE_ACCESS_TOKEN");

                $fb_page_url = 'https://graph.facebook.com/v2.6/me/messages?access_token=' . $access_token;

                $response = $client->request('POST', $fb_page_url, ['json' => $genericPayload]);

                if ($response->getStatusCode() == 200) {
                    $send_res = json_decode($response->getBody());
                    $message_id = $send_res->message_id;

                    $fb_webhook_response->message_sent = 1;
                    $fb_webhook_response->sent_message_id = $message_id;

                    $fb_webhook_response->save();
                }
            }
        }

        $try_count += 1;

        if($try_count <= 55){
            sleep(1);
            $this->handle();
        }
    }

    public function send_introduction_message($brand_id, $user_ref){
        $brand_obj =  \App\Model\Brand::where('active', 1)->find($brand_id);

        if($brand_obj){
            $brand_communicator_name = ucfirst(strtolower($brand_obj->brand_communicator_person_name));

            $text_message = sprintf("Hi! This is %s from %s. Thank you for visiting our store! "
                ."Please tap Add To Favorites 👇 and Get 5%% OFF in your next visit! "
                ."Receive Coupons and more Special Offers from us right here on Tippny!", $brand_communicator_name, $brand_obj->brand_name);

            $recepient_obj = new \stdClass();
            $recepient_obj->user_ref = $user_ref;

            $msg_obj = new \stdClass();
            $msg_obj->text = $text_message;

            $text_msg_payload = [
                "recipient" => $recepient_obj,
                "message" =>  $msg_obj
            ];

            $client = new Client();

            $access_token = env("FB_PAGE_ACCESS_TOKEN");

            $fb_page_url = 'https://graph.facebook.com/v2.6/me/messages?access_token='.$access_token;

            $response = $client->request('POST', $fb_page_url, ['json' => $text_msg_payload]);

            if($response->getStatusCode() == 200) {
                return true;
            }

            else {
                Log::error("Ufff!!! error in sending introduction message");
                Log::error($response->getBody());
                return false;
            }
        }
    }

    public function get_payload_data($fbPayloadObj, $genericPayload){

        $curr_msg_payload = $genericPayload;

        $user_ref = $fbPayloadObj->user_ref;

        $recepient_obj = new \stdClass();
        $recepient_obj->user_ref = $user_ref;

        $curr_msg_payload['recipient'] = $recepient_obj;

        $element = new \stdClass();
        $element->media_type = "image";
        $element->url = "https://business.facebook.com/Tippny/photos/a.2034305916850603/2034375373510324";

        $curr_msg_payload['message']['attachment']['payload']['elements'][0] = $element;

        return $curr_msg_payload;

    }
}
