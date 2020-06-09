<?php

declare(strict_types=1);

namespace Cds\Study\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class UpdateCommentsTable1 extends Migration
{
    public function up()
    {
        Schema::table('cds_study_comments', function($table)
        {
            $table->dropColumn('article_id');
            $table->bigInteger('owner_id')->default(0);
        });
    }

    public function down()
    {
        Schema::table('cds_study_comments', function($table)
        {
            $table->bigInteger('article_id')->default(0);
            $table->dropColumn('owner_id');
        });
    }
}

