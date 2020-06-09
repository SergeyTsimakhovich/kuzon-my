<?php namespace Cds\Study\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateArticlesTable extends Migration
{
    public function up()
    {
        Schema::create('cds_study_articles', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->timestamps();
            $table->timestamp('published_at')->nullable();
            $table->boolean('published')->default('false');
            $table->string('author')->nullable();
            $table->integer('viewed')->default(0);
            $table->string('title', 255);
            $table->string('description', 512)->nullable();
            $table->string('source', 255)->nullable();
            $table->text('body');
            $table->index('author');
            $table->index('published_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cds_study_articles');
    }
}
