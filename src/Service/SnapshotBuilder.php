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

    /**
     * Build a snapshot for a post (reply content).
     * Includes OP content as context for better AI reasoning.
     */
    public function forPost(Post $post): array
    {
        $text = '';
        $raw = '';
        if ($post instanceof CommentPost) {
            $raw = (string) $post->content;
            $text = $this->normalizeText($raw);
        }

        $discussion = $post->discussion;

        $snapshot = [
            'subject_type' => 'post_content',
            'subject_id' => $post->id,
            'content' => [
                'text' => $this->truncate($text),
            ],
            'context' => $this->buildPostContext($post),
            'images' => $this->extractAndMaybeFetchImages($raw),
        ];

        // Include first post content as context for replies
        if ($discussion && $post->number > 1) {
            $firstPost = $discussion->firstPost;
            if ($firstPost && $firstPost instanceof CommentPost && $firstPost->id !== $post->id) {
                $firstText = $this->normalizeText((string) $firstPost->content);
                $snapshot['context']['op_content'] = $this->truncate($firstText);
            }
        }

        return $snapshot;
    }

    /**
     * Build a snapshot for a discussion (new topic).
     */
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
            'subject_type' => 'discussion_title',
            'subject_id' => $discussion->id,
            'content' => [
                'title' => $this->truncate((string) $discussion->title, 300),
                'text' => $this->truncate($text),
            ],
            'context' => [
                'author_username' => $discussion->user?->username,
                'author_display_name' => $discussion->user?->display_name,
                'author_id' => $discussion->user_id,
            ],
            'images' => $this->extractAndMaybeFetchImages($raw),
        ];
    }

    /**
     * Build a snapshot for a user profile change (username, display_name, etc.).
     */
    public function forUser(User $user, array $changes): array
    {
        $content = [];
        foreach (['username', 'display_name', 'nickname', 'bio'] as $k) {
            if (array_key_exists($k, $changes)) {
                $content[$k] = is_string($changes[$k]) ? $this->truncate($changes[$k], 800) : '';
            }
        }

        return [
            'subject_type' => 'user_username',
            'subject_id' => $user->id,
            'content' => $content,
            'context' => [
                'user_id' => $user->id,
                'username' => $user->username,
                'joined_at' => $user->joined_at?->toIso8601String(),
                'discussion_count' => $user->discussion_count ?? 0,
                'post_count' => $user->comment_count ?? 0,
            ],
            'images' => [],
        ];
    }

    /**
     * Build a snapshot for avatar changes.
     */
    public function forUserAvatar(User $user, array $changes): array
    {
        $newAvatarUrl = $changes['newAvatarUrl'] ?? $changes['avatarUrl'] ?? null;
        $oldAvatarUrl = $changes['oldAvatarUrl'] ?? null;

        $images = [];
        if ($newAvatarUrl) {
            $downloaded = $this->extractAndMaybeFetchImages('<img src="' . htmlspecialchars($newAvatarUrl) . '"/>');
            $images = $downloaded;
        }

        return [
            'subject_type' => 'user_avatar',
            'subject_id' => $user->id,
            'content' => [
                'action' => $newAvatarUrl ? 'update' : 'delete',
                'old_avatar_url' => $oldAvatarUrl,
                'new_avatar_url' => $newAvatarUrl,
            ],
            'context' => [
                'user_id' => $user->id,
                'username' => $user->username,
                'joined_at' => $user->joined_at?->toIso8601String(),
            ],
            'images' => $images,
        ];
    }

    /**
     * Build a snapshot for nickname changes (flarum/nicknames).
     */
    public function forUserNickname(User $user, array $changes): array
    {
        return [
            'subject_type' => 'user_nickname',
            'subject_id' => $user->id,
            'content' => [
                'old_nickname' => $changes['oldNickname'] ?? $user->nickname ?? '',
                'new_nickname' => $changes['nickname'] ?? '',
            ],
            'context' => [
                'user_id' => $user->id,
                'username' => $user->username,
                'display_name' => $user->display_name,
            ],
            'images' => [],
        ];
    }

    /**
     * Build a snapshot for bio changes (fof/user-bio).
     */
    public function forUserBio(User $user, array $changes): array
    {
        return [
            'subject_type' => 'user_bio',
            'subject_id' => $user->id,
            'content' => [
                'old_bio' => $changes['oldBio'] ?? $user->bio ?? '',
                'new_bio' => $changes['bio'] ?? '',
            ],
            'context' => [
                'user_id' => $user->id,
                'username' => $user->username,
            ],
            'images' => [],
        ];
    }

    /**
     * Build a snapshot for profile cover changes (forumaker/profile-cover).
     */
    public function forUserCover(User $user, array $changes): array
    {
        return [
            'subject_type' => 'user_cover',
            'subject_id' => $user->id,
            'content' => [
                'action' => isset($changes['cover']) ? 'update' : 'delete',
                'cover_url' => $changes['cover'] ?? null,
            ],
            'context' => [
                'user_id' => $user->id,
                'username' => $user->username,
            ],
            'images' => [],
        ];
    }

    /**
     * Build a snapshot for post images (external URLs in post content).
     */
    public function forPostImage(Post $post): array
    {
        $raw = '';
        if ($post instanceof CommentPost) {
            $raw = (string) $post->content;
        }

        $text = $this->normalizeText($raw);

        return [
            'subject_type' => 'post_image',
            'subject_id' => $post->id,
            'content' => [
                'text' => $this->truncate($text),
            ],
            'context' => $this->buildPostContext($post),
            'images' => $this->extractAndMaybeFetchImages($raw),
        ];
    }

    /**
     * Build a snapshot for uploaded files (fof/upload).
     */
    public function forUploadedFile(object $upload): array
    {
        $post = null;
        if (method_exists($upload, 'post')) {
            $post = $upload->post;
        } elseif (property_exists($upload, 'post_id')) {
            $post = Post::find($upload->post_id);
        }

        $fileName = '';
        if (method_exists($upload, 'getDisplayName')) {
            $fileName = $upload->getDisplayName();
        } elseif (method_exists($upload, 'getAttribute') && $upload->getAttribute('base_name')) {
            $fileName = $upload->getAttribute('base_name');
        }

        $context = [];
        if ($post) {
            $context = $this->buildPostContext($post);
            $context['post_content'] = $post instanceof CommentPost
                ? $this->truncate($this->normalizeText((string) $post->content))
                : '';
        }

        return [
            'subject_type' => 'upload_file',
            'subject_id' => $post?->id ?? 0,
            'content' => [
                'file_name' => $fileName,
                'description' => $context['post_content'] ?? '',
            ],
            'context' => $context,
            'images' => [],
        ];
    }

    /**
     * Build a snapshot for discussion title changes.
     */
    public function forDiscussionTitle(Discussion $discussion, array $changes): array
    {
        return [
            'subject_type' => 'discussion_title',
            'subject_id' => $discussion->id,
            'content' => [
                'old_title' => $changes['oldTitle'] ?? $discussion->title,
                'new_title' => $changes['title'] ?? '',
            ],
            'context' => [
                'author_username' => $discussion->user?->username,
                'author_display_name' => $discussion->user?->display_name,
                'author_id' => $discussion->user_id,
                'discussion_id' => $discussion->id,
            ],
            'images' => [],
        ];
    }

    private function buildPostContext(Post $post): array
    {
        $discussion = $post->discussion;
        return [
            'discussion_id' => $discussion?->id,
            'discussion_title' => $discussion?->title,
            'post_number' => $post->number,
            'author_username' => $post->user?->username,
            'author_display_name' => $post->user?->display_name,
            'author_id' => $post->user_id,
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

        $timeout = (int) $this->settings->get('zephyrisle.ai-audit.image_download_timeout', 8);
        $connectTimeout = max(1, min($timeout / 2, 10));

        $client = new Client([
            'timeout' => $timeout,
            'connect_timeout' => $connectTimeout,
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
