<?php namespace Cds\Study\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateNotificationsTable extends Migration
{
    public function up()
    {
        Schema::create('cds_study_notifications', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->timestamps();
            $table->integer('user_id');
            $table->string('type');
            $table->integer('object_id');
            $table->string('object_type');
            $table->string('text')->nullable();
            $table->string('url')->nullable();
            $table->dateTime('read_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('cds_study_notifications');
    }
}
