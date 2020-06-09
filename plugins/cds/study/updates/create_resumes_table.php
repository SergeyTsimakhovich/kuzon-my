<?php namespace Cds\Study\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateResumesTable extends Migration
{
    public function up()
    {
        Schema::create('cds_study_resumes', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->timestamps();
            $table->integer('user_id');
            $table->string('sex');
            $table->string('address')->nullable();
            $table->string('series');
            $table->string('number');
            $table->string('issued_by');
            $table->string('code');
            $table->date('issued_date');
            $table->text('edu_level');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cds_study_resumes');
    }
}
