<?php namespace Cds\Study\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateActionsTable extends Migration
{
    public function up()
    {
        Schema::create('cds_actions', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('object_type');
            $table->integer('object_id');
            $table->integer('user_id');
            $table->string('action');
            $table->string('session_token');
            $table->integer('value')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('cds_actions');
    }
}

