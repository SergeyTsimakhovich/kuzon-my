<?php

declare(strict_types=1);

namespace Cds\Study\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class UpdateCommentsTable3 extends Migration
{
    public function up()
    {
        Schema::table('cds_study_comments', function($table)
        {
            $table->string('class', 255)->nullable()->default('\Cds\Study\Models\Article\'::character varying');
        });
    }

    public function down()
    {
        Schema::table('cds_study_comments', function($table)
        {
            $table->dropColumn('class');
        });
    }
}

