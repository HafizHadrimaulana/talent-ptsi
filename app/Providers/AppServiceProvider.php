<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth; 
use App\Models\RecruitmentRequest;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind interface -> implementation, passing the logger as required by HttpSitmsClient
        $this->app->bind(\App\Services\SITMS\SitmsClient::class, function ($app) {
            /** @var LoggerInterface $logger */
            $logger = $app->make(LoggerInterface::class);

            if (config('sitms.read_enabled')) {
                // HttpSitmsClient constructor expects $log
                return new \App\Services\SITMS\HttpSitmsClient($logger);
            }

            return new \App\Services\SITMS\NullSitmsClient();
        });
    }

    public function boot(): void
    {
        // --- NOTIFIKASI GLOBAL (IZIN PRINSIP, TRAINING, DLL) ---
        View::composer('layouts.app', function ($view) {
            $allNotifications = collect();

            if (Auth::check()) {
                try {
                    $me = Auth::user();
                    
                    // --- NOTIFIKASI IZIN PRINSIP ---
                    $approverRoles = ['Kepala Unit', 'DHC', 'AVP Human Capital Operation', 'VP Human Capital', 'Dir SDM', 'Superadmin'];
                    
                    if ($me->hasAnyRole($approverRoles)) {
                        $query = RecruitmentRequest::with(['approvals', 'unit'])
                            ->whereIn('status', ['submitted', 'in_review']);

                        if ($me->hasRole('Kepala Unit') && !$me->hasRole('Superadmin')) {
                            $query->where('unit_id', $me->unit_id);
                        }

                        $requests = $query->get();
                        foreach ($requests as $req) {
                            $currentApproval = $req->approvals->sortBy('id')->where('status', 'pending')->first();

                            if ($currentApproval) {
                                $allApprovals = $req->approvals->sortBy('id')->values();
                                $stageIndex = $allApprovals->search(fn($item) => $item->id === $currentApproval->id);
                                $shouldShow = false;

                                if ($me->hasRole('Superadmin')) $shouldShow = true;
                                else if ($stageIndex === 0 && $me->hasRole('Kepala Unit') && (string)$me->unit_id === (string)$req->unit_id) $shouldShow = true;
                                else if ($stageIndex === 1 && $me->hasRole('DHC')) $shouldShow = true;
                                else if ($stageIndex === 2 && ($me->hasRole('AVP Human Capital Operation') || $me->job_title == 'AVP Human Capital Operation')) $shouldShow = true;
                                else if ($stageIndex === 3 && ($me->hasRole('VP Human Capital') || $me->job_title == 'VP Human Capital')) $shouldShow = true;
                                else if ($stageIndex === 4 && $me->hasRole('Dir SDM')) $shouldShow = true;

                                if ($shouldShow) {
                                    // struktur data notifikasi
                                    $allNotifications->push((object)[
                                        'id' => $req->id,
                                        'type' => 'izin_prinsip',
                                        'title' => $req->title,
                                        'subtitle' => $req->unit->name ?? 'Unit',
                                        'desc' => $req->headcount . ' Orang',
                                        'status' => 'pending',
                                        'url' => route('recruitment.principal-approval.index', ['open_ticket_id' => $req->id]),
                                        'time' => $req->updated_at,
                                        'icon' => 'fa-file-signature',
                                        'color_class' => 'text-blue-600'
                                    ]);
                                }
                            }
                        }
                    }

                    // --- 2. NOTIFIKASI TRAINING (CONTOH) ---
                    // Contoh:
                    /*
                    if ($me->hasRole('Training Manager')) {
                        $trainings = TrainingRequest::where('status', 'pending')->get();
                        foreach($trainings as $train) {
                             $allNotifications->push((object)[
                                'id' => $train->id,
                                'type' => 'training',
                                'title' => $train->topic,
                                'subtitle' => 'Training Request',
                                'desc' => $train->participant_count . ' Peserta',
                                'status' => 'pending',
                                'url' => route('training.approval.index', ['id' => $train->id]),
                                'time' => $train->created_at,
                                'icon' => 'fa-chalkboard-user',
                                'color_class' => 'text-green-600'
                            ]);
                        }
                    }
                    */

                } catch (\Exception $e) {
                }
            }

            // Urutkan notifikasi berdasarkan waktu terbaru
            $sortedNotifications = $allNotifications->sortByDesc('time');

            // Kirim variabel $globalNotifications ke view layout
            $view->with('globalNotifications', $sortedNotifications);
        });
    }
}
