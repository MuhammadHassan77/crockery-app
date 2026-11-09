@extends('layouts.app')

@section('content')
    <div class="container">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Laravel Logs</h2>

            @if ($fileExists)
                <a href="{{ route('admin.logs.download', $date) }}" class="btn btn-primary">
                    Download
                </a>
            @endif
        </div>

        <form method="GET" action="{{ route('admin.logs') }}" class="mb-3">
            <div class="row">
                <div class="col-md-3">
                    @if (config('log'))
                        <input type="hidden" name="date" value="laravel">
                    @else
                        <input type="date" name="date" value="{{ $date }}" class="form-control">
                    @endif
                </div>

                <div class="col-md-2">
                    <button class="btn btn-secondary">
                        View Logs
                    </button>
                </div>
            </div>
        </form>

        @if (!$fileExists)
            <div class="alert alert-warning">
                No log file exists for {{ $date }}.
            </div>
        @else
            <div
                style="
                    background:#111;
                    color:#ddd;
                    padding:20px;
                    max-height:700px;
                    overflow:auto;
                    white-space:pre-wrap;
                    font-family:monospace;
                ">
                {{ $logs }}</div>
        @endif

    </div>
@endsection
