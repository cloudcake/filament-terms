<?php

namespace Cloudcake\Terms\Models;

use Cloudcake\Terms\Terminology;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * @property array $terms
 */
class PanelTerminology extends Model
{
    use HasUuids;

    private static array $cachedConfig = [];

    private static int $cacheTTL = 360;

    protected function casts(): array
    {
        return [
            'panel_id'  => 'string',
            'tenant_id' => 'string',
            'terms'     => 'array'
        ];
    }

    protected $fillable = [
        'panel_id',
        'tenant_id',
        'terms'
    ];

    public static function boot(): void
    {
        parent::boot();

        static::updated(function (PanelTerminology $model) {
            static::$cachedConfig[$model->panel_id . ($model->tenant_id ?? '')] = $model;

            $panelId = $model->panel_id;
            $tenantId = $model->tenant_id ?? '';

            $cacheKey = "cloudcake-terms-$panelId$tenantId";

            Cache::put($cacheKey, $model, static::$cacheTTL);
        });
    }

    public function getTable(): string
    {
        return Terminology::getTableName();
    }

    public static function current(): static
    {
        $panelId = filament()->getCurrentPanel()->getId() ?? 'default';
        $tenantId = filament()->getTenant() ? filament()->getTenant()->getKey() : null;
        $cacheKey = "cloudcake-terms-$panelId$tenantId";

        if (isset(static::$cachedConfig[$cacheKey]))
            return static::$cachedConfig[$cacheKey];

        $model = Cache::get($cacheKey, false);

        if (!$model)
            $model = PanelTerminology::firstOrCreate([
                'panel_id'  => $panelId,
                'tenant_id' => $tenantId,
            ]);

        static::$cachedConfig[$cacheKey] = $model;

        return $model;
    }
}