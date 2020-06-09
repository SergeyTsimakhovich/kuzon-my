<?php namespace Cds\Study\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateSurveyAnswerUsersTable extends Migration
{
    public function up()
    {
        Schema::create('cds_study_survey_answer_users', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->timestamps();
            $table->integer('user_id');
            $table->integer('survey_answer_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cds_study_survey_answer_users');
    }
}
