<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('endpoint_checks', function (Blueprint $table) {
            $table->enum('status', ['up', 'down', 'error'])->nullable()->after('http_status_code');
            $table->text('error_message')->nullable()->after('status');
        });

        Schema::table('endpoint_checks', function (Blueprint $table) {
            // A signed tinyint (-128..127) cannot hold a real HTTP status
            // code (200, 404, 500, ...).
            $table->unsignedSmallInteger('http_status_code')->nullable()->change();
        });

        // Backfill existing rows: separate our own error text (previously
        // stored in raw_response for a connection failure) from the
        // target's actual response body, and classify each check's outcome.
        DB::table('endpoint_checks')->whereNull('http_status_code')->update([
            'status' => 'error',
            'error_message' => DB::raw('raw_response'),
            'raw_response' => null,
        ]);

        DB::table('endpoint_checks')
            ->whereBetween('http_status_code', [200, 299])
            ->update(['status' => 'up']);

        DB::table('endpoint_checks')
            ->whereNotNull('http_status_code')
            ->where(function ($query) {
                $query->where('http_status_code', '<', 200)->orWhere('http_status_code', '>=', 300);
            })
            ->update(['status' => 'down']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('endpoint_checks', function (Blueprint $table) {
            $table->dropColumn(['status', 'error_message']);
            $table->tinyInteger('http_status_code')->nullable()->change();
        });
    }
};
