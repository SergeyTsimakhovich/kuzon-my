<?php

namespace Cds\Study\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class update_articles_table_18122019 extends Migration
{
    public function up()
    {
        Schema::table('cds_study_articles', function($table)
        {
            $table->integer('user_id')->nullable();
        });
    }

    public function down()
    {
        Schema::table('cds_study_articles', function($table)
        {
            $table->dropColumn('user_id');
        });
    }
}
