<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLogTracingsToAuditLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->enum('level', ['debug', 'info', 'warning', 'error', 'critical'])
                ->default('info')
                ->after('id');

            $table->text('message')->nullable()->after('user_type');

            $table->string('action')->nullable()->change();

            $table->string('user_type')->nullable()->change();

            $table->char('trace_id', 36)->nullable()->after('id');

            // Add index for quick lookup
            $table->index('trace_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn('level');
            $table->dropColumn('message');

            $table->string('action')->nullable(false)->change();
            $table->string('user_type')->nullable(false)->change();

            $table->dropIndex(['trace_id']);
            $table->dropColumn('trace_id');
        });
    }
}
