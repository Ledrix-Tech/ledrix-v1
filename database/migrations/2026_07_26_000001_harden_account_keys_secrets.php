<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ENCRYPTED_COLUMNS = [
        'stripe_secret_key',
        'stripe_webhook_secret',
        'paypal_secret',
    ];

    public function up(): void
    {
        $duplicateGroups = DB::table('account_keys')
            ->select('brand_id', 'module', DB::raw('COUNT(*) as total'))
            ->whereNotNull('brand_id')
            ->groupBy('brand_id', 'module')
            ->having('total', '>', 1)
            ->get();

        foreach ($duplicateGroups as $group) {
            $keepId = DB::table('account_keys')
                ->where('brand_id', $group->brand_id)
                ->where('module', $group->module)
                ->orderByDesc('id')
                ->value('id');

            DB::table('account_keys')
                ->where('brand_id', $group->brand_id)
                ->where('module', $group->module)
                ->where('id', '!=', $keepId)
                ->delete();
        }

        $indexExists = collect(DB::select("SHOW INDEX FROM account_keys WHERE Key_name = 'account_keys_brand_module_unique'"))->isNotEmpty();

        if (! $indexExists) {
            Schema::table('account_keys', function (Blueprint $table) {
                $table->unique(['brand_id', 'module'], 'account_keys_brand_module_unique');
            });
        }

        // Encrypted payloads exceed 255 chars — widen before encrypting.
        DB::statement('ALTER TABLE account_keys MODIFY stripe_webhook_secret TEXT NULL');
        DB::statement('ALTER TABLE account_keys MODIFY paypal_webhook_id TEXT NULL');

        foreach (DB::table('account_keys')->orderBy('id')->cursor() as $row) {
            $updates = [];

            foreach (self::ENCRYPTED_COLUMNS as $column) {
                $value = $row->{$column};

                if ($value !== null && $value !== '' && ! $this->isEncrypted($value)) {
                    $updates[$column] = Crypt::encryptString($value);
                }
            }

            if ($updates !== []) {
                DB::table('account_keys')->where('id', $row->id)->update($updates);
            }
        }
    }

    public function down(): void
    {
        Schema::table('account_keys', function (Blueprint $table) {
            $table->dropUnique('account_keys_brand_module_unique');
        });
    }

    private function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
};
