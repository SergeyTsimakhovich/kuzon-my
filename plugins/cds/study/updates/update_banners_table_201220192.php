<?php

declare(strict_types=1);

namespace Cds\Study\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class UpdateBannersTable extends Migration
{
    public function up()
    {
        Schema::table('cds_study_banners', function($table)
        {
            $table->string('image_link', 255)->default('');
        });
    }

    public function down()
    {
        Schema::table('cds_study_banners', function($table)
        {
            $table->dropColumn('image_link');
        });
    }
}