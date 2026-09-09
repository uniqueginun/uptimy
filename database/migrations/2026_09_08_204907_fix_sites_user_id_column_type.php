<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * `sites.user_id` was created as a ULID column (`foreignUlidFor`), but
     * `users.id` is an auto-incrementing integer — `User` never used ULID
     * primary keys. Nothing enforced the mismatch (no foreign key was ever
     * added), so it silently stored the user's integer id as a string
     * instead of a real foreign key.
     */
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->change();
        });

        Schema::table('sites', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('sites', function (Blueprint $table) {
            $table->char('user_id', 26)->change();
        });
    }
};
