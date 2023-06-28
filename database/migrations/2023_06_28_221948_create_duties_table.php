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
        Schema::create('duties', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->char('institution_id', 26)->index('duties_institution_id_foreign');
            $table->unsignedInteger('order')->default(0)->comment('Order of duty in institution');
            $table->string('email', 255)->nullable()->comment('Commonly the @vusa.lt email address, which is used as the OAuth login. Personal mail is stored in users.email.');
            $table->longText('extra_attributes')->nullable()->comment('For specifying, e.g. study programme.');
            $table->unsignedInteger('places_to_occupy')->nullable()->default(1)->comment('Full number of positions to occupy for this duty');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->softDeletes();

            $table->unique(['name', 'institution_id', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('duties');
    }
};
