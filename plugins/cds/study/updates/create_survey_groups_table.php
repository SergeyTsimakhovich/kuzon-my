<?php namespace Cds\Study\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateSurveyGroupsTable extends Migration
{
    public function up()
    {
        Schema::create('cds_study_survey_groups', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->timestamps();
            $table->string('title', 255);
            $table->integer('realm_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cds_study_survey_groups');
    }
}
