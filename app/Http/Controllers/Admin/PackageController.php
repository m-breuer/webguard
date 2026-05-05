<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePackageRequest;
use App\Http\Requests\UpdatePackageRequest;
use App\Models\Package;
use App\Support\Admin\AsyncTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Class PackageController
 *
 * Handles administrative operations for managing subscription packages.
 */
class PackageController extends Controller
{
    /**
     * Display a listing of the packages.
     *
     * @return View|JsonResponse The view displaying the list of packages or async table rows.
     */
    public function index(Request $request): View|JsonResponse
    {
        $validated = $request->validate(AsyncTable::requestRules([
            'search' => ['nullable', 'string', 'max:100'],
            'is_selectable' => ['nullable', 'string', 'in:1,0'],
        ], ['monitoring_limit', 'price', 'is_selectable', 'created_at', 'updated_at']));
        $asyncTableOptions = AsyncTable::options($validated, 'price', 'asc', 10);

        $lengthAwarePaginator = Package::query()
            ->withoutGlobalScope('selectable')
            ->when($validated['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder->where('monitoring_limit', 'like', '%' . $search . '%')
                        ->orWhere('price', 'like', '%' . $search . '%');
                });
            })
            ->when(isset($validated['is_selectable']), fn (Builder $builder): Builder => $builder->where('is_selectable', (bool) $validated['is_selectable']))
            ->orderBy($asyncTableOptions->sort, $asyncTableOptions->direction)
            ->orderBy('id')
            ->paginate($asyncTableOptions->perPage);

        if ($request->expectsJson()) {
            return AsyncTable::json($lengthAwarePaginator, 'admin.packages.partials.rows', ['packages' => $lengthAwarePaginator]);
        }

        return view('admin.packages.index', [
            'packages' => $lengthAwarePaginator,
            'filters' => [
                [
                    'name' => 'is_selectable',
                    'label' => __('admin.packages.fields.is_selectable'),
                    'placeholder' => __('search.filter.text', ['attribute' => __('admin.packages.fields.is_selectable')]),
                    'options' => [
                        '1' => __('admin.packages.fields.yes'),
                        '0' => __('admin.packages.fields.no'),
                    ],
                ],
            ],
            'activeFilters' => [
                'is_selectable' => (string) ($validated['is_selectable'] ?? ''),
            ],
            'sort' => $asyncTableOptions->sort,
            'direction' => $asyncTableOptions->direction,
        ]);
    }

    /**
     * Show the form for creating a new package.
     *
     * @return View The view for creating a new package.
     */
    public function create(): View
    {
        return view('admin.packages.create');
    }

    /**
     * Store a newly created package in storage.
     *
     * @param  Request  $storePackageRequest  The HTTP request instance containing package data.
     * @return RedirectResponse A redirect response after storing the package.
     */
    public function store(StorePackageRequest $storePackageRequest): RedirectResponse
    {
        Package::query()->create($storePackageRequest->validated());

        return to_route('admin.packages.index')->with('success', __('admin.packages.messages.package_created'));
    }

    /**
     * Show the form for editing the specified package.
     *
     * @param  string  $id  The ID of the package to edit.
     * @return View The view for editing the package.
     */
    public function edit(string $id): View
    {
        $package = Package::query()->withoutGlobalScope('selectable')->findOrFail($id);

        return view('admin.packages.edit', compact('package'));
    }

    /**
     * Update the specified package in storage.
     *
     * @param  Request  $updatePackageRequest  The HTTP request instance containing updated package data.
     * @param  string  $id  The ID of the package to update.
     * @return RedirectResponse A redirect response after updating the package.
     */
    public function update(UpdatePackageRequest $updatePackageRequest, string $id): RedirectResponse
    {
        $package = Package::query()->findOrFail($id);
        $package->update($updatePackageRequest->validated());

        return to_route('admin.packages.index')->with('success', __('admin.packages.messages.package_updated'));
    }

    /**
     * Remove the specified package from storage.
     *
     * @param  string  $id  The ID of the package to delete.
     * @return RedirectResponse A redirect response after deleting the package.
     */
    public function destroy(string $id): RedirectResponse
    {
        $package = Package::query()->withoutGlobalScope('selectable')->findOrFail($id);

        if ($package->users()->exists()) {
            return to_route('admin.packages.index')
                ->with('error', __('admin.packages.messages.package_in_use'));
        }

        $package->delete();

        return to_route('admin.packages.index')->with('success', __('admin.packages.messages.package_deleted'));
    }
}
