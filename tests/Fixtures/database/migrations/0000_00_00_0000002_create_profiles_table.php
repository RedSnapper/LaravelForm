<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class CreateProfilesTable extends Migration{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('profiles', function ($table) {
            $table->integer('user_id')->unsigned();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->primary(['user_id']);
        });
    }
}