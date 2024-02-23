<?php

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
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->foreignId('author_id')->constrained()->onUpdate('CASCADE')->onDelete('RESTRICT');
            $table->tinyText('short_description')->nullable();
            $table->text('description')->nullable();
            $table->string('cover_image')->nullable();
            $table->boolean('status')->default(1);
            $table->foreignId('created_by')->constrained(table: 'users')->onUpdate('CASCADE')->onDelete('RESTRICT');
            $table->foreignId('updated_by')->constrained(table: 'users')->onUpdate('CASCADE')->onDelete('RESTRICT');
            $table->timestamps();
        });

        Schema::create('book_genre', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->onUpdate('CASCADE')->onDelete('RESTRICT');
            $table->foreignId('genre_id')->constrained()->onUpdate('CASCADE')->onDelete('RESTRICT');
            $table->foreignId('created_by')->constrained(table: 'users')->onUpdate('CASCADE')->onDelete('RESTRICT');
            $table->foreignId('updated_by')->constrained(table: 'users')->onUpdate('CASCADE')->onDelete('RESTRICT');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('book_genre');
        Schema::dropIfExists('books');
    }
};
