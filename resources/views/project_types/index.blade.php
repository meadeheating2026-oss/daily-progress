@extends('layouts.app')
@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('dashboard') }}" class="text-muted text-decoration-none">
            {{ __('general.dashboard') }}
        </a>
    </li>
@endsection
@section('content')
    <div class="container py-4">

        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold text-primary mb-0">
                <i class="fa-solid fa-diagram-project me-2"></i> {{ __('general.project_types') }}
            </h3>
            @can('project-types-create')
                <a href="{{ route('project_types.create') }}" class="btn btn-success rounded-pill shadow-sm">
                    <i class="fa-solid fa-plus me-1"></i> {{ __('general.add_project_type') }}
                </a>
            @endcan
        </div>

        
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <i class="fa-solid fa-circle-check me-1"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"
                    aria-label="{{ __('general.close') }}"></button>
            </div>
        @endif

        
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 60px">#</th>
                            <th>{{ __('general.name') }}</th>
                            @canany(['project-types-edit', 'project-types-delete'])
                                <th class="text-center" style="width: 180px">{{ __('general.actions') }}</th>
                            @endcanany
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($types as $type)
                            <tr>
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td>{{ $type->name }}</td>
                                @canany(['project-types-edit', 'project-types-delete'])
                                    <td class="text-center">
                                        @can('project-types-edit')
                                        <a href="{{ route('project_types.edit', $type->id) }}"
                                            class="btn btn-sm btn-outline-primary me-1"
                                            title="{{ __('general.edit') }}">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @endcan
                                        @can('project-types-delete')
                                        <form action="{{ route('project_types.destroy', $type->id) }}" method="POST"
                                            class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button onclick="return confirm('{{ __('general.confirm_delete') }}')"
                                                class="btn btn-sm btn-outline-danger"
                                                title="{{ __('general.delete') }}">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        @endcan
                                    </td>
                                @endcanany
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">
                                    <i class="fa-regular fa-folder-open fa-2x mb-2 d-block"></i>
                                    {{ __('general.no_project_types') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
