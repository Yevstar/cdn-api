<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDevicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('serial_number', 12);
            $table->string('imei', 20)->nullable();
            $table->string('lan_mac_address', 20)->nullable();
            $table->string('iccid', 30)->nullable();
            $table->boolean('registered')->default(false);
            $table->string('public_ip_sim')->nullable();
            $table->string('sim_status', 20)->nullable();

            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('zone_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('machine_id')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('devices');
    }
}