<?php namespace Cds\Study\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateBillsTable extends Migration
{
    public function up()
    {
        Schema::create('cds_study_bills', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->timestamps();
            $table->integer('user_id');
            $table->string('object_type');
            $table->integer('object_id');
            $table->integer('tariff_id');
            $table->integer('cost');
            $table->date('date_start');
            $table->date('date_end');
            $table->integer('bill_property');
            $table->boolean('status')->default(false);
        });
    }

    public function down()
    {
        Schema::dropIfExists('cds_study_bills');
    }
}
