<?php

namespace App\Http\Middleware;

use App\Modules\Account\Queries\GetAccountQuery;
use App\Modules\RoleMenus\Queries\GetMenusByIdRoleQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{

    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $this->getAccountInformation($request)
            ],
            'menus' => $this->getMenusIfAuthenticated($request),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }

    private function getMenusIfAuthenticated(Request $request): array
    {
        return $request->user()
            ? app(GetMenusByIdRoleQuery::class)->execute($request->user()->role_id)
            : [];
    }

    private function getAccountInformation(Request $request)
    {
        return $request->user()
            ? app(GetAccountQuery::class)->execute($request->user()->id)
            : [];
    }
}
