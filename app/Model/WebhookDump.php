<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class WebhookDump extends Model
{
    protected $table = 'fb_webhook_calls';
    public $timestamps = false;
}
