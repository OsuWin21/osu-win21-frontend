<?php

namespace App\Http\Controllers;

use Rinvex\Country\Country;
use Carbon\Carbon;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function index()
    {
        $user = User::orderBy('id', 'desc')->take(5)->get();
        $userCount = User::count();
        $userCount -= 5;

        $vnRecord = DB::table('scores')
            ->join('users', 'scores.userid', '=', 'users.id')
            ->join('maps', 'scores.map_md5', '=', 'maps.md5')
            ->select('users.id', 'users.name', 'scores.pp')
            ->orderBy('scores.pp', 'desc')
            ->where('scores.mode', 0)
            ->where('maps.status', 2)
            ->where('scores.status', 2)
            ->first();
        
        $rxRecord = DB::table('scores')
            ->join('users', 'scores.userid', '=', 'users.id')
            ->join('maps', 'scores.map_md5', '=', 'maps.md5')
            ->select('users.id', 'users.name', 'scores.pp')
            ->orderBy('scores.pp', 'desc')
            ->where('scores.mode', 4)
            ->where('maps.status', 2)
            ->where('scores.status', 2)
            ->first();

        $apRecord = DB::table('scores')
            ->join('users', 'scores.userid', '=', 'users.id')
            ->join('maps', 'scores.map_md5', '=', 'maps.md5')
            ->select('users.id', 'users.name', 'scores.pp')
            ->orderBy('scores.pp', 'desc')
            ->where('scores.mode', 8)
            ->where('maps.status', 2)
            ->where('scores.status', 2)
            ->first();

        return view('user.index', [
            'user' => $user,
            'user_count' => $userCount,
            'vn_record' => $vnRecord,
            'rx_record' => $rxRecord,
            'ap_record' => $apRecord
        ]);
    }

    public function register()
    {
        if (!auth()->check()) {
            return view('user.register');
        } else {
            return redirect('/u/' . Auth::user()->id)->with('error', 'You are already logged in.');
        }
    }

    public function registerProcess(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:15',
            'email' => 'required|string|email|max:255',
            'pw_bcrypt' => 'required|string|min:8|max:32',
            'invite_code' => 'required|string|exists:invites,invite_code'
        ]);

        if (User::where('name', $request->input('username'))->exists()) {
            return redirect()->back()->withInput()->with('error', 'Username Existed.');
        }
        if (User::where('email', $request->input('email'))->exists()) {
            return redirect()->back()->withInput()->with('error', 'Email Existed.');
        }

        $inviteUsed = DB::table('invite_usage')->where('invite_code', $request->invite_code)->exists();
        if ($inviteUsed) {
            return redirect()->back()->withInput()->with('error', 'Invite code already used.');
        }

        // Proses pendaftaran user
        $countryCode = $this->detectCountryFromIP();

        $user = new User();
        $user->name = $request->input('username');
        $user->safe_name = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $request->input('username')));
        $user->email = $request->input('email');
        $user->priv = User::UNRESTRICTED;
        $user->pw_bcrypt = Hash::make(md5($request->input('pw_bcrypt')), ['rounds' => 12]);
        $user->country = strtolower($countryCode) ?: $request->input('country', 'id');
        $user->silence_end = 0;
        $user->donor_end = 0;
        $user->creation_time = Carbon::now()->timestamp;
        $user->latest_activity = Carbon::now()->timestamp;
        $user->clan_id = 0;
        $user->clan_priv = 0;
        $user->preferred_mode = 0;
        $user->play_style = 0;
        $user->save();

        DB::table('invite_usage')->insert([
            'invite_code' => $request->invite_code,
            'code_user' => $user->id,
            'used_at' => now(),
        ]);

        for ($i = 0; $i < 4; $i++) {
            DB::table('invites')->insert([
                'invite_code' => strtoupper(bin2hex(random_bytes(4))),
                'userid' => $user->id,
                'created_at' => now(),
            ]);
        }

        Auth::login($user);

        $stats = [];
        $userId = Auth::user()->id;

        foreach ([0, 1, 2, 3, 4, 5, 6, 8] as $mode) {
            $stats[] = [
                'id' => $userId,
                'mode' => $mode,
                'tscore' => 0,
                'rscore' => 0,
                'pp' => 0,
                'plays' => 0,
                'playtime' => 0,
                'acc' => 0,
                'max_combo' => 0,
                'total_hits' => 0,
                'replay_views' => 0,
                'xh_count' => 0,
                'x_count' => 0,
                'sh_count' => 0,
                's_count' => 0,
                'a_count' => 0
            ];
        }

        DB::table('stats')->insert($stats);

        return redirect('/u/' . Auth::user()->id)->with('success', 'Registration successful!');
    }

    public function login()
    {
        if (!auth()->check()) {
            return view('user.login');
        } else {
            return redirect('/u/' . Auth::user()->id);
        }
    }

    public function loginProcess(Request $request)
    {
        $data = [
            'name' => $request->input('username'),
            'pw_bcrypt' => md5($request->input('pw_bcrypt'))
        ];

        $user = User::where('name', $data['name'])->first();

        if ($user && password_verify($data['pw_bcrypt'], $user->pw_bcrypt)) {
            Auth::login($user);
            return redirect('/u/' . Auth::user()->id);
        } else {
            return redirect('/login')->with('error', 'Invalid username or password');
        }
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->back()->with('success', 'You have been logged out successfully.');
    }

    public function leaderboard(request $request)
    {
        $mode = $request->input('mode', 0);
        $sort = $request->input('sort', 0);
        $rx = $request->input('rx', 0);

        $combinedMode = $mode + $rx;

        $apiUrl = env('API_OSU_URL') . "/v1/get_leaderboard?sort={$sort}&mode={$combinedMode}&limit=50&offset=0";
        $response = Http::get($apiUrl);
        if ($response->successful()) {
            $leaderboard = $response->json()["leaderboard"];
        } else {
            $leaderboard = [];
        }

        $leaderboard = collect($leaderboard ?? [])->transform(function ($item, $key) {
            $item['rank'] = $key + 1;
            if (!empty($item['country'])) {
                $item['flag_url'] = $this->getTwemojiFlagUrl($item['country']);
                $item['country_name'] = country($item['country'])->getOfficialName();
            }
            return $item;
        });

        return view('user.leaderboard', [
            'mode' => $mode,
            'rx' => $rx,
            'sort' => $sort,
            'leaderboard' => $leaderboard
        ]);
    }

    public function profile(request $request, $id)
    {
        # Check if user exists
        $user = User::find($id);
        if (!$user) {
            return redirect('/')->with('error', 'User not found');
        } else {
            if ($user && $user->country) {
                $user->flag_url = $this->getTwemojiFlagUrl($user->country);
                $user->country_name = country($user->country)->getOfficialName();
            }

            # Mode Check
            $mode = $request->input('mode', 0);
            $rx = $request->input('rx', 0);

            $combinedMode = $rx + $mode;

            # Get the user's Stats
            $userProfileUrl = env('API_OSU_URL') . "/v2/players/{$id}/stats/{$combinedMode}";
            $response = Http::get($userProfileUrl);
            if ($response->successful()) {
                $userProfile = $response->json()["data"];
            } else {
                $userProfile = [];
            }

            $countryCode = $user->country ?? null;
            $userPP = isset($userProfile['pp']) ? $userProfile['pp'] : 0;

            if ($userPP > 0) {
                $globalRank = DB::table('stats')
                    ->where('mode', $combinedMode)
                    ->where('pp', '>', $userPP)
                    ->count() + 1;

                $countryRank = DB::table('stats')
                    ->join('users', 'users.id', '=', 'stats.id')
                    ->where('stats.mode', $combinedMode)
                    ->where('users.country', $countryCode)
                    ->where('stats.pp', '>', $userPP)
                    ->count() + 1;
            } else {
                $globalRank = 0;
                $countryRank = 0;
            }

            # Get the user's first place scores
            if ($rx != 0) {
                $firstPlaces = DB::table('scores')
                    ->join('maps', 'scores.map_md5', '=', 'maps.md5')
                    ->join(
                        DB::raw('(SELECT 
                        map_md5, 
                        MAX(pp) as max_pp 
                      FROM scores 
                      WHERE mode = ' . $combinedMode . ' 
                        AND status = 2
                      GROUP BY map_md5) as max_pps'),
                        function ($join) {
                            $join->on('scores.map_md5', '=', 'max_pps.map_md5')
                                ->on('scores.pp', '=', 'max_pps.max_pp');
                        }
                    )
                    ->select(
                        'scores.id',
                        'scores.grade',
                        'scores.userid',
                        'scores.score',
                        'scores.acc',
                        'scores.pp',
                        'scores.mods',
                        'scores.play_time',
                        'maps.id as map_id',
                        'maps.title as map_title',
                        'maps.status as map_status',
                        'maps.artist as map_artist',
                        'maps.version as map_version'
                    )
                    ->where('maps.status', '!=', 0)
                    ->where('scores.userid', $id)
                    ->where('scores.mode', $combinedMode)
                    ->orderBy('scores.play_time', 'desc')
                    ->get()
                    ->map(function ($pp) {
                        $pp->pp = (int)$pp->pp;
                        return $pp;
                    });
            } else {
                $firstPlaces = DB::table('scores')
                    ->join('maps', 'scores.map_md5', '=', 'maps.md5')
                    ->join(
                        DB::raw('(SELECT 
                        map_md5, 
                        MAX(score) as max_score 
                      FROM scores 
                      WHERE mode = ' . $mode . ' 
                        AND status = 2
                      GROUP BY map_md5) as max_scores'),
                        function ($join) {
                            $join->on('scores.map_md5', '=', 'max_scores.map_md5')
                                ->on('scores.score', '=', 'max_scores.max_score');
                        }
                    )
                    ->select(
                        'scores.id',
                        'scores.grade',
                        'scores.userid',
                        'scores.score',
                        'scores.acc',
                        'scores.pp',
                        'scores.mods',
                        'scores.play_time',
                        'maps.id as map_id',
                        'maps.title as map_title',
                        'maps.status as map_status',
                        'maps.artist as map_artist',
                        'maps.version as map_version'
                    )
                    ->where('maps.status', '!=', 0)
                    ->where('scores.userid', $id)
                    ->where('scores.mode', $mode)
                    ->orderBy('scores.play_time', 'desc')
                    ->get()
                    ->map(function ($pp) {
                        $pp->pp = (int)$pp->pp;
                        return $pp;
                    });
            }

            $firstPlaces->transform(function ($play) {
                $play->mods_list = $this->decodeMods($play->mods);
                return $play;
            });

            # Get the user's top plays
            $topPlaysUrl = env('API_OSU_URL') . "/v1/get_player_scores?scope=best&id={$id}&mode={$combinedMode}&limit=100&include_loved=false&include_failed=false";
            $response = Http::get($topPlaysUrl);
            if ($response->successful()) {
                $topPlays = collect($response->json()["scores"] ?? []);
            } else {
                $topPlays = collect([]);
            }

            $topPlays->transform(function ($play) {
                $play['mods_list'] = $this->decodeMods($play['mods']);
                return $play;
            });

            # Get the user's recent plays
            $recentPlaysUrl = env('API_OSU_URL') . "/v1/get_player_scores?scope=recent&id={$id}&mode={$combinedMode}&limit=100&include_loved=true&include_failed=true";
            $response = Http::get($recentPlaysUrl);
            if ($response->successful()) {
                $recentPlays = collect($response->json()["scores"] ?? []);
            } else {
                $recentPlays = collect([]);
            }

            $recentPlays = $recentPlays->filter(function ($play) {
                if (!isset($play['play_time'])) return false;
                try {
                    $playTime = Carbon::parse($play['play_time']);
                } catch (\Exception $e) {
                    return false;
                }
                return $playTime->greaterThanOrEqualTo(now()->subDay());
            })->take(100)->values();

            $recentPlays->transform(function ($play) {
                $play['mods_list'] = $this->decodeMods($play['mods']);
                return $play;
            });
        }

        return view('user.profile', [
            'user' => $user,
            'first_places' => $firstPlaces,
            'top_plays' => $topPlays,
            'recent_plays' => $recentPlays,
            'mode' => $mode,
            'rx' => $rx,
            'user_profile' => $userProfile,
            'global_rank' => $globalRank,
            'country_rank' => $countryRank,
        ]);
    }

    public function editProfile($id)
    {
        $user = User::find($id);

        if (!$user) {
            return redirect()->back()->with('error', 'User not found');
        } else if ($user != Auth::user()) {
            return redirect()->back()->with('error', 'You are not authorized to edit this profile');
        } else {
            if ($user && $user->country) {
                $user->country = country($user->country)->getOfficialName();
            }

            return view('user.edit', [
                'user' => $user
            ]);
        }
    }

    public function editProcess(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $response = ['success' => true];

        // Simpan konten halaman pengguna
        if ($request->has('userpage-content')) {
            $user->userpage_content = $request->input('userpage-content');
            $user->save();
        }

        // Simpan avatar
        if ($request->hasFile('avatar')) {
            $avatarPath = 'avatars/' . $user->id . '.png';
            Storage::disk('public')->put($avatarPath, file_get_contents($request->file('avatar')));
        }

        // Simpan background
        if ($request->hasFile('background')) {
            $bgPath = 'backgrounds/' . $user->id . '.png';
            Storage::disk('public')->put($bgPath, file_get_contents($request->file('background')));
        }

        // Verifikasi apakah file berhasil disimpan
        if ($request->hasFile('avatar')) {
            $avatarPath = 'avatars/' . $user->id . '.png';
            if (Storage::disk('public')->exists($avatarPath)) {
                $response['avatar'] = Storage::url($avatarPath);
            } else {
                return response()->json(['success' => false, 'error' => 'Failed to Store Avatar'], 500);
            }
        }

        if ($request->hasFile('background')) {
            $bgPath = 'backgrounds/' . $user->id . '.png';
            if (Storage::disk('public')->exists($bgPath)) {
                $response['background'] = Storage::url($bgPath);
            } else {
                return response()->json(['success' => false, 'error' => 'Failed to Store Background'], 500);
            }
        }

        return response()->json($response);
    }

    public function invites($id)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect('/login')->with('error', 'You must be logged in to view your invites.');
        }

        if ($user->id != $id) {
            return redirect()->back()->with('error', "You don't have access to other people's invites.");
        }
        // Ambil semua undangan milik user
        $invites = DB::table('invites')
            ->where('userid', $user->id)
            ->get();

        // Ambil semua kode undangan yang sudah digunakan beserta user yang menggunakannya dan waktu digunakan
        $usedInvites = DB::table('invite_usage')
            ->pluck('code_user', 'invite_code')
            ->toArray();

        $usedAt = DB::table('invite_usage')
            ->pluck('used_at', 'invite_code')
            ->toArray();

        // Ambil data user yang menggunakan invite (jika ada)
        $usedUserIds = array_values($usedInvites);
        $usedUsers = [];
        if (!empty($usedUserIds)) {
            $usedUsers = User::whereIn('id', $usedUserIds)
                ->pluck('name', 'id')
                ->toArray();
        }

        // Tandai setiap undangan apakah sudah digunakan, oleh siapa, kapan, dan id user yang menggunakan
        $invites->transform(function ($invite) use ($usedInvites, $usedUsers, $usedAt) {
            $invite->used = array_key_exists($invite->invite_code, $usedInvites);
            $invite->used_by = $invite->used ? ($usedUsers[$usedInvites[$invite->invite_code]] ?? null) : null;
            $invite->used_at = $invite->used ? ($usedAt[$invite->invite_code] ?? null) : null;
            $invite->used_by_id = $invite->used ? ($usedInvites[$invite->invite_code] ?? null) : null;
            return $invite;
        });

        return view('user.invites', [
            'invites' => $invites
        ]);
    }

    private function decodeMods($modsValue)
    {
        $mods = [];
        $modsValue = (int)$modsValue;

        if ($modsValue & 512) { // Nightcore (512 + 64)
            $mods[] = 'Nightcore';
            $modsValue &= ~(512 | 64);
        }
        if ($modsValue & 16384) { // Perfect (16384 + 32)
            $mods[] = 'Perfect';
            $modsValue &= ~(16384 | 32);
        }

        $modsMap = [
            0 => 'None',
            1 => 'No Fail',
            2 => 'Easy',
            4 => 'TouchDevice',
            8 => 'Hidden',
            16 => 'HardRock',
            32 => 'SuddenDeath',
            64 => 'DoubleTime',
            128 => 'Relax',
            256 => 'HalfTime',
            1024 => 'Flashlight',
            2048 => 'Autoplay',
            4096 => 'SpunOut',
            8192 => 'Autopilot',
            32768 => 'Key4',
            65536 => 'Key5',
            131072 => 'Key6',
            262144 => 'Key7',
            524288 => 'Key8',
            1048576 => 'Fade In',
            2097152 => 'Random',
            4194304 => 'Cinema',
            8388608 => 'Target',
            16777216 => 'Key9',
            33554432 => 'KeyCoop',
            67108864 => 'Key1',
            134217728 => 'Key3',
            268435456 => 'Key2',
            536870912 => 'ScoreV2',
            1073741824 => 'LastMod',
        ];

        foreach ($modsMap as $bit => $name) {
            if ($modsValue & $bit) {
                $mods[] = $name;
            }
        }

        return !empty($mods) ? $mods : ['None'];
    }

    private function getTwemojiFlagUrl(string $countryCode): ?string
    {
        $countryCode = strtoupper($countryCode);

        if (strlen($countryCode) !== 2 || !ctype_alpha($countryCode)) {
            return null;
        }

        $codepoints = [
            'A' => '1f1e6',
            'B' => '1f1e7',
            'C' => '1f1e8',
            'D' => '1f1e9',
            'E' => '1f1ea',
            'F' => '1f1eb',
            'G' => '1f1ec',
            'H' => '1f1ed',
            'I' => '1f1ee',
            'J' => '1f1ef',
            'K' => '1f1f0',
            'L' => '1f1f1',
            'M' => '1f1f2',
            'N' => '1f1f3',
            'O' => '1f1f4',
            'P' => '1f1f5',
            'Q' => '1f1f6',
            'R' => '1f1f7',
            'S' => '1f1f8',
            'T' => '1f1f9',
            'U' => '1f1fa',
            'V' => '1f1fb',
            'W' => '1f1fc',
            'X' => '1f1fd',
            'Y' => '1f1fe',
            'Z' => '1f1ff'
        ];

        $chars = str_split($countryCode);
        $first = $codepoints[$chars[0]] ?? null;
        $second = $codepoints[$chars[1]] ?? null;

        if (!$first || !$second) {
            return null;
        }

        return "https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/svg/{$first}-{$second}.svg";
    }

    private function detectCountryFromIP()
    {
        try {
            $clientIP = request()->ip();

            // Untuk development, gunakan IP publik jika di localhost
            if ($clientIP === '127.0.0.1' || $clientIP === '::1') {
                $clientIP = Http::get('https://api.ipify.org')->body();
            }

            $response = Http::get("http://ip-api.com/json/{$clientIP}?fields=countryCode");

            if ($response->successful()) {
                return $response->json()['countryCode'] ?? null;
            }
        } catch (\Exception $e) {
            Log::error("Failed to detect country from IP: " . $e->getMessage());
        }

        return null;
    }
}
