<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voyages', function (Blueprint $table) {
            $table->foreignId('chauffeur_id')->nullable()->after('autocar_id')->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('retard_minutes')->default(0)->after('prix');
            $table->string('motif_retard')->nullable()->after('retard_minutes');
        });

        // Add special permission for the dedicated chauffeur view
        $chauffeurPerm = Permission::firstOrCreate(
            ['name' => 'chauffeur.view'],
            ['slug' => 'chauffeur-view']
        );
        $voyagesRead = Permission::where('name', 'voyages.read')->first();

        // Ready-made Chauffeur roles
        $chauffeurRole = Role::firstOrCreate(
            ['slug' => 'chauffeur'],
            ['name' => 'Chauffeur']
        );
        $chauffeurRole->permissions()->syncWithoutDetaching(
            array_filter([$chauffeurPerm->id, $voyagesRead?->id])
        );

        $compagnieChauffeurRole = Role::firstOrCreate(
            ['slug' => 'compagnie-chauffeur'],
            ['name' => 'Compagnie – Chauffeur']
        );
        $compagnieChauffeurRole->permissions()->syncWithoutDetaching([$chauffeurPerm->id]);
    }

    public function down(): void
    {
        Schema::table('voyages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('chauffeur_id');
            $table->dropColumn(['retard_minutes', 'motif_retard']);
        });

        Role::whereIn('slug', ['chauffeur', 'compagnie-chauffeur'])->delete();
        Permission::where('name', 'chauffeur.view')->delete();
    }
};
