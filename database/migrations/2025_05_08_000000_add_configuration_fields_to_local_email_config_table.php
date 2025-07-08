<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddConfigurationFieldsToLocalEmailConfigTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('local_email_config') && !Schema::hasColumn('local_email_config', 'command')) {
            Schema::table('local_email_config', function (Blueprint $t) {
                $t->string('command')->nullable()->default('/usr/sbin/sendmail -bs');
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
        if (Schema::hasTable('local_email_config') && Schema::hasColumn('local_email_config', 'command')) {
            Schema::table('local_email_config', function (Blueprint $t) {
                $t->dropColumn([
                    'command'
                ]);
            });
        }
    }
}
