<?php namespace Cds\Study\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class UpdateUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function($table)
        {
            $table->integer('type')->default(0)->comment('Тип пользователя');
            $table->string('midname')->nullable();
            $table->date('birth_date')->nullable();
            $table->boolean('agent')->default(false)->comment('Представитель организации');
            $table->string('name_org')->nullable();
            $table->string('inn', 12)->nullable();;
            $table->string('kpp', 10)->nullable();;
            $table->string('orgn', 15)->nullable();;
            $table->string('phone_org', 15)->nullable();
            $table->boolean('personal')->default(true);
        });
    }

    public function down()
    {
        //
    }
}
