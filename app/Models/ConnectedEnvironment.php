<?php

namespace App\Models;

use Database\Factories\ConnectedEnvironmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConnectedEnvironment extends Model
{
    /** @use HasFactory<ConnectedEnvironmentFactory> */
    use HasFactory;

    protected $fillable = [
        'cloud_connection_id',
        'application_id',
        'environment_id',
        'application_name',
        'environment_name',
        'domains',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'domains' => 'array',
            'synced_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<CloudConnection, $this> */
    public function cloudConnection(): BelongsTo
    {
        return $this->belongsTo(CloudConnection::class);
    }
}
