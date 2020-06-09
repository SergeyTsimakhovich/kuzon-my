<?php namespace Cds\Study\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateBannerGroupsTable extends Migration
{
    public function up()
    {
        Schema::create('cds_study_banner_groups', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->timestamps();
            $table->boolean('published')->default(true);
            $table->string('title', 100);
            $table->string('width', 20)->default('0');
            $table->string('height', 20)->default('0');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cds_study_banner_groups');
    }
}
