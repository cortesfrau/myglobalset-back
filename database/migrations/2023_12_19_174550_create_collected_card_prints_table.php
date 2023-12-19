<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCollectedCardPrintsTable extends Migration
{

    public function up()
    {
        Schema::create('collected_card_prints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained('collections');
            $table->string('scryfall_id');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('collected_card_prints');
    }

}
