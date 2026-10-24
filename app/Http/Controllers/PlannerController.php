<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Content;
use App\Models\ContentRevision;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PlannerController extends Controller
{
    public function loginForm() { return view('auth.login'); }

    public function login(Request $request)
    {
        $credentials = $request->validate(['email' => ['required','email'], 'password' => ['required']]);
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Email atau kata sandi tidak cocok.'])->onlyInput('email');
        }
        $request->session()->regenerate();
        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    public function guide(Request $request)
    {
        $user = $request->user();
        $clients = match ($user->role) {
            'client' => Client::whereKey($user->client_id)->get(),
            'team' => $user->clientWorkspaces()->orderBy('name')->get(),
            default => Client::orderBy('name')->get(),
        };
        return view('guide', compact('clients'));
    }

    public function dashboard(Request $request)
    {
        $user = $request->user();
        if ($user->role === 'pm' && ! $request->filled('client')) {
            $month = $request->query('month', now()->format('Y-m'));
            if (! preg_match('/^\d{4}-\d{2}$/', $month)) $month = now()->format('Y-m');
            $periodStart = \Carbon\Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $periodEnd = $periodStart->copy()->endOfMonth();
            $clients = Client::withCount(['contents', 'teamMembers'])->orderBy('name')->get();
            $client = null;
            $monthContents = Content::with(['client', 'assignee'])->whereBetween('publish_date', [$periodStart, $periodEnd])->orderBy('publish_date')->orderBy('publish_time')->get();
            $statusCounts = $monthContents->countBy('status');
            $awaitingApproval = Content::with(['client', 'assignee'])->where('status', 'Menunggu persetujuan klien')->orderBy('publish_date')->get();
            $overdueContents = Content::with(['client', 'assignee'])->whereNotIn('status', ['Disetujui', 'Terjadwal', 'Tayang'])->whereNotNull('due_date')->whereDate('due_date', '<', now()->toDateString())->orderBy('due_date')->get();
            $upcomingContents = Content::with(['client', 'assignee'])->whereBetween('publish_date', [now()->toDateString(), now()->addDays(7)->toDateString()])->orderBy('publish_date')->orderBy('publish_time')->get();
            $clientMonthCounts = $monthContents->countBy('client_id');
            $teamCount = User::where('role', 'team')->count();
            return view('pm-dashboard', compact('clients', 'client', 'month', 'monthContents', 'statusCounts', 'awaitingApproval', 'overdueContents', 'upcomingContents', 'clientMonthCounts', 'teamCount'));
        }
        $clients = match ($user->role) {
            'client' => Client::whereKey($user->client_id)->get(),
            'team' => $user->clientWorkspaces()->orderBy('name')->get(),
            default => Client::orderBy('name')->get(),
        };
        $client = $clients->firstWhere('id', (int) $request->query('client'));
        if (! $client) $client = $user->role === 'client' ? $clients->first() : ($clients->firstWhere('name', 'Juragan Property') ?? $clients->first());
        $month = $request->query('month', now()->format('Y-m'));
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) $month = now()->format('Y-m');
        $contents = collect();
        $planContents = collect();
        if ($client) {
            $planContents = $client->contents()->with('assignee')->orderBy('publish_date')->orderBy('publish_time')->get();
            $query = $client->contents()->with('assignee')->whereYear('publish_date', substr($month,0,4))->whereMonth('publish_date', substr($month,5,2));
            if ($request->filled('status')) $query->where('status', $request->query('status'));
            if ($request->filled('type')) $query->where('type', $request->query('type'));
            if ($request->filled('owner')) $query->where('assignee_id', $request->query('owner'));
            if ($request->filled('q')) $query->where(fn ($q) => $q->where('title','like','%'.$request->query('q').'%')->orWhere('caption','like','%'.$request->query('q').'%'));
            $contents = $query->orderBy('publish_date')->orderBy('publish_time')->get();
        }
        $team = $client ? $client->teamMembers()->orderBy('name')->get() : collect();
        return view('dashboard', compact('clients','client','contents','planContents','month','team'));
    }

    public function clientForm(Request $request, ?Client $client = null)
    {
        return view('clients.form', [
            'client' => $client,
            'teamMembers' => User::where('role', 'team')->orderBy('division')->orderBy('name')->get(),
            'assignedTeamIds' => $client?->teamMembers()->pluck('users.id')->all() ?? [],
        ]);
    }

    public function saveClient(Request $request, ?Client $client = null)
    {
        $data = $request->validate([
            'name' => ['required','string','max:150'], 'start_date' => ['required','date'], 'end_date' => ['required','date','after_or_equal:start_date'],
            'quota_single' => ['nullable','integer','min:0'], 'quota_carousel' => ['nullable','integer','min:0'], 'quota_reels' => ['nullable','integer','min:0'], 'quota_story' => ['nullable','integer','min:0'],
            'pillars' => ['nullable','string'], 'information' => ['nullable','string'], 'instagram' => ['nullable','url','max:255'], 'tiktok' => ['nullable','url','max:255'], 'facebook' => ['nullable','url','max:255'], 'drive_url' => ['nullable','url','max:255'], 'logo' => ['nullable','image','max:350'],
        ]);
        foreach (['quota_single','quota_carousel','quota_reels','quota_story'] as $field) $data[$field] = $data[$field] ?? 0;
        if ($request->hasFile('logo')) $data['logo_path'] = $request->file('logo')->store('client-logos','public');
        unset($data['logo']);
        if ($client) $client->update($data); else $client = Client::create($data + ['public_token' => Str::random(40)]);
        return redirect()->route('dashboard',['client'=>$client->id])->with('success', 'Informasi klien berhasil disimpan.');
    }

    public function deleteClient(Client $client)
    {
        $client->delete();
        return redirect()->route('dashboard')->with('success','Klien berhasil dihapus.');
    }

    public function saveClientTeam(Request $request, Client $client)
    {
        $data = $request->validate(['team_ids' => ['nullable','array'], 'team_ids.*' => ['integer','exists:users,id']]);
        $members = User::whereIn('id', $data['team_ids'] ?? [])->where('role', 'team')->pluck('id')->all();
        if (count($members) !== count($data['team_ids'] ?? [])) {
            return back()->withErrors(['team_ids' => 'Pilih hanya akun anggota tim yang valid.'])->withInput();
        }
        $client->teamMembers()->syncWithPivotValues($members, ['assigned_by' => $request->user()->id]);
        return back()->with('success', 'Tim planner klien berhasil diperbarui.');
    }

    public function saveInfo(Request $request, Client $client)
    {
        $this->authorizeClient($request, $client);
        $data = $request->validate(['information' => ['nullable','string'], 'pillars' => ['nullable','string']]);
        $client->update($data);
        return back()->with('success','Informasi klien dan pilar konten tersimpan.');
    }

    public function contentForm(Request $request, ?Content $content = null)
    {
        $client = $content?->client ?? Client::findOrFail($request->query('client'));
        $this->authorizeClient($request, $client);
        $team = $request->user()->role === 'pm'
            ? User::whereIn('role',['team','pm'])->orderBy('name')->get()
            : $client->teamMembers()->orderBy('name')->get();
        return view('contents.form', compact('client','content','team'));
    }

    public function showContent(Request $request, Content $content)
    {
        $this->authorizeClient($request, $content->client);
        $content->load(['assignee','revisions.user','client']);
        return view('contents.show', compact('content'));
    }

    public function saveContent(Request $request, ?Content $content = null)
    {
        $client = $content?->client ?? Client::findOrFail($request->input('client_id'));
        $this->authorizeClient($request, $client);
        $data = $request->validate([
            'title'=>['required','string','max:180'],'type'=>['required','in:Single Image,Carousel,Reels,Story'],
            'publish_date'=>['required','date','after_or_equal:'.$client->start_date->format('Y-m-d'),'before_or_equal:'.$client->end_date->format('Y-m-d')],
            'publish_time'=>['nullable','date_format:H:i'],'due_date'=>['nullable','date'],'status'=>['required','in:Ide,Dalam proses,Menunggu review,Menunggu persetujuan klien,Perlu revisi,Disetujui,Terjadwal,Tayang'],
            'assignee_id'=>['nullable','exists:users,id'],'pillar'=>['nullable','string','max:150'],'funnel'=>['nullable','string','max:100'],
            'brief'=>['nullable','string'],'caption'=>['nullable','string'],'reference_url'=>['nullable','url','max:2000'],'drive_url'=>['nullable','url','max:2000'],'next_step'=>['nullable','string'],'note'=>['nullable','string'],'revision_comment'=>['nullable','string','max:3000'],'checklist'=>['nullable','array'],
        ]);
        $revisionComment = $data['revision_comment'] ?? null;
        unset($data['revision_comment']);
        $data['client_id'] = $client->id;
        if (! empty($data['assignee_id'])) {
            $assignee = User::findOrFail($data['assignee_id']);
            if ($assignee->role === 'team' && ! $client->teamMembers()->whereKey($assignee->id)->exists()) {
                return back()->withErrors(['assignee_id' => 'PIC belum ditambahkan ke tim planner klien ini.'])->withInput();
            }
        }
        $oldStatus = $content?->status;
        if ($content) $content->update($data); else $content = Content::create($data);
        if (! $oldStatus || $oldStatus !== $content->status || $revisionComment) $content->revisions()->create(['user_id'=>$request->user()->id,'action'=>$oldStatus && $oldStatus === $content->status ? 'Konten diperbarui' : $content->status,'comment'=>$revisionComment]);
        return redirect()->route('dashboard',['client'=>$client->id,'month'=>substr($data['publish_date'],0,7)])->with('success','Konten berhasil disimpan.');
    }

    public function deleteContent(Request $request, Content $content)
    {
        $this->authorizeClient($request, $content->client); $id = $content->client_id; $content->delete();
        return redirect()->route('dashboard',['client'=>$id])->with('success','Konten dihapus.');
    }

    public function review(Request $request, Content $content)
    {
        abort_unless($request->user()->role === 'client' && $request->user()->client_id === $content->client_id, 403);
        $data = $request->validate(['decision'=>['required','in:Disetujui,Perlu revisi'],'comment'=>['nullable','string','max:3000']]);
        if ($data['decision'] === 'Perlu revisi' && blank($data['comment'] ?? null)) return back()->withErrors(['comment'=>'Tuliskan catatan revisi untuk tim terlebih dahulu.'])->withInput();
        $content->update(['status'=>$data['decision']]);
        $content->revisions()->create(['user_id'=>$request->user()->id,'action'=>$data['decision'],'comment'=>$data['comment'] ?? null]);
        return back()->with('success','Umpan balik Anda sudah terkirim.');
    }

    public function share(Client $client)
    {
        abort_unless(request()->route('token') === $client->public_token, 404);
        return view('public', ['client'=>$client,'contents'=>$client->contents()->with('assignee')->orderBy('publish_date')->get()]);
    }

    public function users()
    {
        return view('users.index',['users'=>User::with('client')->orderBy('role')->orderBy('name')->get(),'clients'=>Client::orderBy('name')->get()]);
    }

    public function saveUser(Request $request, ?User $user = null)
    {
        $data=$request->validate(['name'=>['required','string','max:150'],'email'=>['required','email','max:255','unique:users,email'.($user?','.$user->id:'')],'role'=>['required','in:pm,team,client'],'division'=>[$request->input('role')==='team'?'required':'nullable','string','max:100'],'client_id'=>['nullable','exists:clients,id'],'password'=>[$user?'nullable':'required','string','min:8']]);
        if ($data['role'] === 'client' && empty($data['client_id'])) return back()->withErrors(['client_id'=>'Pilih klien untuk akun client.'])->withInput();
        if ($data['role'] !== 'client') $data['client_id']=null;
        if (empty($data['password'])) unset($data['password']); else $data['password']=Hash::make($data['password']);
        if ($user) $user->update($data); else User::create($data);
        return back()->with('success','Akun pengguna berhasil disimpan.');
    }

    public function deleteUser(Request $request, User $user)
    {
        abort_if($request->user()->is($user),403,'Anda tidak dapat menghapus akun yang sedang digunakan.'); $user->delete();
        return back()->with('success','Akun pengguna dihapus.');
    }

    private function authorizeClient(Request $request, Client $client): void
    {
        if ($request->user()->role === 'client') abort_unless($request->user()->client_id === $client->id,403);
        if ($request->user()->role === 'team') abort_unless($client->teamMembers()->whereKey($request->user()->id)->exists(),403);
    }
}
