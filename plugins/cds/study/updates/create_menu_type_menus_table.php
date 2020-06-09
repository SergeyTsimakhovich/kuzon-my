<?php namespace Cds\Study\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateMenuTypeMenusTable extends Migration
{
    public function up()
    {
        Schema::create('cds_study_menu_type_menus', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->integer('menu_id')->unsigned();
            $table->integer('menu_type_id')->unsigned();
            $table->primary(['menu_id', 'menu_type_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('cds_study_menu_type_menus');
    }
}
