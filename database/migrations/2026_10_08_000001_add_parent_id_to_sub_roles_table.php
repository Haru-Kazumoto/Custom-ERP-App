<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hierarki sub-role dinamis lewat `parent_id` self-reference.
     *
     * Rantai approval DO berjalan naik mengikuti parent ini (salesman ->
     * sales supervisor -> sales manager), jadi perubahan struktur organisasi
     * cukup lewat data -- tanpa kode. Seed di bawah mengisi hierarki sales
     * yang sudah disepakati; sub-role lain (marketing dnp/dku) sengaja null:
     * di role itu tidak ada tingkatan.
     */
    public function up(): void
    {
        Schema::table('sub_roles', function (Blueprint $table) {
            $table->foreignId('parent_id')
                ->nullable()
                ->after('role_id')
                ->constrained('sub_roles')
                ->nullOnDelete();
        });

        // Berdasarkan `code`, bukan id: aman kalau urutan insert sub_roles
        // berbeda di lingkungan lain.
        $parents = [
            'salesman' => 'sales_supervisor',
            'sales_supervisor' => 'sales_manager',
        ];

        $ids = DB::table('sub_roles')->pluck('id', 'code');

        foreach ($parents as $code => $parentCode) {
            if (! isset($ids[$code], $ids[$parentCode])) {
                continue;
            }

            DB::table('sub_roles')->where('id', $ids[$code])->update([
                'parent_id' => $ids[$parentCode],
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('sub_roles')->update(['parent_id' => null]);

        Schema::table('sub_roles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
        });
    }
};
