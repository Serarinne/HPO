<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use App\Models\FetchTask;

class FetchController extends Controller
{
    public function index(Request $request)
    {
        $tasks = FetchTask::query()
            ->when($request->status, function($query, $status) {
                return $query->where('status', $status);
            })
            ->orderByRaw("
                CASE 
                    WHEN status = 'running' THEN 1
                    WHEN status = 'rerunning' THEN 2
                    WHEN status = 'pending' THEN 3
                    WHEN status = 'completed' THEN 4
                    ELSE 5
                END
            ")
            ->orderBy('updated_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        try {
            $stats = [
                'total' => FetchTask::count(),
                'pending' => FetchTask::where('status', 'pending')->count(),
                'running' => FetchTask::where('status', 'running')->count(),
                'rerunning' => FetchTask::where('status', 'rerunning')->count(),
                'completed' => FetchTask::where('status', 'completed')->count(),
            ];
        } catch (\Exception $e) {
            Log::error('[Monitor] Failed to get stats: ' . $e->getMessage());
            
            $stats = [
                'total' => 0,
                'pending' => 0,
                'running' => 0,
                'rerunning' => 0,
                'completed' => 0,
            ];
        }

        return view('fetch.index', compact('tasks', 'stats'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tag_name' => 'required|string|max:255',
            'source_api' => 'required|in:danbooru,gelbooru,zerochan',
        ]);

        try {
            $tag = trim($request->tag_name);

            $existing = FetchTask::where('source_api', $request->source_api)
                ->where('tag_name', $tag)
                ->first();

            if ($existing) {
                if (!in_array($existing->status, ['running', 'rerunning'])) {
                    $existing->update([
                        'status' => 'pending',
                        'current_page' => 1,
                        'last_run_at' => null,
                    ]);
                    
                    Log::info("[Monitor] Task reset to pending", [
                        'task_id' => $existing->id,
                        'tag' => $tag,
                        'api' => $request->source_api,
                    ]);

                    return redirect()->back()->with('success', "Task '{$tag}' already exists and has been reset to pending.");
                } else {
                    return redirect()->back()->with('error', "Task '{$tag}' is currently {$existing->status}. Cannot modify.");
                }
            }

            FetchTask::create([
                'tag_name' => $tag,
                'source_api' => $request->source_api,
                'status' => 'pending',
                'current_page' => 1,
                'last_source_id' => 0,
                'last_post_date' => null,
                'last_run_at' => null,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            Log::info("[Monitor] Manual task added", [
                'tag' => $tag,
                'api' => $request->source_api,
            ]);

            return redirect()->back()->with('success', "Task '{$tag}' added to queue.");

        } catch (\Exception $e) {
            Log::error("[Monitor] Failed to add task", [
                'tag' => $request->tag_name,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', 'Failed to add task: ' . $e->getMessage());
        }
    }

    public function reset($id)
    {
        try {
            $task = FetchTask::findOrFail($id);
            
            $originalStatus = $task->status;
            $newStatus = match($task->status) {
                'running' => 'pending',
                'rerunning' => 'completed',
                'completed' => 'pending',
                default => 'pending'
            };

            $task->update([
                'status' => $newStatus,
                'current_page' => 1,
                'last_run_at' => null,
            ]);

            Log::info("[Monitor] Task reset", [
                'task_id' => $id,
                'tag' => $task->tag_name,
                'from_status' => $originalStatus,
                'to_status' => $newStatus,
            ]);

            return redirect()->back()->with('success', "Task '{$task->tag_name}' reset from {$originalStatus} to {$newStatus}.");

        } catch (\Exception $e) {
            Log::error("[Monitor] Reset failed", [
                'task_id' => $id,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', 'Failed to reset task: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $task = FetchTask::findOrFail($id);
            
            if (in_array($task->status, ['running', 'rerunning'])) {
                return redirect()->back()->with('error', "Cannot delete task while {$task->status}. Reset it first.");
            }

            $tagName = $task->tag_name;
            $task->delete();

            Log::info("[Monitor] Task deleted", [
                'task_id' => $id,
                'tag' => $tagName,
            ]);

            return redirect()->back()->with('success', "Task '{$tagName}' deleted successfully.");

        } catch (\Exception $e) {
            Log::error("[Monitor] Delete failed", [
                'task_id' => $id,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', 'Failed to delete task: ' . $e->getMessage());
        }
    }

    public function populate(Request $request)
    {
        $request->validate([
            'type' => 'required|in:character',
        ]);

        try {
            $activeTasks = FetchTask::whereIn('status', ['pending', 'running', 'rerunning'])->count();

            if ($activeTasks > 0) {
                return redirect()->back()->with('error', "Cannot populate: {$activeTasks} active task(s) exist. Wait for completion or clear queue first.");
            }

            $exitCode = Artisan::call('fetch:manager', [
                '--api' => 'danbooru',
                '--type' => 'character',
                '--force-populate' => true,
            ]);

            $output = Artisan::output();

            Log::info("[Monitor] Populate triggered for NTE Characters", [
                'exit_code' => $exitCode,
            ]);

            if ($exitCode === 0) {
                if (preg_match('/(\d+)\s+new\s+task/i', $output, $matches) || preg_match('/(\d+)\s+tag\s+baru/i', $output, $matches)) {
                    $count = $matches[1];
                    return redirect()->back()->with('success', "Auto-populate successful! Added {$count} new NTE character tags.");
                }
                
                return redirect()->back()->with('success', "Auto-populate for NTE characters executed successfully.");
            }

            return redirect()->back()->with('error', "Auto-populate failed with exit code: {$exitCode}. Check log for details.");

        } catch (\Exception $e) {
            Log::error("[Monitor] Populate failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', "Failed to run populate: " . $e->getMessage());
        }
    }
}