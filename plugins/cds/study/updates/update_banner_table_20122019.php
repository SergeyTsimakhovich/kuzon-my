<?php

declare(strict_types=1);

namespace Cds\Study\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class UpdateArticlesTable extends Migration
{
    public function up()
    {
        Schema::table('cds_study_banners', function($table)
        {
            $table->string('link')->nullable()->default('');
            $table->boolean('new_window')->default(false);
        });
    }

    public function down()
    {
        Schema::table('cds_study_banners', function($table)
        {
            $table->dropColumn('link');
            $table->dropColumn('new_window');
        });
    }
}