<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAddressRequest;
use App\Http\Requests\UpdateAddressRequest;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class AddressController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $addresses = $request->user()->addresses()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->latest()
            ->get();

        return AddressResource::collection($addresses);
    }

    public function store(StoreAddressRequest $request): AddressResource
    {
        $address = DB::transaction(function () use ($request) {
            $makeDefault = $request->boolean('is_default')
                || ! $request->user()->addresses()->where('is_active', true)->exists();

            if ($makeDefault) {
                $request->user()->addresses()->update(['is_default' => false]);
            }

            return $request->user()->addresses()->create([
                ...$request->safe()->except('is_default'),
                'is_default' => $makeDefault,
            ]);
        });

        return new AddressResource($address);
    }

    public function update(UpdateAddressRequest $request, string $address): AddressResource
    {
        $address = DB::transaction(function () use ($request, $address) {
            $address = $this->findOwnedAddress($request, $address, true);
            $data = $request->validated();

            if (($data['is_default'] ?? false) === true) {
                $request->user()->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
            }

            if (($data['is_default'] ?? null) === false && $address->is_default) {
                unset($data['is_default']);
            }

            $address->update($data);

            return $address->fresh();
        });

        return new AddressResource($address);
    }

    public function destroy(Request $request, string $address): AnonymousResourceCollection
    {
        DB::transaction(function () use ($request, $address) {
            $address = $this->findOwnedAddress($request, $address, true);
            $wasDefault = $address->is_default;
            $address->update(['is_active' => false, 'is_default' => false]);

            if ($wasDefault) {
                $request->user()->addresses()
                    ->where('is_active', true)
                    ->latest()
                    ->first()?->update(['is_default' => true]);
            }
        });

        return $this->index($request);
    }

    private function findOwnedAddress(Request $request, string $address, bool $lock = false): Address
    {
        $query = $request->user()->addresses()->whereKey($address)->where('is_active', true);

        return ($lock ? $query->lockForUpdate() : $query)->firstOrFail();
    }
}
