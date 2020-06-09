<?php

declare(strict_types=1);

namespace Cds\Study\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class UpdateCommentsTable2 extends Migration
{
    public function up()
    {
        Schema::table('cds_study_comments', function($table)
        {
            $table->integer('status')->default(0);
        });
    }

    public function down()
    {
        Schema::table('cds_study_comments', function($table)
        {
            $table->dropColumn('status');
        });
    }
}

