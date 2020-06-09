<?php namespace Cds\Study\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateSurveyAnswersTable extends Migration
{
    public function up()
    {
        Schema::create('cds_study_survey_answers', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->timestamps();
            $table->string('name', 255);
            $table->integer('survey_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cds_study_survey_answers');
    }
}
