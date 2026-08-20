<?php

namespace UasDashboard\WebCurator\Support;

class ContentPreviewSignature
{
    public static function make(string $entitySlug, int $postId, int $expires): string
    {
        $secret = (string) config('web_curator.content_preview.secret');

        if ($secret === '') {
            throw new \RuntimeException('UAS content preview secret is not configured.');
        }

        $payload = strtolower(trim($entitySlug)).'|'.$postId.'|'.$expires;

        return hash_hmac('sha256', $payload, $secret);
    }
}
