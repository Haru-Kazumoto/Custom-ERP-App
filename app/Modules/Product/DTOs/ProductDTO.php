<?php

namespace App\Modules\Product\DTOs;

use Illuminate\Http\Request;

class ProductDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $code,
        public readonly string $unit,
        public readonly string $category,
        public readonly ?float $price,
        public readonly ?int $product_type_id,
        public readonly ?int $product_sub_type_id,
        public readonly ?int $vendor_id,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->string('name')->trim()->toString(),
            code: $request->string('code')->trim()->toString(),
            unit: $request->string('unit')->trim()->toString(),
            category: $request->string('category')->trim()->toString(),
            // Kolom `price` nullable: input kosong dari form berarti "tidak diisi",
            // bukan 0 — jadi tidak boleh diterjemahkan jadi nol.
            price: $request->filled('price') ? (float) $request->input('price') : null,
            product_type_id: $request->filled('product_type_id') ? (int) $request->input('product_type_id') : null,
            product_sub_type_id: $request->filled('product_sub_type_id') ? (int) $request->input('product_sub_type_id') : null,
            vendor_id: $request->filled('vendor_id') ? (int) $request->input('vendor_id') : null,
        );
    }

    /**
     * Bentuk siap-simpan untuk kolom `products`.
     *
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'name' => $this->name,
            'code' => $this->code,
            'unit' => $this->unit,
            'category' => $this->category,
            'price' => $this->price,
            'product_type_id' => $this->product_type_id,
            'product_sub_type_id' => $this->product_sub_type_id,
            'vendor_id' => $this->vendor_id,
        ];
    }
}
