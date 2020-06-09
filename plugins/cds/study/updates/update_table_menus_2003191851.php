<?php namespace Cds\Study\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class UpdateMenusTable extends Migration
{
    public function up()
    {
        Schema::table('cds_study_menus', function($table)
        {
            $table->integer('parent_id')->nullable()->comment('ID родительского пунтка меню');
            $table->integer('type_menu')->default(0)->comment('Тип меню');
        });
    }

    public function down()
    {
        Schema::table('cds_study_menus', function($table)
        {
            $table->dropColumn('parent_id');
            $table->dropColumn('type_menu');
        });
    }
}
