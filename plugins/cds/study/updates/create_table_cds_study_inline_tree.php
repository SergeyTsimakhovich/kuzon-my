<?php namespace Cds\Study\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateInlineTreeTable extends Migration
{
    public function up()
    {
        Schema::create('cds_study_inline_tree', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('sector_id');
            $table->integer('child');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cds_study_inline_tree');
    }
}