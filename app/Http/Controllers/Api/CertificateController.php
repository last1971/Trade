<?php

namespace App\Http\Controllers\Api;

use App\Certificate;
use App\CertificateGood;
use App\Http\Requests\ModelRequest;
use App\Marketplace;
use App\Services\CertificateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CertificateController extends ModelController
{
    public function __construct()
    {
        parent::__construct(CertificateService::class);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Certificate
     */
    public function store(Request $request)
    {
        abort_unless($request->user()->can('certificate.store'), 403);
        $request->validate([
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png',
            'number' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'name' => 'nullable|string|max:255',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'remark' => 'nullable|string|max:255',
        ]);
        $file = $request->file('file');
        return Certificate::create([
            'number' => $request->number,
            'type' => $request->type,
            'name' => $request->name,
            'file_path' => $file->store('certificates'),
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'remark' => $request->remark,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param ModelRequest $request
     * @param int $id
     * @return void
     * @throws \Exception
     */
    public function destroy(ModelRequest $request, $id)
    {
        $certificate = Certificate::query()->findOrFail(intval($id));
        Storage::delete($certificate->file_path);
        return $certificate->delete();
    }

    /**
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function download($id)
    {
        $certificate = Certificate::query()->findOrFail(intval($id));
        return Storage::download($certificate->file_path, $certificate->original_name);
    }

    /**
     * @param Request $request
     * @param int $id
     * @return Certificate
     */
    public function attachGoods(Request $request, $id)
    {
        $request->validate([
            'good_ids' => 'required|array',
            'good_ids.*' => 'integer',
        ]);
        $certificate = Certificate::query()->findOrFail(intval($id));
        foreach ($request->good_ids as $goodId) {
            CertificateGood::query()->firstOrCreate([
                'certificate_id' => $certificate->id,
                'good_id' => $goodId,
            ]);
        }
        return $certificate->load('certificateGoods.good.name', 'marketplaces');
    }

    /**
     * @param int $id
     * @param int $goodId
     * @return void
     */
    public function detachGood($id, $goodId)
    {
        CertificateGood::query()
            ->where('certificate_id', intval($id))
            ->where('good_id', intval($goodId))
            ->delete();
    }

    /**
     * Certificates attached to the good.
     *
     * @param int $goodscode
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function forGood($goodscode)
    {
        return Certificate::query()
            ->whereHas('certificateGoods', function ($query) use ($goodscode) {
                $query->where('good_id', intval($goodscode));
            })
            ->with('marketplaces')
            ->orderBy('number')
            ->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function marketplaces()
    {
        return Marketplace::query()->orderBy('name')->get();
    }

    /**
     * Distinct certificate types already used in the registry.
     *
     * @return \Illuminate\Support\Collection
     */
    public function types()
    {
        return Certificate::query()->distinct()->orderBy('type')->pluck('type');
    }

    /**
     * Mark certificate as uploaded to marketplace (created on the fly by name).
     *
     * @param Request $request
     * @param int $id
     * @return Certificate
     */
    public function markMarketplace(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'uploaded_at' => 'nullable|date',
        ]);
        $certificate = Certificate::query()->findOrFail(intval($id));
        $marketplace = Marketplace::query()->firstOrCreate(['name' => trim($request->name)]);
        $certificate->marketplaces()->syncWithoutDetaching([
            $marketplace->id => ['uploaded_at' => $request->uploaded_at ?? today()],
        ]);
        return $certificate->load('certificateGoods.good.name', 'marketplaces');
    }

    /**
     * @param int $id
     * @param int $marketplaceId
     * @return Certificate
     */
    public function unmarkMarketplace($id, $marketplaceId)
    {
        $certificate = Certificate::query()->findOrFail(intval($id));
        $certificate->marketplaces()->detach(intval($marketplaceId));
        return $certificate->load('certificateGoods.good.name', 'marketplaces');
    }
}
