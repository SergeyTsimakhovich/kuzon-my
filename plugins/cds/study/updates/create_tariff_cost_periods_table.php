<?php namespace Cds\Study\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateTariffCostPeriodsTable extends Migration
{
    public function up()
    {
        Schema::create('cds_study_tariff_cost_periods', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->timestamps();
            $table->integer('tariff_id');
            $table->integer('cost')->default(0);
            $table->integer('period')->default(0);
        });
    }

    public function down()
    {
        Schema::dropIfExists('cds_study_tariff_cost_periods');
    }
}
