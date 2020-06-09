<?php namespace Cds\Study\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateObjectValuesTable extends Migration
{
    public function up()
    {
        Schema::create('cds_study_object_values', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->timestamps();
            $table->integer('object_type_id');
            $table->integer('object_id');
            $table->integer('parameter_id');
            $table->string('value',255);
        });
    }

    public function down()
    {
        Schema::dropIfExists('cds_study_object_values');
    }
}
