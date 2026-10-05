<?php

namespace App\Modules\Product\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductRepository
{
    /**
     * Kolom yang boleh dipakai untuk sorting dari query string.
     *
     * Dipakai allowlist supaya nama kolom dari URL tidak bisa disisipkan mentah
     * ke ORDER BY.
     *
     * @var array<int, string>
     */
    private const SORTABLE = ['id', 'name', 'code', 'unit', 'category', 'price', 'type', 'sub_type', 'vendor'];

    /**
     * Katalog untuk form pemesanan (Purchase Order).
     *
     * Dipakai lewat endpoint JSON `api/product`, jadi bentuk kolomnya harus
     * tetap `p.id as product_id` — berbeda dengan `getPaginated()` yang memakai
     * `p.id` untuk form edit.
     *
     * `limit` dipakai supaya halaman Create tidak menarik seluruh katalog
     * sekaligus. `->limit()` yang dulu dikomentari membuat katalog berukuran
     * besar dikirim penuh ke browser setiap kali dropdown dibuka.
     *
     * @param  array{category?: string|null, vendorId?: int|null}  $filters
     * @return Collection<int, object>
     */
    public function getAll(
        ?string $search = null,
        int $limit = 20,
        array $filters = [],
    ) {
        $category = $filters['category'] ?? null;
        $vendorId = $filters['vendorId'] ?? null;

        return DB::table('products as p')
            ->select([
                'p.id as product_id',
                'p.name',
                'p.code',
                'p.unit',
                'p.category',
                'p.price',
                'v.name as vendor',
                'pt.name as type',
                'pst.name as sub_type',
                'p.vendor_id',
                'p.product_type_id',
                'p.product_sub_type_id',
                // trade promos sebagai JSON array per produk
                DB::raw("(
                SELECT COALESCE(
                    JSON_ARRAYAGG(
                        JSON_OBJECT(
                            'id', tp.id,
                            'name', tp.name,
                            'price', tp.price,
                            'quota', tp.quota,
                            'is_active', tp.is_active
                        )
                    ), JSON_ARRAY()
                )
                FROM products_trade_promo as ptp
                JOIN trade_promo as tp ON tp.id = ptp.trade_promo_id
                WHERE ptp.product_id = p.id
                  AND tp.is_active = 1
            ) as trade_promos"),
            ])
            // LEFT JOIN wajib: `product_type_id`, `product_sub_type_id`, dan
            // `vendor_id` semuanya nullable di DB. Dengan INNER JOIN produk yang
            // salah satu relasinya kosong ikut lenyap dari daftar — gejalanya
            // produk "hilang sendiri" right setelah dibuat tanpa tipe/vendor.
            ->leftJoin('product_type as pt', 'pt.id', '=', 'p.product_type_id')
            ->leftJoin('product_sub_type as pst', 'pst.id', '=', 'p.product_sub_type_id')
            ->leftJoin('vendor as v', 'v.id', '=', 'p.vendor_id')
            ->when($search, function ($q, $search) {
                $q->where(function ($w) use ($search) {
                    $w->where('p.name', 'like', "%{$search}%")
                        ->orWhere('p.code', 'like', "%{$search}%")
                        // Kategori dan nama principal ikut dicari supaya user tidak
                        // perlu mengingat kode produk persis.
                        ->orWhere('p.category', 'like', "%{$search}%")
                        ->orWhere('v.name', 'like', "%{$search}%");
                });
            })
            ->when($category, fn ($q, $category) => $q->where('p.category', $category))
            ->when($vendorId, fn ($q, $vendorId) => $q->where('p.vendor_id', $vendorId))
            ->orderBy('p.name')
            // Without orderBy+limit, MySQL tidak boleh memakai index dan seluruh
            // katalog ikut terambil — itu yang membuat dropdown berat.
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                // JSON_ARRAYAGG mengembalikan string di sebagian driver → decode
                $row->trade_promos = is_string($row->trade_promos)
                    ? json_decode($row->trade_promos, true)
                    : ($row->trade_promos ?? []);

                return $row;
            });
    }

    /**
     * Daftar produk untuk halaman manajemen: sudah berupa `id` asli (bukan
     * alias `product_id` seperti `getAll()`) supaya form edit bisa memakainya.
     */
    public function getPaginated(?string $search = null, int $perPage = 10, string $sortBy = 'id', string $sortDir = 'desc'): LengthAwarePaginator
    {
        $column = in_array($sortBy, self::SORTABLE, true) ? $sortBy : 'id';
        $direction = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';

        // Kolom hasil join dipetakan balik ke kolom asli supaya sorting
        // deterministik dan tidak bergantung pada alias yang ambigu.
        $orderBy = match ($column) {
            'type' => 'pt.name',
            'sub_type' => 'pst.name',
            'vendor' => 'v.name',
            default => "p.$column",
        };

        return $this->listQuery($search)
            ->orderBy($orderBy, $direction)
            ->orderBy('p.id', 'desc')
            ->paginate($perPage)
            ->through(fn ($row) => $this->castRow($row))
            // Query string ikut dibawa ke link paginator supaya `?search=` dan
            // `?sort_by=` tidak hilang saat pindah halaman.
            ->withQueryString();
    }

    public function find(int $id): ?object
    {
        $row = $this->listQuery()->where('p.id', $id)->first();

        return $row === null ? null : $this->castRow($row);
    }

    /**
     * Rapikan tipe sebelum dikirim ke frontend.
     *
     * Query builder mengembalikan `decimal(18,2)` sebagai string (`"150000.00"`),
     * sementara tipe `price` di frontend sudah `number | null`. Tanpa cast ini
     * frontend perlu menebak-nebak bentuk datanya.
     */
    private function castRow(object $row): object
    {
        $row->price = $row->price === null ? null : (float) $row->price;

        return $row;
    }

    public function create(array $attributes): int
    {
        return (int) DB::table('products')->insertGetId($attributes);
    }

    public function update(int $id, array $attributes): bool
    {
        return DB::table('products')
            ->where('id', $id)
            ->update($attributes + ['updated_at' => now()]) > 0;
    }

    /**
     * Hard delete. `product_prices.product_id` memakai `cascadeOnDelete`, jadi
     * harga dinamis produk ikut terhapus — dipanggil dari UI dengan konfirmasi.
     */
    public function delete(int $id): bool
    {
        return DB::table('products')->where('id', $id)->delete() > 0;
    }

    /**
     * @return array<int, array{label: string, value: int}>
     */
    public function getProductTypeOptions(): array
    {
        return $this->options('product_type');
    }

    /**
     * @return array<int, array{label: string, value: int}>
     */
    public function getProductSubTypeOptions(): array
    {
        return $this->options('product_sub_type');
    }

    /**
     * @return array<int, array{label: string, value: int}>
     */
    public function getVendorOptions(): array
    {
        return $this->options('vendor');
    }

    /**
     * Query dasar untuk daftar + detail.
     */
    private function listQuery(?string $search = null): Builder
    {
        return DB::table('products as p')
            ->select([
                'p.id',
                'p.name',
                'p.code',
                'p.unit',
                'p.category',
                'p.price',
                'p.vendor_id',
                'p.product_type_id',
                'p.product_sub_type_id',
                'v.name as vendor',
                'pt.name as type',
                'pst.name as sub_type',
                'p.created_at',
                'p.updated_at',
            ])
            ->leftJoin('product_type as pt', 'pt.id', '=', 'p.product_type_id')
            ->leftJoin('product_sub_type as pst', 'pst.id', '=', 'p.product_sub_type_id')
            ->leftJoin('vendor as v', 'v.id', '=', 'p.vendor_id')
            ->when($search, function ($q, $search) {
                $q->where(function ($w) use ($search) {
                    $w->where('p.name', 'like', "%{$search}%")
                        ->orWhere('p.code', 'like', "%{$search}%")
                        ->orWhere('p.category', 'like', "%{$search}%");
                });
            });
    }

    /**
     * Opsi dropdown untuk tabel referensi. Nama tabel berasal dari allowlist di
     * pemanggil, bukan dari input user.
     *
     * @return array<int, array{label: string, value: int}>
     */
    private function options(string $table): array
    {
        return DB::table($table)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($row) => [
                'label' => (string) $row->name,
                'value' => (int) $row->id,
            ])
            ->all();
    }
}
