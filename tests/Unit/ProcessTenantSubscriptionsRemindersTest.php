<?php

namespace Tests\Unit;

use App\Mail\TenantSubscriptionRenewalReminderMail;
use App\Models\Central\PackagePricing;
use App\Models\Central\Tenant;
use App\Models\Central\TenantMembership;
use App\Services\Tenant\ProcessTenantSubscriptionsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\Support\UsesSqliteCentral;
use Tests\TestCase;

class ProcessTenantSubscriptionsRemindersTest extends TestCase
{
    use RefreshDatabase;
    use UsesSqliteCentral;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootSqliteCentral();
        $this->ensureReminderColumns();
        config(['subscription.renewal_reminder_days' => [7, 3, 1]]);
    }

    public function test_sends_seven_three_and_one_day_renewal_reminders(): void
    {
        Mail::fake();

        $plan = PackagePricing::query()->create([
            'name'          => 'Agency',
            'slug'          => 'agency-'.uniqid(),
            'monthly_price' => 99,
            'yearly_price'  => 990,
            'currency'      => 'USD',
            'trial_days'    => 0,
            'is_popular'    => false,
            'is_public'     => true,
            'sort_order'    => 1,
            'status'        => 'active',
        ]);

        $cases = [
            7 => 'renewal_reminder_7d_sent_at',
            3 => 'renewal_reminder_3d_sent_at',
            1 => 'renewal_reminder_1d_sent_at',
        ];

        foreach ($cases as $days => $column) {
            $tenant = Tenant::query()->create([
                'plan_id'  => $plan->id,
                'name'     => "T{$days}",
                'slug'     => 't'.$days.'-'.uniqid(),
                'email'    => "t{$days}-".uniqid().'@example.com',
                'password' => Hash::make('password'),
                'status'   => 'active',
            ]);

            TenantMembership::query()->create([
                'tenant_id'     => $tenant->id,
                'plan_id'       => $plan->id,
                'billing_cycle' => 'monthly',
                'amount'        => 99,
                'currency'      => 'USD',
                'api_key'       => 'key_'.$days.'_'.uniqid(),
                'start_date'    => now()->subMonth()->toDateString(),
                'end_date'      => now()->addDays($days)->toDateString(),
                'status'        => 'active',
                'renewed_by'    => 'tenant',
            ]);
        }

        $stats = app(ProcessTenantSubscriptionsService::class)->run();

        $this->assertSame(1, $stats['reminders_7d']);
        $this->assertSame(1, $stats['reminders_3d']);
        $this->assertSame(1, $stats['reminders_1d']);

        Mail::assertSent(TenantSubscriptionRenewalReminderMail::class, 3);
    }

    private function ensureReminderColumns(): void
    {
        if (! Schema::connection('central')->hasTable('tenant_memberships')) {
            Schema::connection('central')->create('tenant_memberships', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('plan_id');
                $table->string('billing_cycle')->default('monthly');
                $table->decimal('amount', 10, 2)->default(0);
                $table->string('currency', 3)->default('USD');
                $table->string('api_key', 64)->unique();
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->string('status')->default('active');
                $table->string('renewed_by')->default('tenant');
                $table->timestamp('renewal_reminder_7d_sent_at')->nullable();
                $table->timestamp('renewal_reminder_3d_sent_at')->nullable();
                $table->timestamp('renewal_reminder_1d_sent_at')->nullable();
                $table->timestamp('renewal_expired_notice_sent_at')->nullable();
                $table->timestamps();
            });

            return;
        }

        foreach (['renewal_reminder_7d_sent_at', 'renewal_reminder_3d_sent_at', 'renewal_reminder_1d_sent_at', 'renewal_expired_notice_sent_at'] as $col) {
            if (! Schema::connection('central')->hasColumn('tenant_memberships', $col)) {
                Schema::connection('central')->table('tenant_memberships', function (Blueprint $table) use ($col) {
                    $table->timestamp($col)->nullable();
                });
            }
        }
    }
}
