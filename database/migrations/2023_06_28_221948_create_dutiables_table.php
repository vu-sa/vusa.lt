<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dutiables', function (Blueprint $table) {
            $table->char('duty_id', 26)->index('duties_users_duty_id_foreign');
            $table->char('dutiable_id', 26)->index('duties_users_user_id_foreign');
            $table->string('dutiable_type');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->longText('extra_attributes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();

            $table->primary(['dutiable_id', 'dutiable_type', 'duty_id', 'start_date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dutiables');
    }
};
