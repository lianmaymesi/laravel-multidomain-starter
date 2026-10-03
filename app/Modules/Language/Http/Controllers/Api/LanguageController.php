<?php

namespace App\Modules\Language\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Language\Http\Requests\StoreLanguageRequest;
use App\Modules\Language\Http\Requests\UpdateLanguageRequest;
use App\Modules\Language\Http\Resources\LanguageResource;
use App\Modules\Language\Models\Language;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Example API resource: /v1/languages. Token abilities (read/write) are
 * checked by the route; the token user's own permissions (languages.*) by
 * Gate and the form requests — a token never grants more than its user has.
 */
class LanguageController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('languages.view');

        $perPage = min(max($request->integer('per_page', 25), 1), (int) config('api.max_per_page', 100));

        return LanguageResource::collection(
            Language::query()
                ->when($request->has('active'), fn ($query) => $query->where('is_active', $request->boolean('active')))
                ->orderBy('order')
                ->orderBy('id')
                ->paginate($perPage)
                ->withQueryString(),
        );
    }

    public function show(Language $language): LanguageResource
    {
        Gate::authorize('languages.view');

        return new LanguageResource($language);
    }

    public function store(StoreLanguageRequest $request): JsonResponse
    {
        $language = Language::create([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active', true),
            'order' => (int) Language::max('order') + 1,
        ]);

        return (new LanguageResource($language))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(UpdateLanguageRequest $request, Language $language): LanguageResource
    {
        $data = $request->validated();

        if ($request->boolean('is_primary')) {
            // The model demotes the previous primary; a primary is always active.
            $data['is_primary'] = true;
            $data['is_active'] = true;
        }

        $language->update($data);

        return new LanguageResource($language->refresh());
    }

    public function destroy(Language $language): Response
    {
        Gate::authorize('languages.delete');

        if ($language->is_primary) {
            throw ValidationException::withMessages([
                'language' => __('The primary language can\'t be deleted — set another language as primary first.'),
            ]);
        }

        $language->delete();

        return response()->noContent();
    }
}
