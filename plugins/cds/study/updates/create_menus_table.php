<?php namespace Cds\Study\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateMenusTable extends Migration
{
    public function up()
    {
        Schema::create('cds_study_menus', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->timestamps();
            $table->string('title')->comment('Наименвание пункта меню');
            $table->text('description')->comment('Описание пункта меню');
            $table->text('seo_description')->comment('Описание пункта меню для СЕО');
            $table->integer('sort_order')->default(0)->comment('Сортировка');
            $table->boolean('active')->comment('Показывать пункт меню');
            $table->string('slug')->comment('ЧПУ наименования');
            $table->boolean('agent_only')->default(false)->comment('Только для прдставителей организации');
            $table->text('custom_data')->comment('Дополнительный атрибут для разметки');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cds_study_menus');
    }
}
