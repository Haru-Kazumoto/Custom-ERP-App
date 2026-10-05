<?php

namespace App\Modules\Menus\DTOs;

use Illuminate\Http\Request;

class SyncRoleMenusDTO
{
    /**
     * @param  array<int, int>  $menuIds  menu_id yang harus ter-attach
     */
    public function __construct(
        public readonly int $role_id,
        public readonly array $menuIds,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            role_id: (int) $request->input('role_id'),
            // Checkbox yang tidak tercentang tidak terkirim sama sekali, jadi
            // nilai kosong di sini berarti "lepas semua menu dari role ini".
            menuIds: collect($request->input('menu_ids') ?? [])
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all(),
        );
    }
}
