<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'key', 'icon', 'url', 'route_name', 'active_routes', 'description', 'is_active', 'parent_id'])]
#[Table('menus')]
class Menu extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // kolom JSON,iar frontend selalu menerima array
            'active_routes' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Menu induk. `parent_id` menunjuk ke baris `menus` lain, jadi relasinya
     * belongsTo — bukan hasOne.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Menu anak. Kalau `parent_id` menunjuk ke `$this->id`, baris tersebut
     * adalah anak dari menu ini.
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
