<?php namespace Cds\Study\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateObjectTypesTable extends Migration
{
    public function up()
    {
        Schema::create('cds_study_object_types', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->timestamps();
            $table->string('name',255);
        });
    }

    public function down()
    {
        Schema::dropIfExists('cds_study_object_types');
    }
}
