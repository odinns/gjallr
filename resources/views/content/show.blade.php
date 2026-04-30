@extends('layouts.app')

@section('content')
    <article>
        <header>
            <h2>{{ $contentItem->title ?? '(untitled)' }}</h2>
            <p class="meta">
                {{ ucfirst($contentItem->source_type) }}
                @if($contentItem->published_at)
                    · {{ $contentItem->published_at->format('Y-m-d H:i') }}
                @endif
                · /{{ $contentItem->path }}
            </p>
        </header>

        @if($contentItem->excerpt)
            <p><strong>{{ $contentItem->excerpt }}</strong></p>
        @endif

        <div class="content-body">
            {!! str_replace(["\r\n", "\r", "\n"], '<br/>', $contentItem->body_html ?? '') !!}
        </div>

        @if(! empty($pageData['page']['taxonomies']))
            <section class="page-data">
                <h3>Taxonomies</h3>
                <p>
                    @foreach($pageData['page']['taxonomies'] as $taxonomy)
                        <a href="{{ $taxonomy['path'] }}">{{ $taxonomy['name'] }}</a>@if(! $loop->last), @endif
                    @endforeach
                </p>
            </section>
        @endif

        @if(! empty($pageData['page']['comments']))
            <section class="page-data">
                <h3>Comments</h3>
                <div class="comment-list">
                    @foreach($pageData['page']['comments'] as $comment)
                        <article>
                            <p><strong>{{ $comment['author_name'] ?? 'Anonymous' }}</strong></p>
                            <p class="meta">{{ $comment['created_at'] ?? '' }}</p>
                            <div>{!! nl2br(e($comment['body'])) !!}</div>

                            @if(! empty($comment['children']))
                                <div class="comment-children">
                                    @foreach($comment['children'] as $child)
                                        <article>
                                            <p><strong>{{ $child['author_name'] ?? 'Anonymous' }}</strong></p>
                                            <p class="meta">{{ $child['created_at'] ?? '' }}</p>
                                            <div>{!! nl2br(e($child['body'])) !!}</div>
                                        </article>
                                    @endforeach
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="page-data">
            <h3>AI-friendly page data</h3>
            <pre>{{ json_encode($pageData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </section>
    </article>
@endsection
