<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Wallpaper;
use App\Models\User;
use App\Models\Post;
use App\Models\Character;
use App\Models\Tag;
use App\Models\Series;
use App\Models\Artist;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function index()
    {
        $cacheTtl = now()->addMinutes(10);
        
        $counts = Cache::remember('dashboard_counts', $cacheTtl, function () {
            return [
                'totalWallpapers' => Wallpaper::count(),
                'totalUsers'      => User::count(),
                'totalPosts'      => Post::count(),
                'totalCharacters' => Character::count(),
                'totalTags'       => Tag::count(),
                'totalSeries'     => Series::count(),
                'totalArtists'    => Artist::count(),
            ];
        });

        $fwStats = DB::table('fetched_wallpapers')
            ->selectRaw("
                CAST(SUM(status = 'pending') AS SIGNED) as pending,
                CAST(SUM(status = 'processing') AS SIGNED) as processing,
                CAST(SUM(status = 'failed') AS SIGNED) as failed
            ")->first();

        $ftStats = DB::table('fetch_tasks')
            ->selectRaw("
                CAST(SUM(status = 'running') AS SIGNED) as running,
                CAST(SUM(status = 'completed') AS SIGNED) as completed,
                CAST(SUM(status = 'pending') AS SIGNED) as pending
            ")->first();

        $startDate = now()->subDays(6)->startOfDay();

        $createdStats = User::selectRaw('DATE(created_at) as date, count(*) as count')
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->pluck('count', 'date');

        $modifiedStats = User::selectRaw('DATE(updated_at) as date, count(*) as count')
            ->whereNotNull('updated_at')
            ->where('updated_at', '>=', $startDate)
            ->groupBy('date')
            ->pluck('count', 'date');

        $allDates = collect();
        for ($i = 6; $i >= 0; $i--) {
            $allDates->push(now()->subDays($i)->format('Y-m-d'));
        }

        $chartLabels = [];
        $chartDataCreated = [];
        $chartDataModified = [];

        foreach ($allDates as $date) {
            $chartLabels[] = Carbon::parse($date)->format('d M Y');
            $chartDataCreated[] = $createdStats->get($date, 0);
            $chartDataModified[] = $modifiedStats->get($date, 0);
        }

        return view('dashboard.index', array_merge($counts, [
            'fetchWallpaperPending'    => $fwStats->pending ?? 0,
            'fetchWallpaperProcessing' => $fwStats->processing ?? 0,
            'fetchWallpaperFailed'     => $fwStats->failed ?? 0,
            'taskFetchRunning'         => $ftStats->running ?? 0,
            'taskFetchCompleted'       => $ftStats->completed ?? 0,
            'taskFetchPending'         => $ftStats->pending ?? 0,
            'chartLabels'              => $chartLabels,
            'chartDataCreated'         => $chartDataCreated,
            'chartDataModified'        => $chartDataModified
        ]));
    }

    public function settings()
    {
        $settings = DB::table('settings')->first();

        return view('dashboard.settings', compact('settings'));
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'package_name' => 'required|string|max:255',
            'app_version' => 'required|string|max:255',
            'build_version' => 'required|integer',
            'url_playstore' => 'nullable|url',
            'banner_provider' => 'required|string|max:50',
            'open_app_provider' => 'required|string|max:50',
            'transition_mode' => 'required|string|max:50',
            'ad_interval' => 'required|integer',
            'admob_app_id' => 'nullable|string|max:255',
            'admob_banner_id' => 'nullable|string|max:255',
            'admob_interstitial_id' => 'nullable|string|max:255',
            'admob_rewarded_id' => 'nullable|string|max:255',
            'admob_rewarded_interstitial_id' => 'nullable|string|max:255',
            'admob_open_app_id' => 'nullable|string|max:255',
            'applovin_sdk_key' => 'nullable|string|max:255',
            'applovin_banner_id' => 'nullable|string|max:255',
            'applovin_interstitial_id' => 'nullable|string|max:255',
            'applovin_rewarded_id' => 'nullable|string|max:255',
            'applovin_open_app_id' => 'nullable|string|max:255',
        ]);

        $settings = DB::table('settings')->first();

        if ($settings) {
            DB::table('settings')->where('id', $settings->id)->update(array_merge($validated, [
                'updated_at' => now(),
            ]));
        } else {
            DB::table('settings')->insert(array_merge($validated, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        return redirect()->route('settings')->with('success', 'Application settings updated successfully.');
    }
}