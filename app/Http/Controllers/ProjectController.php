<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Client;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status', 'client_id']);

        $projects = Project::query()
            ->with('client:id,short_name,legal_name')
            ->search($request->string('search')->toString())
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('client_id'), fn ($query) => $query->where('client_id', $request->integer('client_id')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Projects/Index', [
            'projects' => $projects,
            'filters' => $filters,
            'clients' => $this->clientOptions(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Projects/Create', [
            'clients' => $this->clientOptions(),
        ]);
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        Project::create([
            ...$request->validated(),
            'paid_amount' => 0,
        ]);

        return redirect()->route('projects.index')->with('success', 'Проект создан.');
    }

    public function edit(Project $project): Response
    {
        return Inertia::render('Projects/Edit', [
            'project' => $project->load('client:id,short_name,legal_name'),
            'clients' => $this->clientOptions(),
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $project->update($request->validated());

        return redirect()->route('projects.index')->with('success', 'Проект обновлён.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->archive();

        return redirect()->route('projects.index')->with('success', 'Проект архивирован.');
    }

    private function clientOptions()
    {
        return Client::query()
            ->where('status', Client::STATUS_ACTIVE)
            ->orderBy('short_name')
            ->get(['id', 'short_name', 'legal_name']);
    }
}
