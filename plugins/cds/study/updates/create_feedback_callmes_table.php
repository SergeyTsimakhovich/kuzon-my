<?php namespace Cds\Study\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateFeedbackCallmesTable extends Migration
{
    public function up()
    {
        Schema::create('cds_study_feedback_callmes', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->timestamps();
            $table->string('phone');
            $table->boolean('status')->default('false');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cds_study_feedback_callmes');
    }
}
