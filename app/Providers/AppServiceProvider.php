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
        // --- LOGIC NOTIFIKASI IZIN PRINSIP (GLOBAL) ---
        View::composer('layouts.app', function ($view) {
            $pendingNotifications = collect();

            if (Auth::check()) {
                try {
                    $me = Auth::user();
                    
                    // 1. Ambil Role User
                    $isKepalaUnit = $me->hasRole('Kepala Unit');
                    $isDHC = $me->hasRole('DHC');
                    $isAVP = $me->hasRole('AVP Human Capital Operation');
                    $isVP = $me->hasRole('VP Human Capital');
                    $isDir = $me->hasRole('Dir SDM');
                    $isSuper = $me->hasRole('Superadmin');

                    // Jika user tidak punya role approval, skip query berat
                    if ($isKepalaUnit || $isDHC || $isAVP || $isVP || $isDir || $isSuper) {

                        $query = RecruitmentRequest::with(['approvals', 'unit'])
                            ->whereIn('status', ['submitted', 'in_review']);

                        if ($isKepalaUnit && !$isSuper) {
                            $query->where('unit_id', $me->unit_id);
                        }

                        $requests = $query->get();
                        foreach ($requests as $req) {
                            $currentApproval = $req->approvals->sortBy('id')->where('status', 'pending')->first();

                            if ($currentApproval) {
                                $allApprovals = $req->approvals->sortBy('id')->values();
                                $stageIndex = $allApprovals->search(function($item) use ($currentApproval) {
                                    return $item->id === $currentApproval->id;
                                });

                                $shouldShow = false;

                                if ($isSuper) {
                                    $shouldShow = true;
                                } else {
                                    // Stage 0: Kepala Unit
                                    if ($stageIndex === 0 && $isKepalaUnit) {
                                        if ((string)$me->unit_id === (string)$req->unit_id) $shouldShow = true;
                                    }
                                    // Stage 1: DHC
                                    elseif ($stageIndex === 1 && $isDHC) {
                                        $shouldShow = true;
                                    }
                                    // Stage 2: AVP
                                    elseif ($stageIndex === 2 && $isAVP) {
                                        $shouldShow = true;
                                    }
                                    // Stage 3: VP
                                    elseif ($stageIndex === 3 && $isVP) {
                                        $shouldShow = true;
                                    }
                                    // Stage 4: Dir SDM
                                    elseif ($stageIndex === 4 && $isDir) {
                                        $shouldShow = true;
                                    }
                                }

                                if ($shouldShow) {
                                    $pendingNotifications->push($req);
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {}
            }

            $view->with('approvalNotifs', $pendingNotifications);
        });
    }
}
