<?php

namespace Layman\LaravelWebsocket\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WebSocketMessageReceipt extends Model
{
    use SoftDeletes;

    protected $table = 'websocket_message_receipts';
    protected $guarded = [];

    public function websocketMessage(): BelongsTo
    {
        return $this->belongsTo(WebSocketMessage::class, 'msg_id', 'msg_id')->withDefault();
    }

    public function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}
