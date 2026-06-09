<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ html_entity_decode($post['post_title'] ?? 'Post Preview') }}</title>
    <!-- Import DM Sans and DM Serif Display font from Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=DM+Serif+Display:wght@400;500;700&display=swap" rel="stylesheet">
    @vite('resources/css/app.css')
    @moduleVite('web_curator', 'rendered-content')
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    @php
        $apiBaseUrl = rtrim((string) config('web-api.api_base_url'), '/');
        $apiWebBaseUrl = preg_replace('#/api$#', '', $apiBaseUrl);
        $attachments = is_array($post['attachments'] ?? null) ? $post['attachments'] : [];

        $normalizeAttachmentUrl = static function (?string $value) use ($apiWebBaseUrl): string {
            $url = trim((string) ($value ?? ''));
            if ($url === '') {
                return '';
            }

            $escapedOrigin = preg_quote($apiWebBaseUrl, '#');
            $url = preg_replace("#^{$escapedOrigin}(?=https?:?/?/?)#i", '', $url) ?? $url;

            if (preg_match('/^(https?):?(\/\/)?(.*)$/i', $url, $matches)) {
                $url = strtolower($matches[1]) . '://' . ltrim($matches[3], '/');
            }

            if (preg_match('/^(https?:\/\/[^\/]+)(https?:\/\/.+)$/i', $url, $matches)) {
                $url = $matches[2];
            }

            return $url;
        };

        $formatAttachmentUrl = static function (array $attachment) use ($normalizeAttachmentUrl, $apiWebBaseUrl): string {
            $directUrl = $normalizeAttachmentUrl(
                $attachment['url'] ?? $attachment['full_url'] ?? $attachment['attachment_url'] ?? ''
            );

            if ($directUrl !== '') {
                return $directUrl;
            }

            $uri = $normalizeAttachmentUrl($attachment['attachment_uri'] ?? '');
            if ($uri === '') {
                return '';
            }

            if (preg_match('#^https?://#i', $uri)) {
                return $uri;
            }

            return rtrim($apiWebBaseUrl, '/') . '/storage/' . ltrim($uri, '/');
        };

        $formatAttachmentSize = static function (array $attachment): string {
            if (!empty($attachment['formatted_size'])) {
                return (string) $attachment['formatted_size'];
            }

            $size = (int) ($attachment['file_size'] ?? 0);
            if ($size <= 0) {
                return '';
            }

            $units = ['B', 'KB', 'MB', 'GB'];
            $value = (float) $size;
            $unitIndex = 0;

            while ($value >= 1024 && $unitIndex < count($units) - 1) {
                $value /= 1024;
                $unitIndex++;
            }

            return round($value, 2) . ' ' . $units[$unitIndex];
        };
    @endphp
    <div class="mx-auto flex min-h-screen w-full items-start justify-center px-4 py-8 sm:px-6 lg:px-8">
        <article class="w-full max-w-[1024px] overflow-hidden rounded-3xl bg-white shadow-[0_20px_60px_rgba(15,23,42,0.08)] ring-1 ring-slate-200/70">
            @if (!empty($post['featured_image_uri']))
                <div class="overflow-hidden border-b border-slate-200/80">
                    <img
                        src="{{ $post['featured_image_uri'] }}"
                        alt="{{ $post['post_title'] ?? 'Featured image' }}"
                        class="h-auto max-h-[28rem] w-full object-cover"
                    >
                </div>
            @endif

            <div class="px-6 py-8 sm:px-12 sm:py-10">
                <header class="mb-8">
                    <h1 class="text-3xl font-bold leading-tight text-slate-900 sm:text-4xl">
                        {{ html_entity_decode($post['post_title'] ?? 'Untitled Post') }}
                    </h1>

                    @if (!empty($post['post_excerpt']))
                        <p class="mt-4 max-w-3xl text-base leading-7 text-slate-600 sm:text-lg">
                            {!! nl2br(e(html_entity_decode($post['post_excerpt']))) !!}
                        </p>
                    @endif
                </header>

                @if (!empty($attachments))
                    <section class="mb-8 rounded-2xl border border-slate-200 bg-slate-50/70 p-5 sm:p-6">
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <h2 class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-500">
                                Attachments
                            </h2>
                            <div class="text-sm text-slate-500">
                                {{ count($attachments) }} {{ \Illuminate\Support\Str::plural('file', count($attachments)) }}
                            </div>
                        </div>

                        <div class="grid gap-3">
                            @foreach ($attachments as $attachment)
                                @php
                                    $attachmentUrl = $formatAttachmentUrl((array) $attachment);
                                    $attachmentTitle = $attachment['attachment_title']
                                        ?? $attachment['file_name']
                                        ?? $attachment['original_name']
                                        ?? 'Untitled attachment';
                                    $attachmentType = $attachment['attachment_type'] ?? 'file';
                                    $attachmentSize = $formatAttachmentSize((array) $attachment);
                                    $attachmentDescription = trim((string) ($attachment['description'] ?? ''));
                                @endphp
                                <div class="flex items-start justify-between gap-4 rounded-xl border border-slate-200 bg-white px-4 py-3">
                                    <div class="min-w-0 flex-1">
                                        <div class="truncate text-sm font-medium text-slate-900">
                                            {{ html_entity_decode($attachmentTitle) }}
                                        </div>
                                        <div class="mt-1 text-xs text-slate-500">
                                            {{ $attachmentType }}@if($attachmentSize !== '') · {{ $attachmentSize }}@endif
                                        </div>
                                        @if ($attachmentDescription !== '')
                                            <div class="mt-1 text-sm text-slate-600">
                                                {{ $attachmentDescription }}
                                            </div>
                                        @endif
                                    </div>
                                    @if ($attachmentUrl !== '')
                                        <a
                                            href="{{ $attachmentUrl }}"
                                            target="_blank"
                                            rel="noopener"
                                            download
                                            class="inline-flex shrink-0 items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-400 hover:bg-slate-100"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12.554 16.506a.75.75 0 0 1-1.107 0l-4-4.375a.75.75 0 0 1 1.107-1.012l2.696 2.95V3a.75.75 0 0 1 1.5 0v11.068l2.697-2.95a.75.75 0 1 1 1.107 1.013z"/><path fill="currentColor" d="M3.75 15a.75.75 0 0 0-1.5 0v.055c0 1.367 0 2.47.117 3.337c.12.9.38 1.658.981 2.26c.602.602 1.36.86 2.26.982c.867.116 1.97.116 3.337.116h6.11c1.367 0 2.47 0 3.337-.116c.9-.122 1.658-.38 2.26-.982s.86-1.36.982-2.26c.116-.867.116-1.97.116-3.337V15a.75.75 0 0 0-1.5 0c0 1.435-.002 2.436-.103 3.192c-.099.734-.28 1.122-.556 1.399c-.277.277-.665.457-1.4.556c-.755.101-1.756.103-3.191.103H9c-1.435 0-2.437-.002-3.192-.103c-.734-.099-1.122-.28-1.399-.556c-.277-.277-.457-.665-.556-1.4c-.101-.755-.103-1.756-.103-3.191"/></svg>
                                            Download
                                        </a>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                <div class="wc-rendered-content">
                    {!! $post['post_content'] ?? '' !!}
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
