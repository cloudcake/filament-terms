<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(\Cloudcake\Terms\Terminology::getTableName(), function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('panel_id')->nullable()->index();
            $table->string('tenant_id')->nullable();
            $table->json('terms')->nullable();
            $table->dateTimes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(\Cloudcake\Terms\Terminology::getTableName());
    }
};
