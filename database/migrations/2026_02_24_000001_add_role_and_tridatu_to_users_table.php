<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRoleAndTridatuToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->unique()->nullable()->after('name');
            $table->enum('role', ['super_admin', 'admin', 'operator', 'viewer'])->default('operator')->after('password');
            $table->boolean('is_active')->default(true)->after('role');
            // Link ke ID user di sistem Tridatu Netmon (untuk sinkronisasi identitas)
            $table->string('tridatu_user_id')->unique()->nullable()->after('is_active');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'role', 'is_active', 'tridatu_user_id']);
        });
    }
}
