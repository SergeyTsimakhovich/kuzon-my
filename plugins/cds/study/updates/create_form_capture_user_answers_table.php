<?php namespace Cds\Study\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateFormCaptureUserAnswersTable extends Migration
{
    public function up()
    {
        Schema::create('cds_study_form_capture_user_answers', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->timestamps();
            $table->integer('form_capture_id');
            $table->string('token');
            $table->string('link');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cds_study_form_capture_user_answers');
    }
}
