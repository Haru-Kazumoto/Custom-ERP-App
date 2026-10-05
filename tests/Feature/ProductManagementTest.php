<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Product;
use App\Models\Role;
use App\Models\RoleMenus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    private function userWithProductMenu(): User
    {
        $role = Role::query()->create(['name' => 'business_development', 'code' => 'BD']);

        $parent = Menu::query()->create([
            'name' => 'Manajemen Produk', 'key' => 'products', 'icon' => 'Package',
            'url' => '/products', 'route_name' => null, 'active_routes' => [],
            'description' => null, 'is_active' => true, 'parent_id' => null,
        ]);

        $child = Menu::query()->create([
            'name' => 'Daftar Produk', 'key' => 'products-list', 'icon' => 'Package',
            'url' => '/products/list', 'route_name' => null, 'active_routes' => [],
            'description' => null, 'is_active' => true, 'parent_id' => $parent->id,
        ]);

        // ，只有 parent，没有 child，正是之前排障的那个坑，必须能正常渲染。
        RoleMenus::query()->create(['role_id' => $role->id, 'menu_id' => $parent->id]);

        return User::factory()->withPersonalTeam()->create(['role_id' => $role->id]);
    }

    private function payload(string $uri): array
    {
        $response = $this->get($uri);

        $response->assertOk();

        return json_decode($response->getContent(), true);
    }

    public function test_index_renders_with_empty_state(): void
    {
        $this->actingAs($this->userWithProductMenu());

        $data = $this->payload('/products/list');

        $this->assertSame('Product/Index', $data['component']);
        $this->assertCount(0, $data['props']['products']['data']);
        $this->assertSame(0, $data['props']['products']['total']);
        $this->assertSame('', $data['props']['filters']['search']);
    }

    public function test_create_page_exposes_reference_options(): void
    {
        $this->actingAs($this->userWithProductMenu());

        $data = $this->payload('/products/create');

        $this->assertSame('Product/Create', $data['component']);
        // Tabel referensi kosong → array kosong, bukan error.
        $this->assertSame([], $data['props']['productTypes']);
        $this->assertSame([], $data['props']['productSubTypes']);
        $this->assertSame([], $data['props']['vendors']);
    }

    public function test_store_create_show_edit_update_destroy(): void
    {
        $this->actingAs($this->userWithProductMenu());

        // create — semua relasi null, dulu tidak muncul di daftar karena INNER JOIN。
        $this->post('/products/store', [
            'name' => 'Binding Machine', 'code' => 'BRG-001', 'unit' => 'Pcs',
            'category' => 'Elektronik', 'price' => '1250000.50',
            'product_type_id' => null, 'product_sub_type_id' => null, 'vendor_id' => null,
        ])->assertRedirect('/products/list');

        $product = Product::query()->where('code', 'BRG-001')->sole();
        $this->assertSame('Binding Machine', $product->name);
        $this->assertNull($product->product_type_id);
        $this->assertNull($product->vendor_id);

        // index — produk dengan relasi NULL tetap terlihat。
        $data = $this->payload('/products/list');
        $this->assertSame(1, $data['props']['products']['total']);
        $this->assertSame('BRG-001', $data['props']['products']['data'][0]['code']);
        // decimal harus jadi number, bukan string。
        $this->assertIsFloat($data['props']['products']['data'][0]['price']);
        $this->assertSame(1250000.5, $data['props']['products']['data'][0]['price']);

        // show
        $data = $this->payload('/products/'.$product->id);
        $this->assertSame('Product/Show', $data['component']);
        $this->assertSame('BRG-001', $data['props']['product']['code']);

        // edit
        $data = $this->payload('/products/'.$product->id.'/edit');
        $this->assertSame('Product/Edit', $data['component']);

        // update
        $this->put('/products/'.$product->id, [
            'name' => 'Binding Machine LX-2', 'code' => 'BRG-001', 'unit' => 'Box',
            'category' => 'Elektronik', 'price' => '1500000',
            'product_type_id' => null, 'product_sub_type_id' => null, 'vendor_id' => null,
        ])->assertRedirect('/products/list');

        $this->assertSame('Binding Machine LX-2', $product->fresh()->name);

        // update tanpa mengubah kode sendiri tidak boleh kena validasi unique。
        $this->put('/products/'.$product->id, [
            'name' => 'Binding Machine LX-3', 'code' => 'BRG-001', 'unit' => 'Box',
            'category' => 'Elektronik', 'price' => null,
            'product_type_id' => null, 'product_sub_type_id' => null, 'vendor_id' => null,
        ])->assertRedirect('/products/list');
        $this->assertNull($product->fresh()->price);

        // destroy
        $this->delete('/products/'.$product->id)->assertRedirect('/products/list');
        $this->assertSame(0, Product::query()->count());
    }

    public function test_store_rejects_duplicate_code(): void
    {
        $this->actingAs($this->userWithProductMenu());

        foreach (['BRG-001', 'BRG-002'] as $code) {
            $this->post('/products/store', [
                'name' => 'Produk '.$code, 'code' => $code, 'unit' => 'Pcs',
                'category' => 'ATK', 'price' => null,
                'product_type_id' => null, 'product_sub_type_id' => null, 'vendor_id' => null,
            ])->assertSessionHasNoErrors();
        }

        $this->post('/products/store', [
            'name' => 'Duplikat', 'code' => 'BRG-001', 'unit' => 'Pcs',
            'category' => 'ATK', 'price' => null,
            'product_type_id' => null, 'product_sub_type_id' => null, 'vendor_id' => null,
        ])->assertSessionHasErrors('code');

        $this->assertSame(2, Product::query()->count());
    }

    public function test_store_requires_core_fields(): void
    {
        $this->actingAs($this->userWithProductMenu());

        $this->post('/products/store', [])->assertSessionHasErrors([
            'name', 'code', 'unit', 'category',
        ]);

        $this->assertSame(0, Product::query()->count());
    }

    public function test_store_rejects_unknown_foreign_keys(): void
    {
        $this->actingAs($this->userWithProductMenu());

        $this->post('/products/store', [
            'name' => 'X', 'code' => 'X-1', 'unit' => 'Pcs', 'category' => 'ATK',
            'price' => 100, 'product_type_id' => 999, 'product_sub_type_id' => 888, 'vendor_id' => 777,
        ])->assertSessionHasErrors(['product_type_id', 'product_sub_type_id', 'vendor_id']);
    }

    public function test_store_rejects_negative_price(): void
    {
        $this->actingAs($this->userWithProductMenu());

        $this->post('/products/store', [
            'name' => 'X', 'code' => 'X-1', 'unit' => 'Pcs', 'category' => 'ATK',
            'price' => -1, 'product_type_id' => null, 'product_sub_type_id' => null, 'vendor_id' => null,
        ])->assertSessionHasErrors('price');
    }

    public function test_index_supports_search(): void
    {
        $this->actingAs($this->userWithProductMenu());

        foreach ([['Kabel Listrik', 'ELK-01', 'Elektronik'], ['Kabel Jaringan', 'NET-01', 'Jaringan']] as [$name, $code, $category]) {
            $this->post('/products/store', [
                'name' => $name, 'code' => $code, 'unit' => 'Lusin', 'category' => $category,
                'price' => null, 'product_type_id' => null, 'product_sub_type_id' => null, 'vendor_id' => null,
            ]);
        }

        $data = $this->payload('/products/list?search=Kabel');
        $this->assertSame(2, $data['props']['products']['total']);
        $this->assertSame('Kabel', $data['props']['filters']['search']);

        $data = $this->payload('/products/list?search=NET-01');
        $this->assertSame(1, $data['props']['products']['total']);

        $data = $this->payload('/products/list?search=Jaringan');
        $this->assertSame(1, $data['props']['products']['total']);

        $data = $this->payload('/products/list?search=tidak-ada');
        $this->assertSame(0, $data['props']['products']['total']);
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/products/list')->assertRedirect('/login');
    }
}
