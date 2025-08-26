<?php

namespace Layman\LaravelWebsocket\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WebsocketGroup extends Model
{
    use SoftDeletes;

    protected $table = 'websocket_groups';
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

    public function websocketGroupUser(): HasMany
    {
        return $this->hasMany(WebsocketGroupUser::class, 'group_code', 'uuid');
    }

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}
