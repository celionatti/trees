<?php

use Trees\Database\Migration;
use Trees\Database\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('users', function (Schema $table) {
            $table->id();
            $table->string('name', 100)->notNullable();
            $table->string('email', 150)->notNullable()->unique();
            $table->string('password')->notNullable();
            $table->string('remember_token', 100)->nullable();
            $table->timestamps();
            
            $table->index('email');
        });
    }
    
    public function down(): void
    {
        $this->dropTable('users');
    }
};