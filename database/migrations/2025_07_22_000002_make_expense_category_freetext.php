<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Skip if expenses table is not created yet (create migration runs later).
        // Also skip on SQLite: ->change() is unsupported and fresh installs already use string.
        if (!Schema::hasTable('expenses') || Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('expenses', function (Blueprint $table) {
            $table->string('category')->change();
        });
    }

    public function down(): void {}
};
