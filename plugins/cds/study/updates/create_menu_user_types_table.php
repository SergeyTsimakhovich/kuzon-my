<?php namespace Cds\Study\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateMenuUserTypesTable extends Migration
{
    public function up()
    {
        Schema::create('menu_user_type', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->integer('menu_id')->unsigned();
            $table->integer('user_type_id')->unsigned();
            $table->primary(['menu_id', 'user_type_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('menu_user_type');
    }
}
