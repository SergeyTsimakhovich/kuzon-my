<?php namespace Cds\Study\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateArticleReviewsTable extends Migration
{
    public function up()
    {
        Schema::create('cds_study_article_reviews', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('article_id');
            $table->integer('user_id')->nullable();
            $table->boolean('status');
            $table->string('text')->nullable();
            $table->string('session_token')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('cds_study_article_reviews');
    }
}
