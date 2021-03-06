<?php namespace Cds\Study\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateSurveyGroupRealmsTable extends Migration
{
    public function up()
    {
        Schema::create('cds_study_survey_group_realms', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->timestamps();
            $table->integer('survey_group_id');
            $table->integer('realm_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cds_study_survey_group_realms');
    }
}
