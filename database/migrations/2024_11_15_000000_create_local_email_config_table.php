<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLocalEmailConfigTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('local_email_config')) {
            Schema::create(
                'local_email_config',
                function (Blueprint $t) {
                    $t->integer('service_id')->unsigned()->primary();
                    $t->foreign('service_id')->references('id')->on('service')->onDelete('cascade');
                }
            );
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('local_email_config');
    }
} 