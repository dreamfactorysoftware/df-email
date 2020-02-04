<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmailTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('smtp_config')) {
            //Email service config table
            Schema::create(
                'smtp_config',
                function (Blueprint $t) {
                    $t->integer('service_id')->unsigned()->primary();
                    $t->foreign('service_id')->references('id')->on('service')->onDelete('cascade');
                    $t->string('host');
                    $t->string('port')->default('587');
                    $t->string('encryption')->nullable();
                    $t->text('username')->nullable(); //encrypted
                    $t->text('password')->nullable(); //encrypted
                }
            );
        }

        //Email service config table
        if (!Schema::hasTable('cloud_email_config')) {
            Schema::create(
                'cloud_email_config',
                function (Blueprint $t) {
                    $t->integer('service_id')->unsigned()->primary();
                    $t->foreign('service_id')->references('id')->on('service')->onDelete('cascade');
                    $t->string('domain')->nullable();
                    $t->text('key'); //encrypted
                }
            );
        }

        //Email service parameters config table
        if (!Schema::hasTable('email_parameters_config')) {
            Schema::create(
                'email_parameters_config',
                function (Blueprint $t) {
                    $t->increments('id');
                    $t->integer('service_id')->unsigned();
                    $t->foreign('service_id')->references('id')->on('service')->onDelete('cascade');
                    $t->string('name');
                    $t->mediumText('value')->nullable();
                    $t->boolean('active')->default(1);
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
        // Drop created tables in reverse order

        //Email service config table
        Schema::dropIfExists('cloud_email_config');
        Schema::dropIfExists('smtp_config');
        //Email service parameters config table
        Schema::dropIfExists('email_parameters_config');
    }
}
