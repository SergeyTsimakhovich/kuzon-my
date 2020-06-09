<?php namespace Cds\Study\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateObjectTypeAllowsTable extends Migration
{
    public function up()
    {
        Schema::create('cds_study_object_type_allows', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->integer('object_type_id')->unsigned();
            $table->integer('parameter_id')->unsigned();
            $table->primary(['object_type_id', 'parameter_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('cds_study_object_type_allows');
    }
}
