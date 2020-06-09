<?php namespace Cds\Study\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateBillPropertiesTable extends Migration
{
    public function up()
    {
        Schema::create('cds_study_bill_properties', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->timestamps();
            $table->integer('user_id');
            $table->string('name_organization');
            $table->string('inn', 12);
            $table->string('kpp', 10);
        });
    }

    public function down()
    {
        Schema::dropIfExists('cds_study_bill_properties');
    }
}
