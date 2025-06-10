<?php

namespace Layman\LaravelWebsocket\Models;

use Illuminate\Database\Eloquent\Model;

class WebSocketMessage extends Model
{
    protected $table = 'websocket_messages';
    protected $guarded = [];
}
