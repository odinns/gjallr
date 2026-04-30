@extends('layouts.app')

@section('content')
    <header>
        <h2>{{ $taxonomy->name }}</h2>
        <p class="meta">
            {{ ucfirst($taxonomy->type) }} · /{{ $taxonomy->path }}
        </p>
        @if($taxonomy->description)
            <p>{{ $taxonomy->description }}</p>
        @endif
    </header>

    <section class="post-list">
        @forelse($contentItems as $contentItem)
            <article>
                <h3><a href="/{{ $contentItem->path }}">{{ $contentItem->title ?? '(untitled)' }}</a></h3>
                <p class="meta">{{ ucfirst($contentItem->source_type) }}</p>
                @if($contentItem->excerpt)
                    <p>{{ $contentItem->excerpt }}</p>
                @endif
            </article>
        @empty
            <p>No rescued content for this taxonomy yet.</p>
        @endforelse
    </section>

    <section class="page-data">
        <h3>AI-friendly page data</h3>
        <pre>{{ json_encode($pageData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    </section>
@endsection
