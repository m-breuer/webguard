<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServerInstanceRequest;
use App\Http\Requests\UpdateServerInstanceRequest;
use App\Models\ServerInstance;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ServerInstanceController extends Controller
{
    public function index(): View
    {
        $instances = ServerInstance::query()->orderBy('code')->get();

        return view('admin.server-instances.index', ['instances' => $instances]);
    }

    public function create(): View
    {
        return view('admin.server-instances.create');
    }

    public function store(StoreServerInstanceRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        ServerInstance::query()->create([
            'code' => $validated['code'],
            'api_key_hash' => $validated['api_key'],
            'is_active' => $validated['is_active'] ?? false,
        ]);

        return to_route('admin.server-instances.index')->with('success', __('admin.server_instances.messages.instance_created'));
    }

    public function edit(ServerInstance $serverInstance): View
    {
        return view('admin.server-instances.edit', ['instance' => $serverInstance]);
    }

    public function update(UpdateServerInstanceRequest $request, ServerInstance $serverInstance): RedirectResponse
    {
        $validated = $request->validated();

        $data = [
            'code' => $validated['code'],
            'is_active' => $validated['is_active'] ?? false,
        ];

        if (! empty($validated['api_key'])) {
            $data['api_key_hash'] = $validated['api_key'];
        }

        $serverInstance->update($data);

        return to_route('admin.server-instances.index')->with('success', __('admin.server_instances.messages.instance_updated'));
    }

    public function destroy(ServerInstance $serverInstance): RedirectResponse
    {
        if ($serverInstance->monitorings()->exists()) {
            return to_route('admin.server-instances.index')->with('error', __('admin.server_instances.messages.instance_in_use'));
        }

        $serverInstance->delete();

        return to_route('admin.server-instances.index')->with('success', __('admin.server_instances.messages.instance_deleted'));
    }
}
