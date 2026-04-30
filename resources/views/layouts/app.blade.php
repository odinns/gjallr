<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageData['page']['seo_title'] ?? $pageData['page']['title'] ?? $site->name ?? 'Gjallr' }}</title>
    @if(! empty($pageData['page']['seo_description'] ?? null))
        <meta name="description" content="{{ $pageData['page']['seo_description'] }}">
    @endif
    <style>
        :root {
            --bg: #f1ece2;
            --paper: #fbf7f1;
            --ink: #1e1a16;
            --muted: #6d6459;
            --line: #d2c6b5;
            --accent: #8a5d3b;
            --link: #6d2514;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Georgia, "Times New Roman", serif;
            color: var(--ink);
            background: linear-gradient(180deg, #e7ddcf 0, var(--bg) 180px);
        }
        a { color: var(--link); }
        img { max-width: 100%; height: auto; }
        .shell {
            max-width: 1100px;
            margin: 0 auto;
            padding: 24px;
        }
        .masthead, .card {
            background: var(--paper);
            border: 1px solid var(--line);
            box-shadow: 0 8px 24px rgba(30, 26, 22, 0.08);
        }
        .masthead {
            padding: 20px 24px;
            margin-bottom: 24px;
        }
        .site-title {
            margin: 0;
            font-size: 2rem;
        }
        .site-subtitle {
            margin: 6px 0 0;
            color: var(--muted);
        }
        .layout {
            display: grid;
            grid-template-columns: 260px minmax(0, 1fr);
            gap: 24px;
        }
        .sidebar, .content {
            min-width: 0;
        }
        .card {
            padding: 20px;
        }
        .menu + .menu {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--line);
        }
        .menu h2 {
            font-size: 1rem;
            margin: 0 0 12px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .menu ul {
            margin: 0;
            padding-left: 20px;
        }
        .menu li + li {
            margin-top: 8px;
        }
        .menu li ul {
            margin-top: 8px;
            padding-left: 20px;
            border-left: 2px solid var(--line);
        }
        .post-list article + article,
        .comment-list article + article {
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid var(--line);
        }
        .meta {
            color: var(--muted);
            font-size: 0.95rem;
        }
        .content-body {
            line-height: 1.7;
        }
        .content-body iframe,
        .content-body video {
            max-width: 100%;
        }
        .comment-children {
            margin-left: 20px;
            padding-left: 16px;
            border-left: 2px solid var(--line);
        }
        .page-data {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px dashed var(--line);
        }
        pre {
            white-space: pre-wrap;
            word-break: break-word;
            font-size: 0.9rem;
            background: #efe7db;
            border: 1px solid var(--line);
            padding: 12px;
            overflow-x: auto;
        }
        @media (max-width: 900px) {
            .layout {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="shell">
        <header class="masthead">
            <h1 class="site-title"><a href="/">{{ $site->name ?? 'Gjallr rescue' }}</a></h1>
            <p class="site-subtitle">
                Rescued from WordPress.
                Theme signal: {{ $site->active_theme ?? 'unknown' }}.
            </p>
        </header>

        <div class="layout">
            <aside class="sidebar">
                <div class="card">
                    @forelse($menus as $menu)
                        <nav class="menu">
                            <h2>{{ $menu->name }}</h2>
                            <ul>
                                @foreach($menu->items->whereNull('parent_id')->filter(fn ($item) => $item->url !== null && ($item->contentItem === null || $item->contentItem->status === 'publish')) as $item)
                                    <li>
                                        <a href="{{ $item->url ?? '#' }}">{{ $item->label }}</a>
                                        @php($visibleChildren = $item->children->filter(fn ($child) => $child->url !== null && ($child->contentItem === null || $child->contentItem->status === 'publish')))
                                        @if($visibleChildren->isNotEmpty())
                                            <ul>
                                                @foreach($visibleChildren as $child)
                                                    <li><a href="{{ $child->url ?? '#' }}">{{ $child->label }}</a></li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </nav>
                    @empty
                        <p class="meta">No menus imported yet.</p>
                    @endforelse
                </div>
            </aside>

            <main class="content">
                <div class="card">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>
</body>
</html>
