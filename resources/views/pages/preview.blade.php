<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ html_entity_decode($page['page_title'] ?? 'Page Preview') }}</title>
    <!-- Import DM Sans and DM Serif Display font from Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=DM+Serif+Display:wght@400;500;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/css/web-curator/rendered-content.css', 'resources/js/web-curator/rendered-content.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    <div class="mx-auto flex min-h-screen w-full items-start justify-center px-4 py-8 sm:px-6 lg:px-8">
        <article class="w-full max-w-[1024px] overflow-hidden rounded-3xl bg-white shadow-[0_20px_60px_rgba(15,23,42,0.08)] ring-1 ring-slate-200/70">
            @if (!empty($page['featured_image_uri']))
                <div class="overflow-hidden border-b border-slate-200/80">
                    <img
                        src="{{ $page['featured_image_uri'] }}"
                        alt="{!! html_entity_decode($page['page_title'] ?? 'Featured image') !!}"
                        class="h-auto max-h-[28rem] w-full object-cover"
                    >
                </div>
            @endif

            <div class="px-6 py-8 sm:px-12 sm:py-10">
                <header class="mb-8">
                    <h1 class="text-3xl font-bold leading-tight text-slate-900 sm:text-4xl">
                        {!! html_entity_decode($page['page_title'] ?? 'Untitled Page') !!}
                    </h1>

                    @if (!empty($page['page_excerpt']))
                        <p class="mt-4 max-w-3xl text-base leading-7 text-slate-600 sm:text-lg">
                            {!! nl2br(e(html_entity_decode($page['page_excerpt']))) !!}
                        </p>
                    @endif
                </header>

                <div class="wc-rendered-content">
                    {!! $page['page_content'] ?? '' !!}
                </div>
            </div>
        </article>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.WebCuratorRenderedContent?.enhanceAll(document);
        });
    </script>
</body>
</html>
