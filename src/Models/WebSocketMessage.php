<?php

namespace Layman\LaravelWebsocket\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WebSocketMessage extends Model
{
    use SoftDeletes;

    protected $table = 'websocket_messages';
    protected $guarded = [];

    public function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}
