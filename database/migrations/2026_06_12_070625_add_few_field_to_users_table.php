<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->after('name');
            $table->string('emp_id')->after('username');
            
            $table->foreignId('role_id')->after('emp_id')->constrained('roles')->onDelete('cascade');
            $table->foreignId('sub_role_id')->after('role_id')->constrained('sub_roles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
            $table->dropForeign(['sub_role_id']);
            $table->dropColumn('sub_role_id');
            $table->dropColumn('emp_id');
            $table->dropColumn('username');
        });
    }
};
