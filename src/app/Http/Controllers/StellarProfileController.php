<?php

namespace App\Http\Controllers;

use App\Enums\Gender;
use App\Facades\AspectCalculator;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StellarProfileController extends Controller
{
    public function index(Request $request)
    {
        $q = trim($request->query('q', ''));

        $ownerQuery = auth()->check()
            ? fn() => Profile::where('user_id', auth()->id())
            : fn() => Profile::where('guest_id', $this->currentGuest()?->id ?? 0);

        // If ?edit=uuid is present without ?page, redirect to the correct page
        if (($editUuid = $request->query('edit')) && !$request->query('page')) {
            $editProfile = $ownerQuery()->where('uuid', $editUuid)->first();
            if ($editProfile) {
                $position = $ownerQuery()
                    ->where(function ($q2) use ($editProfile) {
                        $q2->where('first_name', '<', $editProfile->first_name)
                           ->orWhere(function ($q3) use ($editProfile) {
                               $q3->where('first_name', $editProfile->first_name)
                                  ->where('last_name', '<', ($editProfile->last_name ?? ''));
                           });
                    })
                    ->count();
                $perPage = config('horo.stellar_profiles_per_page');
                $page    = (int) floor($position / $perPage) + 1;
                if ($page > 1) {
                    return redirect()->route('stellar-profiles.index',
                        array_filter(['q' => $q, 'page' => $page, 'edit' => $editUuid]));
                }
            }
        }

        $profiles = $ownerQuery()
            ->with('birthCity')
            ->when($q, fn($query) => $query->where(function ($query) use ($q) {
                $query->where('first_name', 'like', "%{$q}%")
                      ->orWhere('last_name',  'like', "%{$q}%")
                      ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$q}%"]);
            }))
            ->orderBy('first_name')->orderBy('last_name')
            ->paginate(config('horo.stellar_profiles_per_page'))
            ->appends(['q' => $q]);

        $allNames = $ownerQuery()
            ->orderBy('first_name')->orderBy('last_name')
            ->get(['first_name', 'last_name'])
            ->map(fn ($p) => trim($p->first_name . ' ' . $p->last_name))
            ->values();

        return view('stellar-profiles.index', compact('profiles', 'q', 'allNames'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        if (auth()->check()) {
            $data['user_id'] = auth()->id();
        } else {
            $guest = $this->currentGuest();
            if (!$guest) abort(403);
            if (Profile::where('guest_id', $guest->id)->count() >= 1) {
                return back()->withErrors(['_guest_limit' => 'Register to create more profiles.'])->withInput();
            }
            $data['guest_id'] = $guest->id;
        }

        $profile = Profile::create($data);
        $profile->loadMissing('birthCity');
        AspectCalculator::calculate($profile);

        return redirect()->route('stellar-profiles.index')->with('status', 'profile_created');
    }

    public function update(Request $request, Profile $stellarProfile)
    {
        abort_if(!$this->ownsProfile($stellarProfile), 403);

        $data = $this->validated($request);

        // Only invalidate generated content when birth data changes
        $birthChanged = $stellarProfile->birth_date?->format('Y-m-d') !== ($data['birth_date'] ?? null)
                     || $stellarProfile->birth_time                    !== ($data['birth_time'] ?? null)
                     || (string) $stellarProfile->birth_city_id        !== (string) ($data['birth_city_id'] ?? null);

        if ($birthChanged) {
            $stellarProfile->natalChart()->delete();
            \App\Models\NatalReport::where('profile_id', $stellarProfile->id)->delete();
            // TODO: when horoscope reports (daily/weekly/monthly/solar/synastry) are implemented,
            // delete their cached AI content here too. premium_usage counter is NOT reset.
        }

        $stellarProfile->update($data);
        $stellarProfile->loadMissing('birthCity');
        AspectCalculator::calculate($stellarProfile);

        return back()->with('status', 'profile_updated');
    }

    public function destroy(Profile $stellarProfile)
    {
        abort_if(!$this->ownsProfile($stellarProfile), 403);

        $stellarProfile->delete();

        return back()->with('status', 'profile_deleted');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'first_name'    => ['required', 'string', 'max:100'],
            'last_name'     => ['nullable', 'string', 'max:100'],
            'gender'        => ['required', Rule::in(array_column(Gender::cases(), 'value'))],
            'birth_date'    => ['required', 'date'],
            'birth_time'    => ['nullable', 'date_format:H:i'],
            'birth_city_id' => ['nullable', 'exists:cities,id'],
        ]);
    }
}
