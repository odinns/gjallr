@extends('layouts.app')

@section('content')
    <header>
        <h2>{{ $pageData['page']['title'] ?? 'Latest posts' }}</h2>
        <p class="meta">
            This is the first rescue pass. It is meant to work, not to seduce.
        </p>
    </header>

    <section class="post-list">
        @forelse($posts as $post)
            <article>
                <h3><a href="/{{ $post->path }}">{{ $post->title ?? '(untitled)' }}</a></h3>
                <p class="meta">
                    {{ $post->published_at?->format('Y-m-d') ?? 'undated' }}
                    · {{ ucfirst($post->source_type) }}
                </p>
                @if($post->excerpt)
                    <p>{{ $post->excerpt }}</p>
                @endif
            </article>
        @empty
            <p>No rescued posts yet.</p>
        @endforelse
    </section>
@endsection
