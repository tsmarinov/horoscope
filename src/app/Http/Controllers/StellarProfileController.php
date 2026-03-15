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

        $profiles = Profile::where('user_id', auth()->id())
            ->with('birthCity')
            ->when($q, fn($query) => $query->where(function ($query) use ($q) {
                $query->where('first_name', 'like', "%{$q}%")
                      ->orWhere('last_name',  'like', "%{$q}%");
            }))
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        return view('stellar-profiles.index', compact('profiles', 'q'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['user_id'] = auth()->id();

        $profile = Profile::create($data);
        $profile->loadMissing('birthCity');
        AspectCalculator::calculate($profile);

        return back()->with('status', 'profile_created');
    }

    public function update(Request $request, Profile $stellarProfile)
    {
        abort_if($stellarProfile->user_id !== auth()->id(), 403);

        // Delete old chart so AspectCalculator recalculates with new birth data
        $stellarProfile->natalChart()->delete();
        $stellarProfile->update($this->validated($request));
        $stellarProfile->loadMissing('birthCity');
        AspectCalculator::calculate($stellarProfile);

        return back()->with('status', 'profile_updated');
    }

    public function destroy(Profile $stellarProfile)
    {
        abort_if($stellarProfile->user_id !== auth()->id(), 403);

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
