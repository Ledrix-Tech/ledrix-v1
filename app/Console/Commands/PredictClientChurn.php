<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Admin;
use App\Models\Client;
use App\Models\RiskyClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use App\Notifications\RiskyClientNotification;

class PredictClientChurn extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'predict:churn';
    protected $description = 'Predict churn risk for all clients';

    public function handle()
    {
        $clients = Client::with(['orders', 'leads.seller'])->get();

        foreach ($clients as $client) {
            // === Extract Features ===
            $lastOrderDate = $client->orders->max('created_at');
            $totalOrders   = $client->orders->count();
            $unpaidOrders  = $client->orders->where('status', '!=', 'paid')->count();
            $leadResponses = $client->leads->where('status', 'qualified')->count();

            $daysSinceLastOrder = $lastOrderDate
                ? Carbon::parse($lastOrderDate)->diffInDays(Carbon::now())
                : 999;

            // === Risk score ===
            $score = 0.0;

            if ($daysSinceLastOrder > 90) $score += 0.4;
            if ($unpaidOrders > 2)        $score += 0.3;
            if ($totalOrders < 3)         $score += 0.2;
            if ($leadResponses == 0)      $score += 0.1;

            $score = min($score, 1.0);

            if ($score < 0.3) {
                $level = 'low';
            } elseif ($score < 0.6) {
                $level = 'medium';
            } else {
                $level = 'high';
            }

            // Save to DB
            $risky = RiskyClient::updateOrCreate(
                ['client_id' => $client->id],
                [
                    'risk_score' => $score,
                    'risk_level' => $level,
                    'features'   => json_encode([
                        'days_since_last_order' => $daysSinceLastOrder,
                        'total_orders'          => $totalOrders,
                        'unpaid_orders'         => $unpaidOrders,
                        'lead_responses'        => $leadResponses,
                    ]),
                ]
            );

            // 🔥 Send notification immediately when risky client detected
            // (you can change to `if (in_array($level, ['medium','high']))` if you want)
            if (in_array($level, ['medium', 'high'])) {
                // Front seller = latest lead owner (if any)
                $latestLead = $client->leads->sortByDesc('created_at')->first();
                $frontSeller = $latestLead?->seller;

                // Admins
                $admins = Admin::where('role', 'admin')->get();

                if ($frontSeller && $frontSeller->email) {
                    $frontSeller->notify(new RiskyClientNotification($risky));
                }

                if ($admins->isNotEmpty()) {
                    Notification::send($admins, new RiskyClientNotification($risky));
                }
            }
        }

        $this->info('Churn predictions updated & risky clients notified!');
    }


    // public function handle()
    // {
    //     $clients = Client::with(['orders', 'leads'])->get();

    //     foreach ($clients as $client) {
    //         // === Extract Features from orders + leads ===
    //         $lastOrderDate = $client->orders->max('created_at');
    //         $totalOrders   = $client->orders->count();
    //         $unpaidOrders  = $client->orders->where('status', '!=', 'paid')->count();
    //         $leadResponses = $client->leads->where('status', 'qualified')->count();

    //         $daysSinceLastOrder = $lastOrderDate
    //             ? Carbon::parse($lastOrderDate)->diffInDays(Carbon::now())
    //             : 999;

    //         // === Simple Risk Scoring Logic (can be replaced with ML model) ===
    //         $score = 0.0;

    //         if ($daysSinceLastOrder > 90) $score += 0.4;
    //         if ($unpaidOrders > 2)        $score += 0.3;
    //         if ($totalOrders < 3)         $score += 0.2;
    //         if ($leadResponses == 0)      $score += 0.1;

    //         $score = min($score, 1.0);

    //         if ($score < 0.3) {
    //             $level = 'low';
    //         } elseif ($score < 0.6) {
    //             $level = 'medium';
    //         } else {
    //             $level = 'high';
    //         }

    //         // === Save to DB (update or create) ===
    //         RiskyClient::updateOrCreate(
    //             ['client_id' => $client->id],
    //             [
    //                 'risk_score' => $score,
    //                 'risk_level' => $level,
    //                 'features'   => json_encode([
    //                     'days_since_last_order' => $daysSinceLastOrder,
    //                     'total_orders'          => $totalOrders,
    //                     'unpaid_orders'         => $unpaidOrders,
    //                     'lead_responses'        => $leadResponses,
    //                 ]),
    //             ]
    //         );
    //     }

    //     $this->info('Churn predictions updated successfully!');
    // }
}
