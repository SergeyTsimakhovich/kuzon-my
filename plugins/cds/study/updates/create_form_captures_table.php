<?php namespace Cds\Study\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateFormCapturesTable extends Migration
{
    public function up()
    {
        Schema::create('cds_study_form_captures', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->timestamps();
            $table->string('survey');
            $table->string('answer_no');
            $table->string('answer_yes');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cds_study_form_captures');
    }
}
