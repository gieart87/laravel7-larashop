<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameColumnAndAddColumnsInUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->string('name')->nullable()->change();
                $table->renameColumn('name', 'first_name');
                $table->string('last_name')->nullable()->after('name');
                $table->string('phone')->nullable()->after('email');
                $table->string('company')->nullable()->after('remember_token');
                $table->string('address1')->nullable()->after('company');
                $table->string('address2')->nullable()->after('address1');
                $table->integer('province_id')->nullable()->after('address2');
                $table->integer('city_id')->nullable()->after('province_id');
                $table->integer('postcode')->nullable()->after('city_id');
            });
        } else {
            Schema::dropIfExists('users');
            Schema::create('users', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('first_name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
                $table->string('last_name')->nullable();
                $table->string('phone')->nullable();
                $table->string('company')->nullable();
                $table->string('address1')->nullable();
                $table->string('address2')->nullable();
                $table->integer('province_id')->nullable();
                $table->integer('city_id')->nullable();
                $table->integer('postcode')->nullable();
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
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('first_name', 'name');
                $table->dropColumn('last_name');
                $table->dropColumn('phone');
                $table->dropColumn('company');
                $table->dropColumn('address1');
                $table->dropColumn('address2');
                $table->dropColumn('province_id');
                $table->dropColumn('city_id');
                $table->dropColumn('postcode');
            });
        }
    }
}
