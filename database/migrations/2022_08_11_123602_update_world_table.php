<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('word', function (Blueprint $table) {
            $table->string('prsi')->nullable();
            $table->string('prsh')->nullable();
            $table->string('pas')->nullable();
            $table->string('pas2')->nullable();
            $table->text('pasp')->nullable();
            $table->text('pasp2')->nullable();
            $table->string('ing')->nullable();
            $table->string('comparative')->nullable();
            $table->string('superlative')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
