<?php namespace Cds\Study\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateFeedbackFormCapturesTable extends Migration
{
    public function up()
    {
        Schema::create('cds_study_feedback_form_captures', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->timestamps();
            $table->integer('form_capture_id');
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->boolean('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cds_study_feedback_form_captures');
    }
}
