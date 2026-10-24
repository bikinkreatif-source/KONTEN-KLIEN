<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClientController extends Controller
{
    public function store(Request $request)
    {
        abort_unless($request->user()->role === 'pm', 403, 'Hanya Project Manager yang dapat menambahkan klien.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'quota_single' => ['sometimes', 'integer', 'min:0'],
            'quota_carousel' => ['sometimes', 'integer', 'min:0'],
            'quota_reels' => ['sometimes', 'integer', 'min:0'],
            'quota_story' => ['sometimes', 'integer', 'min:0'],
            'pillars' => ['nullable', 'string'],
            'information' => ['nullable', 'string'],
            'instagram' => ['nullable', 'url', 'max:255'],
            'tiktok' => ['nullable', 'url', 'max:255'],
            'facebook' => ['nullable', 'url', 'max:255'],
            'drive_url' => ['nullable', 'url', 'max:255'],
            'team_ids' => ['sometimes', 'array'],
            'team_ids.*' => ['integer', 'distinct', 'exists:users,id'],
        ]);

        $teamIds = $data['team_ids'] ?? [];
        unset($data['team_ids']);

        if ($teamIds && User::whereIn('id', $teamIds)->where('role', '!=', 'team')->exists()) {
            throw ValidationException::withMessages(['team_ids' => ['Semua PIC harus merupakan akun tim.']]);
        }

        foreach (['quota_single', 'quota_carousel', 'quota_reels', 'quota_story'] as $field) {
            $data[$field] = $data[$field] ?? 0;
        }

        $client = DB::transaction(function () use ($data, $teamIds, $request) {
            $client = Client::create($data);
            if ($teamIds) {
                $client->teamMembers()->syncWithPivotValues($teamIds, ['assigned_by' => $request->user()->id]);
            }
            return $client->load('teamMembers:id,name,division');
        });

        return response()->json([
            'data' => [
                'id' => $client->id,
                'name' => $client->name,
                'start_date' => $client->start_date->toDateString(),
                'end_date' => $client->end_date->toDateString(),
                'quota_single' => $client->quota_single,
                'quota_carousel' => $client->quota_carousel,
                'quota_reels' => $client->quota_reels,
                'quota_story' => $client->quota_story,
                'pillars' => $client->pillars,
                'information' => $client->information,
                'instagram' => $client->instagram,
                'tiktok' => $client->tiktok,
                'facebook' => $client->facebook,
                'drive_url' => $client->drive_url,
                'team_members' => $client->teamMembers->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'division' => $user->division,
                ])->values(),
            ],
        ], 201);
    }
}
