<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SetUpSettingsModule extends Migration
{
    public function up()
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->morphs('settable');
            $table->string('class');
            $table->json('payload');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['class', 'settable_type', 'settable_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('settings');
    }
}
