<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('company_name')->default('')->after('id');
            $table->string('company_id')->nullable()->after('company_name');
            $table->string('vat_id')->nullable()->after('company_id');
            $table->string('first_name')->nullable()->after('vat_id');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('phone_number')->nullable()->after('email');
            $table->string('street')->default('')->after('phone_number');
            $table->string('city')->default('')->after('street');
            $table->string('zip')->default('')->after('city');
            $table->char('country_code', 2)->default('CZ')->after('zip');
        });

        DB::table('customers')->orderBy('id')->chunkById(100, function ($customers) {
            foreach ($customers as $customer) {
                DB::table('customers')->where('id', $customer->id)->update([
                    'company_name' => $customer->name ?? '',
                    'company_id' => 'LEGACY-' . $customer->id,
                    'phone_number' => $customer->phone,
                    'street' => $customer->address ?? '',
                    'city' => '',
                    'zip' => '',
                    'country_code' => 'CZ',
                ]);
            }
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('company_id')->nullable(false)->change();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['name', 'phone', 'address']);
            $table->dropUnique('customers_email_unique');
            $table->unique('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('name')->default('')->after('id');
            $table->string('phone')->nullable()->after('email');
            $table->text('address')->nullable()->after('phone');
        });

        DB::table('customers')->orderBy('id')->chunkById(100, function ($customers) {
            foreach ($customers as $customer) {
                $fullName = trim(implode(' ', array_filter([
                    $customer->first_name,
                    $customer->last_name,
                ])));

                DB::table('customers')->where('id', $customer->id)->update([
                    'name' => $fullName !== '' ? $fullName : ($customer->company_name ?? ''),
                    'phone' => $customer->phone_number,
                    'address' => $customer->street ?: null,
                ]);
            }
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique('customers_company_id_unique');
            $table->dropColumn([
                'company_name',
                'company_id',
                'vat_id',
                'first_name',
                'last_name',
                'phone_number',
                'street',
                'city',
                'zip',
                'country_code',
            ]);
            $table->unique('email');
        });
    }
};
