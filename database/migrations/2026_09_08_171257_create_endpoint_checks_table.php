<?php

use App\Models\Endpoint;
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
        Schema::create('endpoint_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Endpoint::class);
            $table->timestamp('checked_at')->nullable();
            $table->tinyInteger('http_status_code')->nullable();
            $table->text('raw_response')->nullable();
        });

        Schema::table('endpoints', function (Blueprint $table) {
            $table->timestamp('last_run_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('endpoint_checks');
        Schema::dropColumns('endpoints', 'last_run_at');
    }
};
