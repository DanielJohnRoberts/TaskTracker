@extends('layouts.app')

@section('content')
    <section class="edit-head">
        <div>
            <p class="eyebrow">Admin</p>
            <h1>Settings</h1>
        </div>
        <a class="button-link" href="{{ route('dashboard') }}">Dashboard</a>
    </section>

    <form method="POST" action="{{ route('admin.settings.update') }}" class="edit-form">
        @csrf
        @method('PUT')

        <label>
            Public app URL
            <input type="url" name="public_app_url" value="{{ old('public_app_url', $publicAppUrl) }}" required placeholder="https://tasks.example.com">
        </label>

        <p class="field-help">
            Use the exact address people open in the browser. Mobile push works best when this is an HTTPS URL.
        </p>

        <div class="form-actions">
            <button class="primary-button" type="submit">Save settings</button>
        </div>
    </form>
@endsection
