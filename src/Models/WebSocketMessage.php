<?php

namespace Layman\LaravelWebsocket\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WebSocketMessage extends Model
{
    use SoftDeletes;

    protected $table = 'websocket_messages';
    protected $guarded = [];
}
