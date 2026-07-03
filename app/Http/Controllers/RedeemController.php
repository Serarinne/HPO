<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RedeemController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('redeem_codes');

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('reward_description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $statuses = $request->status;

            if (in_array('active', $statuses) && !in_array('inactive', $statuses)) {
                $query->where('is_active', 1);
            } elseif (in_array('inactive', $statuses) && !in_array('active', $statuses)) {
                $query->where('is_active', 0);
            }

            if (in_array('expired', $statuses)) {
                $query->whereNotNull('expired_at')->where('expired_at', '<', now());
            }
        }

        $codes = $query->orderBy('created_at', 'desc')->paginate(10);

        return view('redeems.index', compact('codes'));
    }

    public function create()
    {
        return redirect()->route('redeems.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:50|unique:redeem_codes,code',
            'reward_description' => 'required|string|max:255',
            'is_active' => 'required|boolean',
            'expired_at' => 'nullable|date',
        ]);

        DB::table('redeem_codes')->insert([
            'code' => $request->code,
            'reward_description' => $request->reward_description,
            'is_active' => $request->is_active,
            'expired_at' => $request->expired_at,
            'created_at' => now(),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['message' => 'Code created successfully!']);
        }

        return redirect()->route('redeems.index')->with('success', 'Code created successfully!');
    }

    public function edit($id)
    {
        return redirect()->route('redeems.index');
    }

    public function update(Request $request, $id)
    {
        if ($request->has('code')) {
            $request->validate([
                'code' => 'required|string|max:50|unique:redeem_codes,code,' . $id,
                'reward_description' => 'required|string|max:255',
                'is_active' => 'required|boolean',
                'expired_at' => 'nullable|date',
            ]);

            DB::table('redeem_codes')->where('id', $id)->update([
                'code' => $request->code,
                'reward_description' => $request->reward_description,
                'is_active' => $request->is_active,
                'expired_at' => $request->expired_at,
            ]);
        } else {
            $request->validate([
                'is_active' => 'required|boolean',
            ]);

            DB::table('redeem_codes')->where('id', $id)->update([
                'is_active' => $request->is_active,
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
            }
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['message' => 'Code updated successfully!']);
        }

        return redirect()->route('redeems.index')->with('success', 'Code updated successfully!');
    }

    public function delete($id)
    {
        DB::table('redeem_codes')->where('id', $id)->delete();

        return redirect()->route('redeems.index')->with('success', 'Code deleted successfully!');
    }
}