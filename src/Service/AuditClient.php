<?php

namespace ZephyrIsle\AiAudit\Service;

use Flarum\Settings\SettingsRepositoryInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

class AuditClient
{
    private const FORMAT_VERSION = 'zia_audit_v2';

    public function __construct(
        private SettingsRepositoryInterface $settings,
        private LoggerInterface $logger
    ) {
    }

    public function isConfigured(): bool
    {
        return trim((string) $this->settings->get('zephyrisle.ai-audit.api_key', '')) !== '';
    }

    public function analyze(array $snapshot): array
    {
        $signals = $this->computeSignals($snapshot);

        $result = [
            'format_version' => self::FORMAT_VERSION,
            'signals' => $signals,
            'llm' => null,
            'decision' => null,
            'request' => null,
            'response' => null,
            'error' => null,
        ];

        $reviewThreshold = $this->clamp01((float) $this->settings->get('zephyrisle.ai-audit.review_threshold', 0.55));
        $actionThreshold = max(
            $reviewThreshold,
            $this->clamp01((float) $this->settings->get('zephyrisle.ai-audit.action_threshold', 0.75))
        );

        $riskBase = (float) $signals['risk'];
        $severityBase = (int) $signals['severity'];

        if (!$this->isConfigured()) {
            $result['error'] = 'api_key_not_configured';
            $result['decision'] = $this->decide($riskBase, $severityBase, $reviewThreshold, $actionThreshold, $signals, null);
            return $result;
        }

        try {
            $messages = $this->buildMessages($snapshot, $signals);
            $payload = $this->buildPayload($messages);

            $result['request'] = $payload;
            $raw = $this->send($payload);
            $result['response'] = $raw;

            $llm = $this->parseLlm($raw);
            $result['llm'] = $llm;

            $risk = max($riskBase, (float) ($llm['risk'] ?? 0.0));
            $severity = max($severityBase, (int) ($llm['severity'] ?? 0));

            $result['decision'] = $this->decide($risk, $severity, $reviewThreshold, $actionThreshold, $signals, $llm);
            return $result;
        } catch (\Exception $e) {
            $this->logger->warning('[AI Audit] LLM request failed, fallback to signals', [
                'error' => $e->getMessage(),
            ]);
            $result['error'] = $e->getMessage();
            $result['decision'] = $this->decide($riskBase, $severityBase, $reviewThreshold, $actionThreshold, $signals, null);
            return $result;
        }
    }

    private function decide(float $risk, int $severity, float $reviewThreshold, float $actionThreshold, array $signals, ?array $llm): array
    {
        $risk = $this->clamp01($risk);
        $severity = max(0, min(3, $severity));

        $actions = ['none'];
        $hasSuicideSignal = in_array('suicide_ideation', $signals['hits'] ?? [], true);

        if ($risk >= $reviewThreshold) {
            // For suicide signals, always alert regardless of threshold
            $actions = $hasSuicideSignal ? ['suicide_alert'] : ['review'];
        }
        if ($risk >= $actionThreshold) {
            $suggestedActions = $llm['actions'] ?? null;
            if (is_array($suggestedActions) && !empty($suggestedActions)) {
                $actions = $suggestedActions;
            } elseif ($hasSuicideSignal) {
                $actions = ['suicide_alert'];
            } else {
                $actions = ['hide'];
                if ($severity >= 3 || $risk >= min(0.95, $actionThreshold + 0.2)) {
                    $actions[] = 'suspend';
                }
            }
        }

        $conclusion = $this->buildConclusion($risk, $actions, $signals, $llm);

        return [
            'risk' => $risk,
            'severity' => $severity,
            'actions' => $actions,
            'conclusion' => $conclusion,
        ];
    }

    private function buildConclusion(float $risk, array $actions, array $signals, ?array $llm): string
    {
        if (is_array($llm) && isset($llm['conclusion']) && is_string($llm['conclusion']) && trim($llm['conclusion']) !== '') {
            return trim($llm['conclusion']);
        }

        $labels = [];
        foreach (($signals['hits'] ?? []) as $hit) {
            if (is_string($hit) && $hit !== '') {
                $labels[] = $hit;
            }
        }
        $labels = array_slice(array_values(array_unique($labels)), 0, 4);

        $riskText = number_format($risk * 100, 1) . '%';
        $actionText = implode(',', $actions);
        if ($labels === []) {
            return "风险 {$riskText}，动作 {$actionText}";
        }

        return "信号: " . implode(',', $labels) . "；风险 {$riskText}；动作 {$actionText}";
    }

    private function buildMessages(array $snapshot, array $signals): array
    {
        $systemPrompt = trim((string) $this->settings->get('zephyrisle.ai-audit.system_prompt', ''));
        if ($systemPrompt === '') {
            $systemPrompt = $this->dynamicSystemPrompt($snapshot);
        }

        $text = $this->buildUserText($snapshot, $signals);
        $hasImage = false;
        foreach (($snapshot['images'] ?? []) as $img) {
            if (is_array($img) && isset($img['data']) && is_string($img['data']) && $img['data'] !== '') {
                $hasImage = true;
                break;
            }
        }

        if (!$hasImage) {
            return [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $text],
            ];
        }

        $content = [['type' => 'text', 'text' => $text]];
        foreach (($snapshot['images'] ?? []) as $img) {
            if (!is_array($img) || !isset($img['data']) || !is_string($img['data']) || $img['data'] === '') {
                continue;
            }
            $content[] = ['type' => 'image_url', 'image_url' => ['url' => $img['data']]];
        }

        return [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $content],
        ];
    }

    private function buildUserText(array $snapshot, array $signals): string
    {
        $lines = [];
        $lines[] = 'input:';
        $lines[] = '- subject_type: ' . (string) ($snapshot['subject_type'] ?? 'unknown');
        $lines[] = '- subject_id: ' . (string) ($snapshot['subject_id'] ?? '');
        $lines[] = '- language_hint: zh';
        $lines[] = '';

        $content = $snapshot['content'] ?? [];
        if (is_array($content) && $content !== []) {
            $lines[] = 'content:';
            foreach ($content as $k => $v) {
                if (is_scalar($v) || $v === null) {
                    $lines[] = "- {$k}: " . (string) $v;
                } else {
                    $lines[] = "- {$k}: [non_scalar]";
                }
            }
            $lines[] = '';
        }

        // Include context if available
        $ctx = $snapshot['context'] ?? [];
        if (is_array($ctx) && $ctx !== [] && $this->contextEnabled()) {
            $lines[] = 'context:';
            foreach ($ctx as $k => $v) {
                if (is_scalar($v) || $v === null) {
                    $lines[] = "- {$k}: " . (string) $v;
                }
            }
            $lines[] = '';
        }

        $lines[] = 'signals:';
        $lines[] = '- risk: ' . number_format((float) ($signals['risk'] ?? 0.0), 4, '.', '');
        $lines[] = '- severity: ' . (string) ($signals['severity'] ?? 0);
        foreach (($signals['hits'] ?? []) as $hit) {
            if (is_string($hit) && $hit !== '') {
                $lines[] = "- hit: {$hit}";
            }
        }

        return implode("\n", $lines);
    }

    private function buildPayload(array $messages): array
    {
        $model = (string) $this->settings->get('zephyrisle.ai-audit.model', 'gpt-4o-mini');
        $temperature = max(0.0, min(2.0, (float) $this->settings->get('zephyrisle.ai-audit.temperature', 0.2)));
        $maxTokens = max(1, min(4096, (int) $this->settings->get('zephyrisle.ai-audit.max_tokens', 800)));

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $temperature,
            'max_tokens' => $maxTokens,
        ];

        // Use json_schema or json_object response format
        $useJsonSchema = (bool) $this->settings->get('zephyrisle.ai-audit.use_json_schema', true);
        if ($useJsonSchema) {
            $payload['response_format'] = [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'content_audit_result',
                    'strict' => true,
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'risk' => [
                                'type' => 'number',
                                'description' => 'Risk score from 0.0 to 1.0',
                            ],
                            'severity' => [
                                'type' => 'integer',
                                'description' => 'Severity level 0-3 (0=normal, 1=low, 2=medium, 3=high)',
                            ],
                            'conclusion' => [
                                'type' => 'string',
                                'description' => 'Brief conclusion in simplified Chinese, max 60 chars',
                            ],
                            'actions' => [
                                'type' => 'array',
                                'description' => 'Suggested moderation actions',
                                'items' => [
                                    'type' => 'string',
                                    'enum' => [
                                        'none',
                                        'review',
                                        'hide',
                                        'delete',
                                        'suspend',
                                        'rename',
                                        'delete_avatar',
                                        'reset_nickname',
                                        'reset_bio',
                                        'delete_cover',
                                        'flag',
                                        'suicide_alert',
                                    ],
                                ],
                            ],
                        ],
                        'required' => ['risk', 'severity', 'conclusion', 'actions'],
                        'additionalProperties' => false,
                    ],
                ],
            ];
        } else {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        return $payload;
    }

    private function send(array $payload): array
    {
        $apiKey = (string) $this->settings->get('zephyrisle.ai-audit.api_key', '');
        $base = rtrim((string) $this->settings->get('zephyrisle.ai-audit.api_endpoint', 'https://api.openai.com/v1'), '/');
        $timeout = (int) $this->settings->get('zephyrisle.ai-audit.timeout', 30);

        $client = new Client([
            'timeout' => $timeout,
            'connect_timeout' => min(10, $timeout),
        ]);

        $url = $base . '/chat/completions';

        try {
            $resp = $client->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $body = (string) $resp->getBody();
            $decoded = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                throw new \RuntimeException('invalid_json_response');
            }

            return $decoded;
        } catch (GuzzleException $e) {
            throw new \RuntimeException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    private function parseLlm(array $raw): array
    {
        $content = $raw['choices'][0]['message']['content'] ?? null;
        if (!is_string($content) || trim($content) === '') {
            throw new \RuntimeException('empty_llm_content');
        }

        $obj = $this->parseJsonObject($content);
        if (!is_array($obj)) {
            throw new \RuntimeException('llm_output_not_json');
        }

        $risk = $this->clamp01((float) ($obj['risk'] ?? 0.0));
        $severity = max(0, min(3, (int) ($obj['severity'] ?? 0)));
        $conclusion = is_string($obj['conclusion'] ?? null) ? trim((string) $obj['conclusion']) : '';

        $rawActions = $obj['actions'] ?? null;
        $actions = null;
        if (is_array($rawActions) && !empty($rawActions)) {
            $validActions = ['none', 'review', 'hide', 'delete', 'suspend', 'rename', 'delete_avatar', 'reset_nickname', 'reset_bio', 'delete_cover', 'flag', 'suicide_alert'];
            $actions = array_values(array_intersect($rawActions, $validActions));
        }

        return [
            'risk' => $risk,
            'severity' => $severity,
            'conclusion' => $conclusion,
            'actions' => $actions,
        ];
    }

    private function parseJsonObject(string $text): ?array
    {
        $trim = trim($text);
        $decoded = json_decode($trim, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start === false || $end === false || $end <= $start) {
            return null;
        }

        $candidate = substr($text, $start, $end - $start + 1);
        $decoded = json_decode($candidate, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        return null;
    }

    private function clamp01(float $x): float
    {
        if ($x < 0.0) return 0.0;
        if ($x > 1.0) return 1.0;
        return $x;
    }

    private function contextEnabled(): bool
    {
        return (bool) $this->settings->get('zephyrisle.ai-audit.enable_context', true);
    }

    /**
     * Generate a dynamic system prompt based on the subject type.
     */
    private function dynamicSystemPrompt(array $snapshot): string
    {
        $subjectType = $snapshot['subject_type'] ?? 'unknown';

        $basePrompt = <<<'PROMPT'
你是论坛内容审核助手。请根据输入内容判断是否存在违规风险，并给出风险值与严重程度。

可采取的审核动作：
- none: 无违规，内容正常
- review: 需要人工复核
- hide: 隐藏内容（设为未审核通过）
- delete: 删除消息（私信消息违规时删除该消息）
- suspend: 封禁用户账号
- rename: 重命名用户（严重违规时使用）
- delete_avatar: 删除用户头像
- reset_nickname: 重置用户昵称为空
- reset_bio: 重置用户签名为空
- delete_cover: 删除用户封面图
- flag: 标记内容供管理员审查
- suicide_alert: 检测到自杀/自残倾向，需立即通知管理员介入（不会处罚用户）

规则：
- 如果内容正常，只返回 ["none"]
- 如果轻微违规或有嫌疑，返回 ["review"]
- 如果明显违规，返回 ["hide"]
- 如果严重违规（暴力、色情、人肉搜索等），返回 ["hide", "suspend"]
- 用户资料（用户名、昵称、头像、签名）违规：返回对应删除/重置动作
- 始终基于风险值和严重程度做出合理判断

自杀/自残检测（重要）：
- 如果用户表达出自杀意图、自残倾向或严重的绝望情绪，返回 ["suicide_alert"]
- 注意区分玩笑话与真实求助，对有真实风险的应优先标记
- suicide_alert 不会隐藏或删除内容，仅通知管理员及时介入提供帮助
- 如果内容同时存在自杀倾向和违规行为，可以合并动作如 ["suicide_alert", "hide"]

输出要求：
1) 只输出一个 JSON 对象，不要输出其他文字。
2) 不要复述原文；使用概括性描述。
3) risk: 0.0-1.0；severity: 0-3；conclusion: 简体中文，60字以内。
4) actions: 数组，包含建议采取的动作。
PROMPT;

        // Specific guidance for each content type
        $typeGuidance = match ($subjectType) {
            'user_username' => "\n\n当前审核类型：用户用户名。注意用户名是否包含违规词汇、广告、联系方式等。",
            'user_avatar' => "\n\n当前审核类型：用户头像。注意头像是否包含违规图像（色情、暴力、政治敏感等）。",
            'user_nickname' => "\n\n当前审核类型：用户昵称。注意昵称是否包含违规词汇、广告、冒充他人等。",
            'user_bio' => "\n\n当前审核类型：用户签名档。注意签名是否包含广告、联系方式、违规内容等。",
            'user_cover' => "\n\n当前审核类型：用户封面图。注意封面是否包含违规图像。",
            'discussion_title' => "\n\n当前审核类型：主题标题。注意标题是否包含违规、恶意、广告内容。",
            'post_content' => "\n\n当前审核类型：帖子内容。注意内容是否包含违规信息、广告、人身攻击、色情等。",
            'post_image' => "\n\n当前审核类型：帖子图片。注意图片是否包含违规内容。",
            'upload_file' => "\n\n当前审核类型：上传文件。注意文件描述是否包含违规内容。",
            'dialog_message' => "\n\n当前审核类型：私信消息。注意消息是否包含违规信息、广告、骚扰、色情等。私信消息的违规处理建议：轻微违规用 review，明显违规用 delete 删除消息，严重违规用 delete+suspend 删除消息并封禁用户。",
            default => '',
        };

        return $basePrompt . $typeGuidance;
    }

    private function computeSignals(array $snapshot): array
    {
        $text = $this->flattenText($snapshot);
        $lower = mb_strtolower($text);

        $hits = [];
        $weights = [];

        $hit = function (string $id, float $w) use (&$hits, &$weights) {
            $hits[] = $id;
            $weights[] = max(0.0, min(1.0, $w));
        };

        if (preg_match('/\b1[3-9]\d{9}\b/u', $text)) $hit('phone_like', 0.26);
        if (preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $text)) $hit('email_like', 0.20);
        if (preg_match('/\b\d{17}[\dXx]\b/u', $text)) $hit('idcard_like', 0.35);
        if (preg_match('/(?:微信|vx|v信|qq|q群|群号|加群)/iu', $text)) $hit('contact_channel', 0.22);
        if (preg_match('/(?:https?:\/\/|www\.)/i', $text)) $hit('url_like', 0.14);
        if (preg_match('/(?:下注|博彩|赌场|外围|彩票)/iu', $text)) $hit('gambling', 0.30);
        if (preg_match('/(?:裸聊|约炮|成人视频|色情网|看片)/iu', $text)) $hit('sexual', 0.34);
        if (preg_match('/(?:杀了你|弄死你|我杀|炸死|爆炸)/iu', $text)) $hit('violence_threat', 0.40);

        // Suicide / self-harm detection
        if (preg_match('/(?:自杀|轻生|自残|割腕|上吊|跳楼|厌世|遗书|不想活了|活不下去|不想活啦|结束生命|告别这个世界|最后(?:的)?(?:时光|旅程|一程)|没有(?:活下去|生存)的(?:勇气|希望|意义)|一死了之|想不开想死|想结束自己的生命|对(?:生活|人生|世界)失去了(?:希望|信心))/iu', $text)) $hit('suicide_ideation', 0.32);
        if (preg_match('/\b(kill\s+myself|committed?\s+suicide|suicide|suicidal|self[\-\s]harm\b|end\s+my\s+life|want\s+to\s+die|better\s+off\s+dead|no\s+reason\s+to\s+live)\b/i', $text)) $hit('suicide_ideation', 0.32);

        $spam = $this->spamScore($lower);
        if ($spam > 0.0) $hit('spam_style', $spam);

        $risk = 0.0;
        foreach ($weights as $w) {
            $risk = 1 - (1 - $risk) * (1 - $w);
        }
        $risk = max(0.0, min(0.99, $risk));

        $severity = 0;
        if ($risk >= 0.85) $severity = 3;
        elseif ($risk >= 0.65) $severity = 2;
        elseif ($risk >= 0.45) $severity = 1;

        return [
            'risk' => $risk,
            'severity' => $severity,
            'hits' => array_values(array_unique($hits)),
        ];
    }

    private function flattenText(array $snapshot): string
    {
        $pieces = [];
        $content = $snapshot['content'] ?? null;
        if (is_array($content)) {
            foreach ($content as $v) {
                if (is_string($v) && $v !== '') $pieces[] = $v;
            }
        }
        $context = $snapshot['context'] ?? null;
        if (is_array($context)) {
            foreach ($context as $v) {
                if (is_string($v) && $v !== '') $pieces[] = $v;
            }
        }
        return implode("\n", $pieces);
    }

    private function spamScore(string $lower): float
    {
        $score = 0.0;
        $keywords = ['低价', '代购', '返利', '代理', '推广', '广告', '私聊', '联系', '加群', '出售', '收'];
        foreach ($keywords as $w) {
            if (str_contains($lower, $w)) $score += 0.05;
        }
        $urls = preg_match_all('/https?:\/\/[^\s]+/i', $lower) ?: 0;
        if ($urls >= 2) $score += min(0.25, 0.08 * ($urls - 1));
        if (preg_match('/(.)\1{6,}/u', $lower)) $score += 0.15;
        return max(0.0, min(0.35, $score));
    }
}
