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
if ($this->app->environment('production')) {
        $publicPath = base_path('../public_html');
        $this->app->usePublicPath($publicPath);

        config([
            'dompdf.public_path' => $publicPath,
            'dompdf.options.chroot' => $publicPath,
        ]);
    }

        $this->app->bind(\App\Services\SITMS\SitmsClient::class, function ($app) {
            /** @var LoggerInterface $logger */
            $logger = $app->make(LoggerInterface::class);

            if (config('sitms.read_enabled')) {
                return new \App\Services\SITMS\HttpSitmsClient($logger);
            }

            return new \App\Services\SITMS\NullSitmsClient();
        });
    }

    public function boot(): void
    {
        View::composer('layouts.app', function ($view) {
            $allNotifications = collect();

            if (Auth::check()) {
                try {
                    $me = Auth::user();
                    $myJobTitle = null;
                    if ($me->person_id) {
                        $myJobTitle = \Illuminate\Support\Facades\DB::table('employees')
                            ->join('positions', 'employees.position_id', '=', 'positions.id')
                            ->where('employees.person_id', $me->person_id)
                            ->value('positions.name');
                    }
                    
                    // --- NOTIFIKASI IZIN PRINSIP ---
                    $cleanJobTitle = $myJobTitle ? trim(strtoupper($myJobTitle)) : '';
                    $approverRoles = ['Kepala Proyek (MP)', 'SDM Unit','Kepala Unit', 'DHC', 'AVP', 'Dir SDM', 'Superadmin'];
                    
                    if ($me->hasAnyRole($approverRoles) || $cleanJobTitle === 'VP HUMAN CAPITAL') {
                        $query = RecruitmentRequest::with(['approvals', 'unit'])
                            ->whereIn('status', ['submitted', 'in_review']);

                        if (!$me->hasRole('Superadmin') && !$me->hasRole('DHC') && !$me->hasRole('Dir SDM') && $cleanJobTitle !== 'VP HUMAN CAPITAL' && ($me->hasRole('Kepala Unit') || $me->hasRole('SDM Unit') || $me->hasRole('Kepala Proyek (MP)'))) {
                        $query->where('unit_id', $me->unit_id);
                    }

                        $requests = $query->get();
                        foreach ($requests as $req) {
                            $currentApproval = $req->approvals->sortBy('id')->where('status', 'pending')->first();

                            if ($currentApproval) {
                                preg_match('/\[stage=([^\]]+)\]/', $currentApproval->note, $matches);
                                $stageKey = $matches[1] ?? ''; 
                                $shouldShow = false;
                                $isSameUnit = (string)$me->unit_id === (string)$req->unit_id;

                                if ($me->hasRole('Superadmin')) {$shouldShow = true;}
                                else if ($stageKey === 'kepala_mp' && $me->hasRole('Kepala Proyek (MP)') && $isSameUnit) {$shouldShow = true;}
                                else if ($stageKey === 'sdm_unit' && $me->hasRole('SDM Unit') && $isSameUnit) {$shouldShow = true;}
                                else if ($stageKey === 'kepala_unit' && $me->hasRole('Kepala Unit') && $isSameUnit) {$shouldShow = true;}
                                else if ($stageKey === 'dhc_checker' && $me->hasRole('DHC')) {$shouldShow = true;}
                                else if ($stageKey === 'avp_hc_ops' && ($me->hasRole('AVP') && $myJobTitle === 'AVP Human Capital Operation')) {$shouldShow = true;}
                                else if ($stageKey === 'vp_hc' && $cleanJobTitle === 'VP HUMAN CAPITAL') { $shouldShow = true; }
                                else if ($stageKey === 'dir_sdm' && $me->hasRole('Dir SDM')) {$shouldShow = true;}
                                
                                if ($shouldShow) {
                                    $slaTime = $req->created_at;
                                    $kaUnitApp = $req->approvals->filter(function($a) {
                                        return $a->status == 'approved' && strpos($a->note, 'stage=kepala_unit') !== false;
                                    })->first();
                                    if ($kaUnitApp && $kaUnitApp->decided_at) {
                                        $slaTime = \Illuminate\Support\Carbon::parse($kaUnitApp->decided_at);
                                    }
                                    $allNotifications->push((object)[
                                        'id' => $req->id,
                                        'type' => 'izin_prinsip',
                                        'title' => $req->title,
                                        'subtitle' => $req->unit->name ?? 'Unit',
                                        'desc' => $req->headcount . ' Orang',
                                        'status' => 'pending',
                                        'url' => route('recruitment.principal-approval.index', ['open_ticket_id' => $req->id]),
                                        'time' => $slaTime,
                                        'icon' => 'fa-file-signature',
                                        'color_class' => 'text-blue-600'
                                    ]);
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                }
            }
            $sortedNotifications = $allNotifications->sortByDesc('time');
            $view->with('globalNotifications', $sortedNotifications);
        });
    }
}