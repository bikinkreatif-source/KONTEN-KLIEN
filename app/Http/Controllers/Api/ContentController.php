<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Content;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContentController extends Controller
{
    private const STATUSES = ['Ide', 'Dalam proses', 'Menunggu review', 'Menunggu persetujuan klien', 'Perlu revisi', 'Disetujui', 'Terjadwal', 'Tayang'];
    private const TYPES = ['Single Image', 'Carousel', 'Reels', 'Story'];

    public function clients(Request $request)
    {
        $user = $request->user();
        $clients = match ($user->role) {
            'pm' => Client::query(),
            'team' => $user->clientWorkspaces(),
            'client' => Client::whereKey($user->client_id),
            default => Client::whereRaw('1 = 0'),
        };

        return response()->json($clients->withCount('contents')->orderBy('name')->get());
    }

    public function contents(Request $request)
    {
        $query = Content::with(['client:id,name', 'assignee:id,name,division']);
        $user = $request->user();
        if ($user->role === 'team') {
            $clientIds = $user->clientWorkspaces()->select('clients.id');
            $query->whereIn('client_id', $clientIds);
        } elseif ($user->role === 'client') {
            $query->where('client_id', $user->client_id);
        } elseif ($user->role !== 'pm') {
            return response()->json(['message' => 'Peran ini tidak memiliki akses API.'], 403);
        }

        $data = $request->validate([
            'client_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'string', Rule::in(self::STATUSES)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'assigned_to_me' => ['nullable', 'boolean'],
        ]);
        if (! empty($data['client_id'])) $query->where('client_id', $data['client_id']);
        if (! empty($data['status'])) $query->where('status', $data['status']);
        if (! empty($data['from'])) $query->whereDate('publish_date', '>=', $data['from']);
        if (! empty($data['to'])) $query->whereDate('publish_date', '<=', $data['to']);
        if (! empty($data['assigned_to_me'])) $query->where('assignee_id', $user->id);

        return response()->json($query->orderBy('publish_date')->orderBy('publish_time')->paginate(100));
    }

    public function show(Request $request, Content $content)
    {
        $this->authorizeClient($request->user(), $content->client);

        return response()->json($content->load(['client', 'assignee:id,name,email,division', 'revisions.user:id,name,role']));
    }

    public function store(Request $request)
    {
        abort_unless(in_array($request->user()->role, ['pm', 'team'], true), 403, 'Hanya PM atau tim yang dapat membuat konten.');
        $data = $this->validatedContent($request);
        $client = Client::findOrFail($data['client_id']);
        $this->authorizeClient($request->user(), $client);
        $this->validateDateInPlan($data, $client);
        $this->validateAssignee($data['assignee_id'] ?? null, $client);

        $content = $client->contents()->create($data);
        $content->revisions()->create(['user_id' => $request->user()->id, 'action' => $content->status]);

        return response()->json($content->load(['client', 'assignee']), 201);
    }

    public function update(Request $request, Content $content)
    {
        abort_unless(in_array($request->user()->role, ['pm', 'team'], true), 403, 'Akun klien hanya memiliki akses baca.');
        $this->authorizeClient($request->user(), $content->client);
        $data = $this->validatedContent($request, $content);
        $client = isset($data['client_id']) ? Client::findOrFail($data['client_id']) : $content->client;
        $this->authorizeClient($request->user(), $client);
        $data['client_id'] = $client->id;
        $this->validateDateInPlan($data, $client, $content);
        $this->validateAssignee($data['assignee_id'] ?? $content->assignee_id, $client);

        $oldStatus = $content->status;
        $content->update($data);
        if ($oldStatus !== $content->status) {
            $content->revisions()->create(['user_id' => $request->user()->id, 'action' => $content->status]);
        }

        return response()->json($content->fresh()->load(['client', 'assignee']));
    }

    public function destroy(Request $request, Content $content)
    {
        abort_unless($request->user()->role === 'pm', 403, 'Hanya PM yang dapat menghapus konten melalui API.');
        $this->authorizeClient($request->user(), $content->client);
        $content->delete();

        return response()->json(['message' => 'Konten berhasil dihapus.']);
    }

    private function validatedContent(Request $request, ?Content $content = null): array
    {
        $requiredOnCreate = $content ? 'sometimes' : 'required';
        $clientId = $request->input('client_id', $content?->client_id);
        $client = $clientId ? Client::find($clientId) : null;
        $publishDateRules = ['required', 'date'];
        if ($client) {
            $publishDateRules[] = 'after_or_equal:'.$client->start_date->format('Y-m-d');
            $publishDateRules[] = 'before_or_equal:'.$client->end_date->format('Y-m-d');
        }

        return $request->validate([
            'client_id' => [$content ? 'sometimes' : 'required', 'integer', 'exists:clients,id'],
            'title' => [$requiredOnCreate, 'string', 'max:180'],
            'type' => [$requiredOnCreate, Rule::in(self::TYPES)],
            'publish_date' => [$requiredOnCreate, ...$publishDateRules],
            'publish_time' => ['nullable', 'date_format:H:i'],
            'due_date' => ['nullable', 'date'],
            'status' => ['sometimes', 'required', Rule::in(self::STATUSES)],
            'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
            'pillar' => ['nullable', 'string', 'max:150'],
            'funnel' => ['nullable', 'string', 'max:100'],
            'brief' => ['nullable', 'string'],
            'caption' => ['nullable', 'string'],
            'reference_url' => ['nullable', 'url', 'max:2000'],
            'drive_url' => ['nullable', 'url', 'max:2000'],
            'next_step' => ['nullable', 'string'],
            'note' => ['nullable', 'string'],
            'checklist' => ['nullable', 'array'],
        ]);
    }

    private function validateDateInPlan(array $data, Client $client, ?Content $content = null): void
    {
        $date = $data['publish_date'] ?? $content?->publish_date?->format('Y-m-d');
        if ($date && ($date < $client->start_date->format('Y-m-d') || $date > $client->end_date->format('Y-m-d'))) {
            abort(422, 'Tanggal posting harus berada di dalam periode planner klien.');
        }
    }

    private function validateAssignee(?int $assigneeId, Client $client): void
    {
        if (! $assigneeId) return;
        $assignee = User::findOrFail($assigneeId);
        if ($assignee->role === 'team' && ! $client->teamMembers()->whereKey($assignee->id)->exists()) {
            abort(422, 'PIC belum ditambahkan ke tim planner klien ini.');
        }
        if ($assignee->role === 'client') {
            abort(422, 'Akun klien tidak dapat dipilih sebagai PIC.');
        }
    }

    private function authorizeClient(User $user, Client $client): void
    {
        if ($user->role === 'pm') return;
        if ($user->role === 'team' && $user->clientWorkspaces()->whereKey($client->id)->exists()) return;
        if ($user->role === 'client' && $user->client_id === $client->id) return;
        abort(403, 'Anda tidak memiliki akses ke klien ini.');
    }
}
