<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePackageRequest;
use App\Http\Requests\UpdatePackageRequest;
use App\Models\Package;
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
     * @return View The view displaying the list of packages.
     */
    public function index(): View
    {
        $packages = Package::query()->withoutGlobalScope('selectable')->orderBy('is_selectable', 'desc')->orderBy('price')->get();

        return view('admin.packages.index', compact('packages'));
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
