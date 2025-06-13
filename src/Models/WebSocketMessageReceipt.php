<?php

namespace Layman\LaravelWebsocket\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WebSocketMessageReceipt extends Model
{
    use SoftDeletes;

    protected $table = 'websocket_message_receipts';
    protected $guarded = [];

    protected function websocketMessage(): BelongsTo
    {
        return $this->belongsTo(WebSocketMessage::class, 'msg_id', 'msg_id')->withDefault();
    }
}
