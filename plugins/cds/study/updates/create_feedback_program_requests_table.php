<?php namespace Cds\Study\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateFeedbackProgramRequestsTable extends Migration
{
    public function up()
    {
        Schema::create('cds_study_feedback_program_requests', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->timestamps();
            $table->integer('resume_id');
            $table->integer('program_id');
            $table->string('fio');
            $table->integer('edu_level');
            $table->date('birth_date');
            $table->string('phone');
            $table->string('email');
            $table->boolean('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cds_study_feedback_program_requests');
    }
}
