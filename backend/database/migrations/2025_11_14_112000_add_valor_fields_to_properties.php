<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('imo_properties', function (Blueprint $table) {
            // Adicionar campos valor_iptu e valor_condominio que faltam na produção
            if (!Schema::hasColumn('imo_properties', 'valor_iptu')) {
                $table->decimal('valor_iptu', 10, 2)->default(0)->after('valor_aluguel');
            }
            
            if (!Schema::hasColumn('imo_properties', 'valor_condominio')) {
                $table->decimal('valor_condominio', 10, 2)->default(0)->after('valor_iptu');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('imo_properties', function (Blueprint $table) {
            $table->dropColumn(['valor_iptu', 'valor_condominio']);
        });
    }
};