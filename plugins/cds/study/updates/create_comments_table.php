<?php namespace Cds\Study\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateCommentsTable extends Migration
{
    public function up()
    {
        Schema::create('cds_study_comments', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->timestamps();
            $table->bigInteger('article_id');
            $table->bigInteger('user_id')->default('0')->nullable();
            $table->bigInteger('parent_id')->nullable();
            $table->text('body');
            $table->index('article_id');
            $table->index('user_id');
            $table->index('parent_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cds_study_comments');
    }
}
