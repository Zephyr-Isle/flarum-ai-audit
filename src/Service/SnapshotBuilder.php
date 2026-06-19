<?php

namespace ZephyrIsle\AiAudit\Service;

use Flarum\Discussion\Discussion;
use Flarum\Post\CommentPost;
use Flarum\Post\Post;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use ZephyrIsle\AiAudit\Support\NetworkUrlGuard;

class SnapshotBuilder
{
    private const MAX_TEXT = 4000;
    private const MAX_IMAGE_BYTES = 4_000_000;

    public function __construct(
        private SettingsRepositoryInterface $settings,
        private LoggerInterface $logger
    ) {
    }

    public function forPost(Post $post): array
    {
        $text = '';
        if ($post instanceof CommentPost) {
            $text = $this->normalizeText($post->content ?? '');
        }

        $discussion = $post->discussion;

        return [
            'subject_type' => 'post',
            'subject_id' => $post->id,
            'content' => [
                'text' => $this->truncate($text),
            ],
            'context' => [
                'discussion_title' => $discussion?->title,
                'author_username' => $post->user?->username,
                'author_display_name' => $post->user?->display_name,
            ],
            'images' => $this->extractAndMaybeFetchImages($post instanceof CommentPost ? (string) $post->content : ''),
        ];
    }

    public function forDiscussion(Discussion $discussion): array
    {
        $firstPost = $discussion->firstPost;
        $text = '';
        $raw = '';
        if ($firstPost instanceof CommentPost) {
            $raw = (string) $firstPost->content;
            $text = $this->normalizeText($raw);
        }

        return [
            'subject_type' => 'discussion',
            'subject_id' => $discussion->id,
            'content' => [
                'title' => $this->truncate((string) $discussion->title, 300),
                'text' => $this->truncate($text),
            ],
            'context' => [
                'author_username' => $discussion->user?->username,
                'author_display_name' => $discussion->user?->display_name,
            ],
            'images' => $this->extractAndMaybeFetchImages($raw),
        ];
    }

    public function forUser(User $user, array $changes): array
    {
        $content = [];
        foreach (['username', 'display_name', 'bio'] as $k) {
            if (array_key_exists($k, $changes)) {
                $content[$k] = is_string($changes[$k]) ? $this->truncate($changes[$k], 800) : '';
            }
        }

        return [
            'subject_type' => 'user',
            'subject_id' => $user->id,
            'content' => $content,
            'context' => [
                'user_id' => $user->id,
                'joined_at' => $user->joined_at?->toIso8601String(),
            ],
            'images' => [],
        ];
    }

    private function normalizeText(string $html): string
    {
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text);
        return trim((string) $text);
    }

    private function truncate(string $text, int $max = self::MAX_TEXT): string
    {
        if (mb_strlen($text) <= $max) return $text;
        return mb_substr($text, 0, $max) . '...';
    }

    private function extractAndMaybeFetchImages(string $raw): array
    {
        $urls = $this->extractImageUrls($raw);
        if ($urls === []) return [];

        $download = (bool) $this->settings->get('zephyrisle.ai-audit.download_images', true);
        if (!$download) {
            return array_map(fn ($u) => ['url' => $u], $urls);
        }

        $images = [];
        foreach ($urls as $u) {
            $data = $this->downloadImageAsDataUri($u);
            if ($data !== null) {
                $images[] = ['data' => $data];
            } else {
                $images[] = ['url' => $u];
            }
        }
        return $images;
    }

    private function extractImageUrls(string $raw): array
    {
        $urls = [];
        if (preg_match_all('/<img\s+[^>]*src=["\']([^"\']+)["\']/i', $raw, $m)) {
            $urls = array_merge($urls, $m[1]);
        }
        if (preg_match_all('/!\[[^\]]*\]\(([^)]+)\)/', $raw, $m)) {
            $urls = array_merge($urls, $m[1]);
        }
        $urls = array_values(array_unique(array_filter($urls, fn ($u) => filter_var($u, FILTER_VALIDATE_URL))));
        return array_slice($urls, 0, 4);
    }

    private function downloadImageAsDataUri(string $url): ?string
    {
        if (!NetworkUrlGuard::isSafeExternalHttpUrl($url)) {
            $this->logger->debug('[AI Audit] blocked unsafe image url', ['url' => $url]);
            return null;
        }

        $timeout = 8;
        $client = new Client([
            'timeout' => $timeout,
            'connect_timeout' => 4,
            'allow_redirects' => false,
        ]);

        try {
            $resp = $client->get($url, [
                'headers' => [
                    'User-Agent' => 'ZephyrIsle-AI-Audit/1.0',
                ],
            ]);

            if ($resp->getStatusCode() !== 200) return null;

            $type = $resp->getHeaderLine('Content-Type');
            if (!str_starts_with($type, 'image/')) return null;

            $body = (string) $resp->getBody();
            if (strlen($body) > self::MAX_IMAGE_BYTES) return null;

            return 'data:' . $type . ';base64,' . base64_encode($body);
        } catch (\Exception $e) {
            $this->logger->debug('[AI Audit] image download failed', ['url' => $url]);
            return null;
        }
    }
}
