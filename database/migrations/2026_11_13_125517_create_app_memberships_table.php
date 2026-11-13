<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_memberships', function (Blueprint $table) {
            $table->id('app_membership_id');

            $table->foreignUuid('auth_id')
                ->constrained('accounts_users', 'auth_id')
                ->restrictOnDelete();

            $table->foreignId('app_id')
                ->constrained('apps', 'app_id')
                ->restrictOnDelete();

            $table->string('status', 30)
                ->default('pending');

            $table->timestamp('registered_at')->nullable();

            $table->timestamps();

            $table->unique(
                ['auth_id', 'app_id'],
                'app_memberships_user_app_unique'
            );

            $table->index(
                ['app_id', 'status'],
                'app_memberships_app_status_index'
            );
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                'ALTER TABLE public.app_memberships ENABLE ROW LEVEL SECURITY'
            );

            DB::statement(<<<'SQL'
ALTER TABLE public.app_memberships
ADD CONSTRAINT app_memberships_status_check
CHECK (status IN ('pending', 'active', 'suspended', 'removed'))
SQL);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('app_memberships');
    }
};
