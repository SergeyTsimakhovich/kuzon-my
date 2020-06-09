<?php namespace Cds\Study\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateFormCaptureSectorsTable extends Migration
{
    public function up()
    {
        Schema::create('cds_study_form_capture_sectors', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('form_capture_id');
            $table->integer('sector_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cds_study_form_capture_sectors');
    }
}
