<?php

namespace Cds\Study\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class UpdateArticlesTable extends Migration
{
    public function up()
    {
        Schema::table('cds_study_articles', function($table)
        {
            $table->integer('viewed')->nullable()->default(0)->change();
        });
    }

    public function down()
    {
        Schema::table('cds_study_articles', function($table)
        {
        });
    }
}