<?php namespace Cds\Study\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class UpdateMenusTable2 extends Migration
{
    public function up()
    {
        Schema::table('cds_study_menus', function($table)
        {
            $table->string('type_menu')->comment('Тип меню')->change();
        });
    }

    public function down()
    {
        Schema::table('cds_study_menus', function($table)
        {
            $table->integer('type_menu')->default(0)->comment('Тип меню')->change();
        });
    }
}
