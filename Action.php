<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * AIPen Action - 处理 AI 请求
 *
 * @author 优优
 * @website https://blog.uuhb.cn
 */
class AIPen_Action extends Typecho_Widget implements Widget_Interface_Do
{
    private $db;
    private $options;
    private $plugin;

    public function __construct($request, $response, $params = NULL)
    {
        parent::__construct($request, $response, $params);
        $this->db = Typecho_Db::get();
        $this->options = Typecho_Widget::widget('Widget_Options');
        $this->plugin = $this->options->plugin('AIPen');
        $this->user = Typecho_Widget::widget('Widget_User');
    }

    /**
     * 生成文章内容
     */
    public function generate()
    {
        // 设置响应头，禁用输出缓冲
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        if (function_exists('apache_setenv')) {
            apache_setenv('no-gzip', 1);
        }
        ini_set('output_buffering', 0);
        ini_set('zlib.output_compression', 0);
        ob_implicit_flush(1);

        // 验证用户权限 - 使用安全的方式检查
        if (!$this->user->hasLogin()) {
            $this->sendStreamChunk('error', '请先登录');
            return;
        }

        if (!$this->user->pass('administrator', true)) {
            $this->sendStreamChunk('error', '权限不足，需要管理员权限');
            return;
        }

        // 获取参数
        $prompt = $this->request->get('prompt');
        $articleType = $this->request->get('articleType', '技术博客');
        $style = $this->request->get('style', '正式');
        $audience = $this->request->get('audience', 'general');
        $length = $this->request->get('length', 'medium');
        $structures = $this->request->get('structures', '');

        if (empty($prompt)) {
            $this->sendStreamChunk('error', '请输入提示词');
            return;
        }

        // 构建完整的提示词
        $fullPrompt = self::buildPrompt($prompt, $articleType, $style, $audience, $length, $structures);

        // 调用 AI API（流式）
        $this->callAIAPI($fullPrompt);
    }

    /**
     * 构建完整提示词
     */
    private function buildPrompt($userPrompt, $articleType, $style, $audience, $length, $structures)
    {
        // 解析配置
        $articleTypes = self::parseStyles($this->plugin->articleTypes ?? '');
        $styles = self::parseStyles($this->plugin->styles ?? '');
        $structureOptions = self::parseStyles($this->plugin->structureOptions ?? '');
        $systemPrompt = $this->plugin->systemPrompt ?? '你是一位专业的博客文章写手，擅长创作高质量、有价值的内容。';

        // 获取文章类型描述
        $articleTypeDesc = isset($articleTypes[$articleType]) ? $articleTypes[$articleType] : '撰写高质量的文章';
        
        // 获取风格描述
        $styleDesc = isset($styles[$style]) ? $styles[$style] : '以正式、专业的语调撰写文章';

        // 目标受众描述
        $audienceDesc = '';
        switch ($audience) {
            case 'beginner':
                $audienceDesc = '目标读者是初学者，使用简单易懂的语言，多用比喻和实例解释复杂概念，避免过多专业术语';
                break;
            case 'professional':
                $audienceDesc = '目标读者是专业人士，可以使用专业术语，深入探讨技术细节，提供高级内容';
                break;
            default:
                $audienceDesc = '目标读者是一般读者，平衡专业性和可读性，适当解释专业术语';
        }

        // 文章长度描述
        $lengthDesc = '';
        switch ($length) {
            case 'short':
                $lengthDesc = '文章长度约500-800字，简洁精炼，重点突出';
                break;
            case 'medium':
                $lengthDesc = '文章长度约1000-1500字，内容充实，结构完整';
                break;
            case 'long':
                $lengthDesc = '文章长度约2000-3000字，详细深入，全面覆盖主题';
                break;
            case 'very_long':
                $lengthDesc = '文章长度3000字以上，深度剖析，包含丰富的细节和案例';
                break;
            default:
                $lengthDesc = '文章长度约1000-1500字';
        }

        // 内容结构要求
        $structureDesc = '';
        if (!empty($structures)) {
            $structureList = explode(',', $structures);
            $structureDesc = "\n内容结构要求：";
            foreach ($structureList as $struct) {
                $struct = trim($struct);
                if (isset($structureOptions[$struct])) {
                    $structureDesc .= "\n- " . $structureOptions[$struct];
                }
            }
        }

        // 构建完整的提示词
        $fullPrompt = <<<EOT
{$systemPrompt}

【文章类型】{$articleType}：{$articleTypeDesc}

【写作风格】{$styleDesc}

【目标受众】{$audienceDesc}

【文章长度】{$lengthDesc}
{$structureDesc}

【用户主题/大纲】
{$userPrompt}

请根据以上要求生成一篇完整的博客文章。文章应该结构清晰、内容丰富、语言流畅。如果是技术文章，请确保代码示例准确可运行。
EOT;

        return $fullPrompt;
    }

    /**
     * 调用 AI API - 流式输出
     */
    private function callAIAPI($prompt)
    {
        $apiUrl = $this->plugin->apiUrl;
        $apiKey = $this->plugin->apiKey;
        $model = $this->plugin->modelName;
        $maxTokens = intval($this->plugin->maxTokens);
        $temperature = floatval($this->plugin->temperature);
        $systemPrompt = $this->plugin->systemPrompt ?? '你是一位专业的博客文章写手。';

        $data = array(
            'model' => $model,
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => $systemPrompt
                ),
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
            'stream' => true  // 启用流式输出
        );

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ));
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, array($this, 'streamCallback'));

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->sendStreamChunk('error', 'cURL请求失败: ' . $error);
            return;
        }

        if ($httpCode !== 200) {
            $this->sendStreamChunk('error', 'HTTP错误: ' . $httpCode);
            return;
        }

        $this->sendStreamChunk('done', '');
    }

    /**
     * 流式回调函数
     */
    private function streamCallback($ch, $data)
    {
        $lines = explode("\n", $data);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            if (strpos($line, 'data: ') === 0) {
                $jsonData = substr($line, 6);

                if ($jsonData === '[DONE]') {
                    continue;
                }

                $parsed = json_decode($jsonData, true);
                if (isset($parsed['choices'][0]['delta']['content'])) {
                    $content = $parsed['choices'][0]['delta']['content'];
                    $this->sendStreamChunk('content', $content);
                }
            }
        }
        return strlen($data);
    }

    /**
     * 发送流式数据块
     */
    private function sendStreamChunk($type, $data)
    {
        $chunk = json_encode(array(
            'type' => $type,
            'data' => $data
        ));

        // 确保每行后都有换行符
        echo $chunk . "\n";

        // 强制刷新输出缓冲区
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }

    /**
     * 解析风格配置
     */
    private function parseStyles($stylesText)
    {
        $styles = array();
        $lines = explode("\n", $stylesText);
        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                list($name, $prompt) = explode(':', $line, 2);
                $styles[trim($name)] = trim($prompt);
            }
        }
        return $styles;
    }

    /**
     * Action 入口方法
     */
    public function action()
    {
        $this->generate();
    }
}
