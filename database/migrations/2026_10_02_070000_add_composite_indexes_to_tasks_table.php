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
            $table->index(['user_id', 'created_at', 'id'], 'tasks_user_created_id_index');
            $table->index(['user_id', 'priority'], 'tasks_user_priority_index');
            $table->index(['user_id', 'project_id'], 'tasks_user_project_index');
            $table->index(['user_id', 'category_id'], 'tasks_user_category_index');
            $table->index(['user_id', 'parent_id'], 'tasks_user_parent_index');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('tasks_user_created_id_index');
            $table->dropIndex('tasks_user_priority_index');
            $table->dropIndex('tasks_user_project_index');
            $table->dropIndex('tasks_user_category_index');
            $table->dropIndex('tasks_user_parent_index');
        });
    }
};
