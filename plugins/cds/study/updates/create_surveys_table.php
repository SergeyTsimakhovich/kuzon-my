<?php namespace Cds\Study\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateSurveysTable extends Migration
{
    public function up()
    {
        Schema::create('cds_study_surveys', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->timestamps();
            $table->string('name', 255);
            $table->integer('survey_group_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cds_study_surveys');
    }
}
