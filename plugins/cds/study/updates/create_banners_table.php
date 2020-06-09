<?php namespace Cds\Study\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateBannersTable extends Migration
{
    public function up()
    {
        Schema::create('cds_study_banners', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->timestamps();
            $table->boolean('published')->default(false);
            $table->string('title', 255);
            $table->integer('group_id');
            $table->timestamp('date_start')->default('NOW()');
            $table->timestamp('date_end')->default('NOW()');
            $table->index('group_id', 'group_id_index');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cds_study_banners');
    }
}
