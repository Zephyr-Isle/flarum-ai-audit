<?php

namespace ZephyrIsle\AiAudit\Service;

use Flarum\Settings\SettingsRepositoryInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

class AuditClient
{
    private const FORMAT_VERSION = 'zia_audit_v1';

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
        $actionThreshold = $this->clamp01((float) $this->settings->get('zephyrisle.ai-audit.action_threshold', 0.75));

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
        if ($risk >= $reviewThreshold) {
            $actions = ['review'];
        }
        if ($risk >= $actionThreshold) {
            $actions = ['hide'];
            if ($severity >= 3 || $risk >= min(0.95, $actionThreshold + 0.2)) {
                $actions[] = 'suspend';
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
            $systemPrompt = $this->defaultSystemPrompt();
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

        $ctx = $snapshot['context'] ?? [];
        if (is_array($ctx) && $ctx !== []) {
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
        $temperature = (float) $this->settings->get('zephyrisle.ai-audit.temperature', 0.2);
        $maxTokens = (int) $this->settings->get('zephyrisle.ai-audit.max_tokens', 800);

        return [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $temperature,
            'max_tokens' => $maxTokens,
            'response_format' => ['type' => 'json_object'],
        ];
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

        return [
            'risk' => $risk,
            'severity' => $severity,
            'conclusion' => $conclusion,
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

    private function defaultSystemPrompt(): string
    {
        return <<<'PROMPT'
你是论坛内容审核助手。请根据输入内容判断是否存在违规风险，并给出风险值与严重程度。

输出要求：
1) 只输出一个 JSON 对象，不要输出其他文字。
2) 不要复述原文；使用概括性描述。
3) risk: 0.0-1.0；severity: 0-3；conclusion: 简体中文，60字以内。

输出示例：
{"risk":0.12,"severity":0,"conclusion":"正常讨论内容"}
PROMPT;
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

