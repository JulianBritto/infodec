<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('query_execution_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamp('executed_at')->useCurrent();
            $table->string('connection', 64)->nullable();
            $table->string('method', 16)->nullable();
            $table->string('path', 255)->nullable();
            $table->string('url', 2048)->nullable();
            $table->string('section', 64)->nullable();
            $table->string('group', 128)->nullable();
            $table->unsignedInteger('time_ms')->default(0);
            $table->longText('sql')->nullable();
            $table->json('bindings')->nullable();
            $table->string('ip', 64)->nullable();
            $table->timestamps();

            $table->index(['executed_at']);
            $table->index(['section']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('query_execution_logs');
    }
};
