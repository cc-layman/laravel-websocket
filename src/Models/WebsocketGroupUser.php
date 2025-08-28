<?php

namespace Layman\LaravelWebsocket\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebsocketGroupUser extends Model
{
    protected $table = 'websocket_group_users';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

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
        return $this->belongsTo(static::$userModelClass, static::$userForeignKey, 'userid')->withDefault();
    }

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}
