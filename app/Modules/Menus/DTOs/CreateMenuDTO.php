<?php

namespace App\Modules\Menus\DTOs;

use Illuminate\Http\Request;

class CreateMenuDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $key,
        public readonly string $icon,
        public readonly string $url,
        public readonly ?string $route_name,
        public readonly array $active_routes,
        public readonly ?string $description,
        public readonly bool $is_active,
        public readonly ?int $parent_id,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->string('name')->trim()->toString(),
            key: $request->string('key')->trim()->toString(),
            icon: $request->string('icon')->trim()->toString(),
            url: $request->string('url')->trim()->toString(),
            route_name: $request->filled('route_name')
                ? $request->string('route_name')->trim()->toString()
                : null,
            // Kolom `active_routes` berupa JSON array; form mengirimnya sebagai
            // array dari NDynamicInput, dan string kosong berarti "tidak ada".
            active_routes: collect($request->input('active_routes') ?? [])
                ->filter(fn ($route) => filled($route))
                ->map(fn ($route) => trim((string) $route))
                ->values()
                ->all(),
            description: $request->filled('description')
                ? $request->string('description')->trim()->toString()
                : null,
            is_active: $request->boolean('is_active'),
            parent_id: $request->filled('parent_id') ? (int) $request->input('parent_id') : null,
        );
    }

    /**
     * Bentuk siap-simpan untuk kolom `menus`.
     *
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'name' => $this->name,
            'key' => $this->key,
            'icon' => $this->icon,
            'url' => $this->url,
            'route_name' => $this->route_name,
            'active_routes' => $this->active_routes,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'parent_id' => $this->parent_id,
        ];
    }
}
