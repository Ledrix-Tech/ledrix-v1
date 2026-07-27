<?php



namespace App\Console\Commands;



use App\Models\Central\Tenant;

use App\Services\Billing\CreateSubscriptionInvoiceService;

use Illuminate\Console\Command;

use RuntimeException;



class IssueTenantSubscriptionInvoiceCommand extends Command

{

    protected $signature = 'tenants:issue-subscription-invoice {tenant : Tenant ID or email}';



    protected $description = 'Manually issue a JazzCash subscription invoice (for testing or support)';



    public function handle(CreateSubscriptionInvoiceService $invoiceService): int

    {

        $tenant = Tenant::query()

            ->when(

                is_numeric($this->argument('tenant')),

                fn ($q) => $q->where('id', (int) $this->argument('tenant')),

                fn ($q) => $q->where('email', $this->argument('tenant')),

            )

            ->first();



        if (! $tenant) {

            $this->error('Tenant not found.');



            return self::FAILURE;

        }



        try {

            $result = $invoiceService->createAndNotify($tenant, 'jazzcash', 'PKR', 'new');

        } catch (RuntimeException $e) {

            $this->error($e->getMessage());



            return self::FAILURE;

        }



        $payment = $result['payment'];

        $invoice = $result['invoice'];



        $this->info('JazzCash subscription invoice issued.');

        $this->line('Tenant: ' . $tenant->name . ' (' . $tenant->email . ')');

        $this->line('Invoice: ' . $invoice->invoice_number);

        $this->line('Reference: ' . $payment->transaction_id);

        $this->line('Amount: PKR ' . number_format((float) $payment->amount, 0));

        $this->line('Billing URL: ' . route('tenant.billing'));



        return self::SUCCESS;

    }

}


