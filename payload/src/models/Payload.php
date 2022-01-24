<?php

namespace Increment\Common\Payload\Models;

use Illuminate\Database\Eloquent\Model;
use App\APIModel;

class Payload extends APIModel
{
    protected $table = 'payloads';
    protected $fillable = ['account_id', 'payload', 'payload_value', 'category', 'details'];

}

