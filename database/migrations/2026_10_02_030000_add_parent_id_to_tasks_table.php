<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('tasks')
                ->cascadeOnDelete();

            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['parent_id']);
            $table->dropConstrainedForeignId('parent_id');
        });
    }
};
