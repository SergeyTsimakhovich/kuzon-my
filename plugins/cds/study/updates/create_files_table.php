<?php namespace Cds\Study\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateFilesTable extends Migration
{
    public function up()
    {
        Schema::create('cds_study_files', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            
            $table->increments('id');
            $table->string('disk_name');
            $table->string('file_name');
            $table->integer('file_size');
            $table->string('content_type');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('field')->nullable();
            $table->string('attachment_id')->nullable();
            $table->string('attachment_type')->nullable();
            $table->boolean('is_public');
            $table->integer('sort_order');

            //поля для видео
            $table->boolean('is_video')->nullable();
            $table->string('url')->nullable();
            $table->string('embed')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('cds_study_files');
    }
}
