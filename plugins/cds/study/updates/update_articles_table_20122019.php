<?php

declare(strict_types=1);

namespace Cds\Study\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class update_articles_table_20122019 extends Migration
{
    public function up()
    {
        Schema::table('cds_study_articles', function($table)
        {
            $table->index('author', 'author_index');
            $table->index('published_at', 'published_at_index');
        });
    }

    public function down()
    {
        Schema::table('cds_study_articles', function($table)
        {

        });
    }
}
