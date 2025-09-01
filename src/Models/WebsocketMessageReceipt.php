<?php

namespace Layman\LaravelWebsocket\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class WebsocketMessageReceipt extends Model
{
    use SoftDeletes;

    protected $table = 'websocket_message_receipts';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    protected static string $userModelClass;
    protected static string $userForeignKey;

    public static function setUserModel(string $class, string $foreignKey): Builder
    {
        static::$userModelClass = $class;
        static::$userForeignKey = $foreignKey;
        return static::query();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(static::$userModelClass, static::$userForeignKey, 'receiver')->withDefault();
    }

    public function websocketMessage(): BelongsTo
    {
        return $this->belongsTo(WebsocketMessage::class, 'messages_uuid', 'uuid')->withDefault();
    }

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}
