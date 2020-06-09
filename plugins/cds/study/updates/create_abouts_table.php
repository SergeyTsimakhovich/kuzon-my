<?php namespace Cds\Study\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateAboutsTable extends Migration
{
    public function up()
    {
        Schema::create('cds_study_abouts', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');

            $table->string('name');
            $table->string('code');
            $table->text('teaser')->nullable();
            $table->text('body')->nullable();
            $table->text('list')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('cds_study_abouts');
    }
}
