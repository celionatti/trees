<?php

use Trees\Database\Migration;
use Trees\Database\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('posts', function (Schema $table) {
            $table->id();
            $table->bigInteger('user_id')->notNullable();
            $table->string('title', 200)->notNullable();
            $table->string('slug', 200)->notNullable()->unique();
            $table->text('content')->notNullable();
            $table->string('status', 20)->default('draft');
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('slug');
            $table->index('status');
            
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }
    
    public function down(): void
    {
        $this->dropTable('posts');
    }
};