<?php

namespace App\Modules\Menus\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Modules\Menus\Actions\AttachMenuToParentAction;
use App\Modules\Menus\Actions\CreateMenuAction;
use App\Modules\Menus\Actions\DetachMenuFromParentAction;
use App\Modules\Menus\DTOs\CreateMenuDTO;
use App\Modules\Menus\DTOs\SyncRoleMenusDTO;
use App\Modules\Menus\Queries\GetMenuIdsByRoleQuery;
use App\Modules\Menus\Queries\GetMenusQuery;
use App\Modules\Menus\Queries\GetParentOptionsQuery;
use App\Modules\Menus\Repositories\MenusRepository;
use App\Modules\RoleMenus\Repositories\RoleMenusRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MenusController extends Controller
{
    /**
     * Daftar menu datar, masing-masing dengan nama parent-nya.
     */
    public function index(GetMenusQuery $get_menus): Response
    {
        return Inertia::render('Menus/Index', [
            'menus_data' => $get_menus->execute(),
            'roles' => $this->getRoles(),
        ]);
    }

    public function create(GetParentOptionsQuery $get_parent_options): Response
    {
        return Inertia::render('Menus/Create', [
            'parents' => $get_parent_options->execute(),
        ]);
    }

    public function store(Request $request, CreateMenuAction $create_menu): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            // `key` adalah identitas stabil menu: dipakai sidebar untuk
            // pencocokan dan oleh `resolveActiveMenuKey`, jadi harus unik.
            'key' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9_\-]+$/',
                Rule::unique('menus', 'key'),
            ],
            'icon' => ['required', 'string', 'max:255'],
            'url' => ['required', 'string', 'max:255'],
            'route_name' => ['nullable', 'string', 'max:255'],
            'active_routes' => ['nullable', 'array'],
            'active_routes.*' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
            'parent_id' => ['nullable', 'integer', Rule::exists('menus', 'id')],
        ], [
            'key.regex' => 'Key hanya boleh berisi huruf kecil, angka, underscore, atau strip.',
        ]);

        $menu = $create_menu->execute(CreateMenuDTO::fromRequest($request));

        return back()->with('success', sprintf('Menu "%s" berhasil dibuat!', $menu->name));
    }

    /**
     * Attach menu ke parent: satu parent per menu, dipilih per baris.
     */
    public function manageMenu(GetMenusQuery $get_menus, MenusRepository $repository): Response
    {
        return Inertia::render('Menus/ManageMenu', [
            // `menus_data`, bukan `menus`: key `menus` dipakai HandleInertiaRequests
            // untuk menu milik role yang sedang login, dan prop halaman menimpanya
            // (Inertia `array_merge($shared, $props)`) sehingga sidebar ikut berubah.
            'menus_data' => $get_menus->execute(),
            // Peta descendant dipakai frontend untuk menonaktifkan opsi parent
            // yang akan membentuk siklus.
            'descendantMap' => $repository->getDescendantMap(),
            'parents' => $repository->getParentOptions(),
        ]);
    }

    public function attachToParent(
        Request $request,
        AttachMenuToParentAction $attach_menu
    ): RedirectResponse {
        $validated = $request->validate([
            'menu_id' => ['required', 'integer', Rule::exists('menus', 'id')],
            'parent_id' => ['required', 'integer', Rule::exists('menus', 'id')],
        ]);

        $menu = $attach_menu->execute(
            menuId: (int) $validated['menu_id'],
            parentId: (int) $validated['parent_id'],
        );

        return back()->with('success', sprintf(
            'Menu "%s" sekarang menjadi anak dari "%s".',
            $menu->name,
            $menu->parent?->name ?? 'parent',
        ));
    }

    public function detachFromParent(
        Request $request,
        DetachMenuFromParentAction $detach_menu
    ): RedirectResponse {
        $validated = $request->validate([
            'menu_id' => ['required', 'integer', Rule::exists('menus', 'id')],
        ]);

        $menu = $detach_menu->execute((int) $validated['menu_id']);

        return back()->with('success', sprintf(
            'Menu "%s" sekarang menjadi menu level atas.',
            $menu->name,
        ));
    }

    /**
     * Attach menu ke role. `?role=<id>` memilih role yang ditampilkan;
     * `attachedMenuIds` menandai checkbox mana yang sudah terpasang.
     */
    public function manageRole(
        Request $request,
        GetMenusQuery $get_menus,
        GetMenuIdsByRoleQuery $get_menu_ids_by_role
    ): Response {
        $roles = $this->getRoles();
        $validRoleIds = array_column($roles, 'value');

        $requested = $request->query('role');
        $selectedRoleId = $requested !== null
            ? (int) $requested
            : (int) ($validRoleIds[0] ?? 0);

        // Guard param `role` dari URL: jatuh ke role pertama kalau tidak dikenal,
        // supaya halaman tidak pernah dirender dengan `role_id` yang tak ada.
        if (! in_array($selectedRoleId, $validRoleIds, true)) {
            $selectedRoleId = (int) ($validRoleIds[0] ?? 0);
        }

        return Inertia::render('Menus/ManageRole', [
            // Lihat catatan di manageMenu() soal kenapa bukan `menus`.
            'menus_data' => $get_menus->execute(),
            'roles' => $roles,
            'selectedRoleId' => $selectedRoleId,
            'attachedMenuIds' => $selectedRoleId > 0
                ? $get_menu_ids_by_role->execute($selectedRoleId)
                : [],
        ]);
    }

    public function syncRoleMenus(Request $request, RoleMenusRepository $repository): RedirectResponse
    {
        $validated = $request->validate([
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')],
            'menu_ids' => ['nullable', 'array'],
            'menu_ids.*' => ['integer', Rule::exists('menus', 'id')],
        ]);

        $dto = new SyncRoleMenusDTO(
            role_id: (int) $validated['role_id'],
            menuIds: $validated['menu_ids'] ?? [],
        );

        $result = DB::transaction(
            fn (): array => $repository->syncMenusForRole($dto->role_id, $dto->menuIds)
        );

        $roleName = Role::query()->whereKey($dto->role_id)->value('name') ?? 'role';

        return redirect()
            ->route('menus.manage-role', ['role' => $dto->role_id])
            ->with('success', sprintf(
                'Hak akses menu untuk role "%s" diperbarui: %d ditambahkan, %d dilepas.',
                $roleName,
                $result['attached'],
                $result['detached'],
            ));
    }

    /**
     * @return array<int, array{label: string, value: int}>
     */
    private function getRoles(): array
    {
        return Role::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Role $role) => [
                'label' => $role->name,
                'value' => (int) $role->id,
            ])
            ->values()
            ->toArray();
    }
}
