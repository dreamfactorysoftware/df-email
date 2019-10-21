<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRegionEndpointField extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        if (Schema::hasTable('cloud_email_config') && !Schema::hasColumn('cloud_email_config', 'region_endpoint')) {
            Schema::table('cloud_email_config', function (Blueprint $t) {
                $t->string('region_endpoint')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('cloud_email_config') && Schema::hasColumn('cloud_email_config', 'region_endpoint')) {
            Schema::table('cloud_email_config', function (Blueprint $t) {
                $t->dropColumn('region_endpoint');
            });
        }
    }
}
