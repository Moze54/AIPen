<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * AIPen - AI 智能写作助手
 *
 * @package AIPen
 * @author 优优
 * @version 1.0.0
 * @link https://blog.uuhb.cn
 */
class AIPen_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件
     */
    public static function activate()
    {
        // 注册后台面板扩展
        Typecho_Plugin::factory('admin/write-post.php')->bottom = array('AIPen_Plugin', 'render');
        Typecho_Plugin::factory('admin/write-page.php')->bottom = array('AIPen_Plugin', 'render');

        // 添加后台配置页面样式
        Typecho_Plugin::factory('admin/footer.php')->end = array('AIPen_Plugin', 'adminStyles');

        // 添加路由处理 AI 请求
        Helper::addRoute('AIPen_Action', '/AIPen/Action', 'AIPen_Action', 'generate');

        return _t('AIPen 插件已激活，请在配置中设置 AI API');
    }

    /**
     * 禁用插件
     */
    public static function deactivate()
    {
        Helper::removeRoute('AIPen_Action');
        return _t('AIPen 插件已禁用');
    }

    /**
     * 插件配置面板
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        // 在表单前添加插件介绍
        echo '<div style="margin-bottom: 20px; padding: 20px 24px; background: linear-gradient(135deg, #0ea5e9 0%, #14b8a6 100%); border-radius: 12px; box-shadow: 0 4px 12px rgba(14, 165, 233, 0.2);">
            <h2 style="margin: 0 0 8px 0; color: #fff; font-size: 20px; font-weight: 700;">🤖 AIPen - AI 智能写作助手</h2>
            <p style="margin: 0; color: rgba(255,255,255,0.9); font-size: 13px; line-height: 1.6;">集成 OpenAI 兼容接口，为 Typecho 编辑器提供 AI 内容生成功能。支持多种写作风格，让创作更高效。</p>
        </div>';

        // API 地址
        $apiUrl = new Typecho_Widget_Helper_Form_Element_Text(
            'apiUrl',
            NULL,
            '',
            _t('API 请求地址'),
            _t('AI 服务的 API 地址，例如：https://api.openai.com/v1/chat/completions 或 https://open.bigmodel.cn/api/paas/v4/chat/completions')
        );
        $form->addInput($apiUrl);

        // API Key
        $apiKey = new Typecho_Widget_Helper_Form_Element_Text(
            'apiKey',
            NULL,
            '',
            _t('API 密钥'),
            _t('请输入你的 API Key（必填）')
        );
        $apiKey->input->setAttribute('style', 'width: 100%');
        $form->addInput($apiKey);

        // 模型名称
        $modelName = new Typecho_Widget_Helper_Form_Element_Text(
            'modelName',
            NULL,
            '',
            _t('模型名称'),
            _t('要使用的 AI 模型，如 gpt-3.5-turbo, gpt-4, glm-4, deepseek-chat 等（必填）')
        );
        $form->addInput($modelName);

        // 文章类型预设
        $articleTypes = new Typecho_Widget_Helper_Form_Element_Textarea(
            'articleTypes',
            NULL,
            "技术博客:撰写技术博客文章，包含代码示例、技术原理分析、最佳实践，适合开发者阅读\n生活随笔:撰写生活感悟、个人经历分享，语言轻松自然，富有情感\n产品评测:撰写客观的产品评测文章，包含优缺点分析、使用体验、购买建议\n教程指南:撰写步骤清晰的教程，从入门到进阶，适合初学者跟随学习\n新闻资讯:撰写简洁的新闻资讯，客观报道事件，包含背景信息和影响分析\n观点评论:撰写有深度的观点评论，包含论点、论据和独特见解",
            _t('文章类型预设'),
            _t('每行一个类型，格式：类型名称:类型描述。用于指导AI生成特定风格的内容')
        );
        $form->addInput($articleTypes);

        // 文章用途预设
        $articleUsage = new Typecho_Widget_Helper_Form_Element_Textarea(
            'articleUsage',
            NULL,
            "个人博客:轻松个人化，有观点和情感，适合个人表达\n公司官网:专业正式，体现品牌形象，避免过于口语化\n知识分享:详细易懂，注重实用性，让读者学到知识\n技术文档:准确规范，逻辑清晰，便于查阅和操作\n社交媒体:简短精炼，开头吸引眼球，便于传播",
            _t('文章用途预设'),
            _t('每行一个用途，格式：用途名称:用途描述。用于指导AI适应不同发布平台')
        );
        $form->addInput($articleUsage);

        // 文章长度
        $articleLength = new Typecho_Widget_Helper_Form_Element_Select(
            'articleLength',
            array(
                'short' => '短文 (500-800字)',
                'medium' => '中等 (1000-1500字)',
                'long' => '长文 (2000-3000字)',
                'very_long' => '深度长文 (3000字以上)'
            ),
            'medium',
            _t('文章长度'),
            _t('选择默认的文章生成长度')
        );
        $form->addInput($articleLength);

        // 风格预设
        $styles = new Typecho_Widget_Helper_Form_Element_Textarea(
            'styles',
            NULL,
            "正式:以正式、专业的语调撰写文章，适合商务场景\n轻松:以轻松、幽默的语调撰写，适合博客分享\n学术:以学术、严谨的语调撰写，适合研究论文\n营销:以营销、推广的语调撰写，突出产品优势",
            _t('写作风格预设'),
            _t('每行一个风格，格式：风格名称:提示词。一行一个')
        );
        $form->addInput($styles);

        // 内容结构要求
        $structureOptions = new Typecho_Widget_Helper_Form_Element_Textarea(
            'structureOptions',
            NULL,
            "包含代码示例:在技术内容中穿插实际可运行的代码片段\n使用Markdown格式:使用标准Markdown语法，包括标题、列表、代码块等\n分段清晰:使用小标题将内容分成多个逻辑段落\n包含总结:在文章末尾添加总结或要点回顾\n添加引用:适当引用权威来源或相关资料\n图文并茂:描述配图建议或图表说明",
            _t('内容结构选项'),
            _t('每行一个选项，格式：选项名:选项描述。用户可从中选择')
        );
        $form->addInput($structureOptions);

        // 自定义系统提示词
        $systemPrompt = new Typecho_Widget_Helper_Form_Element_Textarea(
            'systemPrompt',
            NULL,
            "你是一位专业的博客文章写手，擅长创作高质量、有价值的内容。你的文章应该：\n1. 结构清晰，逻辑严谨\n2. 内容原创，观点独特\n3. 语言流畅，易于阅读\n4. 提供实际价值，解决读者问题\n\n请根据用户提供的主题和要求，生成一篇完整的博客文章。",
            _t('系统提示词'),
            _t('自定义AI的系统提示词，用于设定AI的角色和基本要求')
        );
        $form->addInput($systemPrompt);

        // 最大 Token 数
        $maxTokens = new Typecho_Widget_Helper_Form_Element_Text(
            'maxTokens',
            NULL,
            '2000',
            _t('最大 Token 数'),
            _t('AI 生成内容的最大长度')
        );
        $form->addInput($maxTokens);

        // 温度参数
        $temperature = new Typecho_Widget_Helper_Form_Element_Text(
            'temperature',
            NULL,
            '0.7',
            _t('温度参数'),
            _t('控制生成内容的随机性，0-2 之间，值越高越随机')
        );
        $form->addInput($temperature);
    }

    /**
     * 个人配置（可选）
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
        // 可以添加用户级别的配置
    }

    /**
     * 后台配置页面样式
     */
    public static function adminStyles()
    {
        // 只在插件配置页面加载样式
        $currentUrl = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($currentUrl, 'AIPen') === false) {
            return;
        }

        echo '<style>
        /* AIPen 插件配置页面美化样式 */

        /* 表单容器 - 紧凑简洁 */
        .typecho-option {
            background: #fff;
            border-radius: 8px;
            padding: 16px 18px;
            margin-bottom: 12px;
            border: 1px solid #e2e8f0;
            transition: all 0.2s ease;
        }
        .typecho-option:hover {
            border-color: #cbd5e1;
        }

        /* 标签 - 简洁 */
        .typecho-option label {
            font-weight: 600;
            color: #1e293b;
            font-size: 14px;
            margin-bottom: 8px;
            display: block;
        }

        /* 描述文字 - 简洁 */
        .typecho-option .description {
            margin-top: 6px;
            color: #64748b;
            font-size: 12px;
            line-height: 1.5;
        }

        /* 输入框和文本域 - 简洁 */
        .typecho-option input[type="text"],
        .typecho-option textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.2s ease;
            font-family: inherit;
            background: #fafbfc;
            color: #1e293b;
            box-sizing: border-box;
        }
        .typecho-option input[type="text"]:focus,
        .typecho-option textarea:focus {
            outline: none;
            border-color: #0ea5e9;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
        }
        .typecho-option textarea {
            resize: vertical;
            min-height: 80px;
            line-height: 1.6;
        }
        .typecho-option input[type="text"]:hover,
        .typecho-option textarea:hover {
            border-color: #cbd5e1;
        }

        /* 按钮美化 */
        .typecho-submit {
            background: linear-gradient(135deg, #0ea5e9 0%, #14b8a6 100%);
            border: none;
            padding: 10px 24px;
            border-radius: 6px;
            color: white;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(14, 165, 233, 0.25);
        }
        .typecho-submit:hover {
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.35);
            transform: translateY(-1px);
        }
        .typecho-submit:active {
            transform: translateY(0);
        }
        </style>';
    }

    /**
     * 在编辑页面渲染 AI 助手面板
     */
    public static function render()
    {
        $options = null;
        $plugin = null;

        try {
            $options = Typecho_Widget::widget('Widget_Options');
            $plugin = $options->plugin('AIPen');
        } catch (Exception $e) {
            // 忽略错误，使用默认值
        }

        // 解析配置，使用默认值如果配置为空
        $styles = array();
        $articleTypes = array();
        $structureOptions = array();
        
        if ($plugin) {
            // 解析风格预设
            if (!empty($plugin->styles)) {
                $styles = self::parseStyles($plugin->styles);
            }
            // 解析文章类型
            if (!empty($plugin->articleTypes)) {
                $articleTypes = self::parseStyles($plugin->articleTypes);
            }
            // 解析文章用途
            if (!empty($plugin->articleUsage)) {
                $articleUsageList = self::parseStyles($plugin->articleUsage);
            }
            // 解析内容结构选项
            if (!empty($plugin->structureOptions)) {
                $structureOptions = self::parseStyles($plugin->structureOptions);
            }
        }
        
        // 使用默认值
        if (empty($styles)) {
            $styles = array(
                '正式' => '以正式、专业的语调撰写文章',
                '轻松' => '以轻松、幽默的语调撰写'
            );
        }
        if (empty($articleTypes)) {
            $articleTypes = array(
                '技术博客' => '撰写技术博客文章，包含代码示例、技术原理分析',
                '生活随笔' => '撰写生活感悟、个人经历分享',
                '教程指南' => '撰写步骤清晰的教程，适合初学者'
            );
        }
        if (empty($articleUsageList)) {
            $articleUsageList = array(
                '个人博客' => '轻松个人化，有观点和情感',
                '公司官网' => '专业正式，体现品牌形象',
                '知识分享' => '详细易懂，注重实用性'
            );
        }
        if (empty($structureOptions)) {
            $structureOptions = array(
                '使用Markdown格式' => '使用标准Markdown语法',
                '分段清晰' => '使用小标题将内容分成多个逻辑段落',
                '包含总结' => '在文章末尾添加总结'
            );
        }
        
        // 获取文章用途和文章长度配置
        $articleUsage = $plugin ? ($plugin->articleUsage ?? 'blog') : 'blog';
        $articleLength = $plugin ? ($plugin->articleLength ?? 'medium') : 'medium';

        // 直接输出 HTML，避免文件路径问题
        echo '<div id="ai-message-container" style="position: fixed; top: 20px; left: 50%; transform: translateX(-50%); z-index: 999999; pointer-events: none;"></div>';

        echo '<div id="ai-writer-toggle" style="position: fixed; right: 0; top: 50%; transform: translateY(-50%); background: linear-gradient(135deg, #0ea5e9 0%, #14b8a6 100%); color: white; padding: 18px 12px; border-radius: 14px 0 0 14px; cursor: pointer; z-index: 99999; box-shadow: -4px 2px 20px rgba(14, 165, 233, 0.4); writing-mode: vertical-rl; text-orientation: mixed; font-weight: 600; letter-spacing: 3px; font-size: 14px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);">';
        echo '  ✨ AI 助手';
        echo '</div>';

        echo '<div id="ai-writer-panel" style="position: fixed; right: -420px; top: 50%; transform: translateY(-50%); width: 420px; background: #fff; border-radius: 20px 0 0 20px; box-shadow: -10px 0 60px rgba(0, 0, 0, 0.15); z-index: 99998; transition: right 0.4s cubic-bezier(0.4, 0, 0.2, 1); height: 90vh; max-height: 700px; display: flex; flex-direction: column; overflow: hidden;">';
        
        // 头部
        echo '<div style="padding: 20px 24px 16px; border-bottom: 1px solid #e2e8f0; flex-shrink: 0;">';
        echo '<div style="display: flex; justify-content: space-between; align-items: center;">';
        echo '<div style="display: flex; align-items: center; gap: 12px;">';
        echo '<div style="width: 38px; height: 38px; background: linear-gradient(135deg, #0ea5e9 0%, #14b8a6 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: bold; color: white; box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);">AI</div>';
        echo '<div>';
        echo '<h3 style="margin: 0; color: #0ea5e9; font-size: 18px; font-weight: 700;">AIPen 智能写作</h3>';
        echo '<p style="margin: 2px 0 0 0; font-size: 11px; color: #94a3b8;">AI 驱动的内容创作助手</p>';
        echo '</div>';
        echo '</div>';
        echo '<button id="ai-close-btn" style="background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 8px; cursor: pointer; color: #64748b; font-size: 18px; transition: all 0.2s; display: flex; align-items: center; justify-content: center;">×</button>';
        echo '</div>';
        echo '</div>';
        
        // 内容区域（可滚动）
        echo '<div style="flex: 1; overflow-y: auto; padding: 20px 24px; background: linear-gradient(180deg, #f0fdfa 0%, #ffffff 100%);">';

        // 文章类型选择
        echo '<div style="margin-bottom: 20px;">';
        echo '<label style="display: flex; align-items: center; gap: 8px; font-weight: 600; margin-bottom: 10px; color: #334155; font-size: 14px;">';
        echo '<span style="font-size: 16px;">📄</span> 文章类型';
        echo '</label>';
        echo '<div id="ai-article-type-select" style="position: relative; width: 100%;">';
        echo '  <div id="ai-article-type-selected" style="padding: 12px 48px 12px 16px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px; background: #fff; color: #1e293b; cursor: pointer; font-weight: 500; line-height: 22px; box-sizing: border-box; background-image: url(\'data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'16\' height=\'16\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%2394a3b8\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'><polyline points=\'6 9 12 15 18 9\'></polyline></svg>\'); background-repeat: no-repeat; background-position: right 16px center; background-size: 16px; transition: all 0.2s;">技术博客</div>';
        echo '  <div id="ai-article-type-dropdown" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1.5px solid #e2e8f0; border-radius: 10px; margin-top: 6px; z-index: 999999; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.12); max-height: 200px; overflow-y: auto;">';

        $first = true;
        foreach ($articleTypes as $name => $desc) {
            $bgStyle = $first ? 'background: #f1f5f9;' : '';
            echo '    <div class="ai-article-type-option" data-value="' . htmlspecialchars($name) . '" data-desc="' . htmlspecialchars($desc) . '" style="padding: 10px 16px; cursor: pointer; font-size: 14px; color: #334155; transition: all 0.15s;' . $bgStyle . '">' . htmlspecialchars($name) . '</div>';
            $first = false;
        }

        echo '  </div>';
        echo '</div>';
        echo '</div>';

        // 写作风格选择
        echo '<div style="margin-bottom: 20px;">';
        echo '<label style="display: flex; align-items: center; gap: 8px; font-weight: 600; margin-bottom: 10px; color: #334155; font-size: 14px;">';
        echo '<span style="font-size: 16px;">🎨</span> 写作风格';
        echo '</label>';
        echo '<div id="ai-style-select" style="position: relative; width: 100%;">';
        echo '  <div id="ai-style-selected" style="padding: 12px 48px 12px 16px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px; background: #fff; color: #1e293b; cursor: pointer; font-weight: 500; line-height: 22px; box-sizing: border-box; background-image: url(\'data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'16\' height=\'16\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%2394a3b8\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'><polyline points=\'6 9 12 15 18 9\'></polyline></svg>\'); background-repeat: no-repeat; background-position: right 16px center; background-size: 16px; transition: all 0.2s;">正式</div>';
        echo '  <div id="ai-style-dropdown" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1.5px solid #e2e8f0; border-radius: 10px; margin-top: 6px; z-index: 999999; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.12); max-height: 200px; overflow-y: auto;">';

        $first = true;
        foreach ($styles as $name => $prompt) {
            $bgStyle = $first ? 'background: #f1f5f9;' : '';
            echo '    <div class="ai-style-option" data-value="' . htmlspecialchars($name) . '" style="padding: 10px 16px; cursor: pointer; font-size: 14px; color: #334155; transition: all 0.15s;' . $bgStyle . '">' . htmlspecialchars($name) . '</div>';
            $first = false;
        }

        echo '  </div>';
        echo '</div>';
        echo '</div>';

        // 文章用途选择
        echo '<div style="margin-bottom: 20px;">';
        echo '<label style="display: flex; align-items: center; gap: 8px; font-weight: 600; margin-bottom: 10px; color: #334155; font-size: 14px;">';
        echo '<span style="font-size: 16px;">🎯</span> 文章用途';
        echo '</label>';
        echo '<div id="ai-usage-select" style="position: relative; width: 100%;">';
        
        $firstUsage = key($articleUsageList);
        echo '  <div id="ai-usage-selected" style="padding: 12px 48px 12px 16px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px; background: #fff; color: #1e293b; cursor: pointer; font-weight: 500; line-height: 22px; box-sizing: border-box; background-image: url(\'data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'16\' height=\'16\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%2394a3b8\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'><polyline points=\'6 9 12 15 18 9\'></polyline></svg>\'); background-repeat: no-repeat; background-position: right 16px center; background-size: 16px; transition: all 0.2s;">' . htmlspecialchars($firstUsage) . '</div>';
        echo '  <div id="ai-usage-dropdown" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1.5px solid #e2e8f0; border-radius: 10px; margin-top: 6px; z-index: 999999; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.12); max-height: 200px; overflow-y: auto;">';

        $first = true;
        foreach ($articleUsageList as $name => $desc) {
            $bgStyle = $first ? 'background: #f1f5f9;' : '';
            echo '    <div class="ai-usage-option" data-value="' . htmlspecialchars($name) . '" style="padding: 10px 16px; cursor: pointer; font-size: 14px; color: #334155; transition: all 0.15s;' . $bgStyle . '">' . htmlspecialchars($name) . '</div>';
            $first = false;
        }

        echo '  </div>';
        echo '</div>';
        echo '</div>';

        // 文章长度选择
        echo '<div style="margin-bottom: 20px;">';
        echo '<label style="display: flex; align-items: center; gap: 8px; font-weight: 600; margin-bottom: 10px; color: #334155; font-size: 14px;">';
        echo '<span style="font-size: 16px;">📏</span> 文章长度';
        echo '</label>';
        echo '<div style="display: flex; gap: 8px; flex-wrap: wrap;">';
        
        $lengthOptions = array(
            'short' => '短文',
            'medium' => '中等',
            'long' => '长文',
            'very_long' => '深度'
        );
        
        foreach ($lengthOptions as $value => $label) {
            $isActive = $articleLength === $value;
            $bgStyle = $isActive ? 'background: linear-gradient(135deg, #0ea5e9 0%, #14b8a6 100%); color: white;' : 'background: #f8fafc; color: #475569;';
            $borderStyle = $isActive ? 'border-color: transparent;' : 'border-color: #e2e8f0;';
            echo '<div class="ai-length-option" data-value="' . $value . '" style="padding: 8px 16px; border: 1.5px solid; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 500; transition: all 0.2s; ' . $bgStyle . $borderStyle . '">' . $label . '</div>';
        }
        
        echo '</div>';
        echo '</div>';

        // 内容结构选项（多选）
        echo '<div style="margin-bottom: 16px;">';
        echo '<label style="display: flex; align-items: center; gap: 8px; font-weight: 600; margin-bottom: 10px; color: #334155; font-size: 14px;">';
        echo '<span style="font-size: 16px;">🔧</span> 内容选项';
        echo '</label>';
        echo '<div style="display: flex; flex-wrap: wrap; gap: 8px;">';
        
        foreach ($structureOptions as $name => $desc) {
            echo '<div class="ai-structure-option" data-value="' . htmlspecialchars($name) . '" style="padding: 6px 12px; border: 1.5px solid #e2e8f0; border-radius: 6px; cursor: pointer; font-size: 12px; color: #475569; transition: all 0.2s; background: #fff;" title="' . htmlspecialchars($desc) . '">' . htmlspecialchars($name) . '</div>';
        }
        
        echo '</div>';
        echo '</div>';
        
        echo '</div>'; // 结束内容滚动区域
        
        // 底部固定按钮区域
        echo '<div style="padding: 16px 24px 20px; border-top: 1px solid #e2e8f0; flex-shrink: 0; background: #fff;">';
        
        // 文章主题输入
        echo '<div style="margin-bottom: 14px;">';
        echo '<label style="display: flex; align-items: center; gap: 8px; font-weight: 600; margin-bottom: 8px; color: #334155; font-size: 14px;">';
        echo '<span style="font-size: 16px;">📝</span> 文章主题';
        echo '</label>';
        echo '<textarea id="ai-prompt-input" rows="3" style="width: 100%; padding: 12px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px; resize: vertical; min-height: 80px; max-height: 120px; font-family: inherit; transition: all 0.2s; line-height: 1.6; color: #1e293b; font-weight: 400; box-sizing: border-box;" placeholder="描述你想写的文章主题..."></textarea>';
        echo '</div>';

        echo '<button id="ai-generate-btn" type="button" style="width: 100%; padding: 14px; background: linear-gradient(135deg, #0ea5e9 0%, #14b8a6 100%); color: white; border: none; border-radius: 10px; cursor: pointer; font-size: 15px; font-weight: 600; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 4px 20px rgba(14, 165, 233, 0.35); letter-spacing: 1px;">';
        echo '✨ 开始创作';
        echo '</button>';

        echo '<div id="ai-loading" style="display: none; margin-top: 12px; padding: 12px; background: linear-gradient(135deg, #f0fdfa 0%, #ecfeff 100%); border-radius: 10px; text-align: center; border: 1px solid #ccfbf1;">';
        echo '  <div style="display: flex; align-items: center; justify-content: center; gap: 10px;">';
        echo '    <div style="width: 18px; height: 18px; border: 3px solid #ccfbf1; border-top-color: #0ea5e9; border-radius: 50%; animation: spin 0.8s linear infinite;"></div>';
        echo '    <span style="color: #0ea5e9; font-size: 13px; font-weight: 500;">AI 正在创作中...</span>';
        echo '  </div>';
        echo '</div>';
        echo '</div>'; // 底部固定按钮区域结束
        echo '</div>'; // 面板结束

        echo '<style>';
        echo '@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }';
        echo '@keyframes slideIn { from { transform: translateX(-50%) translateY(-20px); opacity: 0; } to { transform: translateX(-50%) translateY(0); opacity: 1; } }';
        echo '@keyframes fadeOut { from { opacity: 1; } to { opacity: 0; } }';
        echo '.ai-message { padding: 12px 20px; border-radius: 8px; margin-bottom: 10px; display: flex; align-items: center; gap: 8px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08); animation: slideIn 0.3s ease-out; pointer-events: auto; min-width: 200px; max-width: 90vw; font-size: 14px; border: 1px solid rgba(0,0,0,0.08); }';
        echo '.ai-message.success { background: #d1fae5; color: #065f46; }';
        echo '.ai-message.error { background: #fee2e2; color: #991b1b; }';
        echo '.ai-message.warning { background: #fef3c7; color: #92400e; }';
        echo '.ai-message.info { background: #dbeafe; color: #1e40af; }';
        echo '.ai-message.fadeOut { animation: fadeOut 0.3s ease-out forwards; }';
        
        // 下拉选择器样式
        echo '#ai-article-type-selected:hover, #ai-style-selected:hover, #ai-audience-selected:hover { border-color: #0ea5e9 !important; box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1); }';
        echo '#ai-article-type-selected.open, #ai-style-selected.open, #ai-audience-selected.open { border-color: #0ea5e9 !important; box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.15); }';
        echo '#ai-article-type-selected.open + #ai-article-type-dropdown, #ai-style-selected.open + #ai-style-dropdown, #ai-audience-selected.open + #ai-audience-dropdown { display: block; }';
        
        // 选项悬停效果
        echo '.ai-article-type-option:hover, .ai-style-option:hover, .ai-audience-option:hover { background: #f0fdfa !important; }';
        echo '.ai-article-type-option:first-child, .ai-style-option:first-child, .ai-audience-option:first-child { border-radius: 8px 8px 0 0; }';
        echo '.ai-article-type-option:last-child, .ai-style-option:last-child, .ai-audience-option:last-child { border-radius: 0 0 8px 8px; }';
        
        // 内容选项悬停效果
        echo '.ai-structure-option:hover { background: #f0fdfa !important; border-color: #0ea5e9 !important; }';
        
        // 输入框样式
        echo '#ai-prompt-input:hover { border-color: #0ea5e9 !important; }';
        echo '#ai-prompt-input:focus { border-color: #0ea5e9 !important; outline: none; box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.15); background: #fff; }';
        echo '#ai-prompt-input::placeholder { color: #94a3b8; }';
        
        // 按钮样式
        echo '#ai-generate-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 25px rgba(14, 165, 233, 0.45); }';
        echo '#ai-generate-btn:active { transform: translateY(0); }';
        echo '#ai-generate-btn:disabled { background: linear-gradient(135deg, #cbd5e0 0%, #94a3b8 100%); cursor: not-allowed; transform: none; box-shadow: none; }';
        echo '#ai-writer-toggle:hover { background: linear-gradient(135deg, #14b8a6 0%, #0ea5e9 100%); transform: translateX(-6px); box-shadow: -6px 4px 25px rgba(20, 184, 166, 0.5); }';
        echo '#ai-writer-toggle.generating { background: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%); animation: pulse 1.5s ease-in-out infinite; cursor: default; }';
        echo '#ai-writer-toggle.generating:hover { transform: none; box-shadow: -4px 2px 20px rgba(245, 158, 11, 0.4); }';
        echo '@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.7; } }';
        echo '#ai-close-btn:hover { background: #e2e8f0; color: #475569; }';
        
        // 移动端响应式
        echo '@media (max-width: 768px) {';
        echo '  #ai-writer-toggle {';
        echo '    top: 50% !important;';
        echo '    transform: translateY(-50%) !important;';
        echo '    border-radius: 10px 0 0 10px !important;';
        echo '    writing-mode: vertical-rl !important;';
        echo '    text-orientation: mixed !important;';
        echo '    padding: 12px 8px !important;';
        echo '    left: auto !important;';
        echo '    right: 0 !important;';
        echo '    font-size: 12px !important;';
        echo '    letter-spacing: 2px !important;';
        echo '    z-index: 99999 !important;';
        echo '  }';
        echo '  #ai-writer-panel {';
        echo '    width: 100% !important;';
        echo '    max-width: 100% !important;';
        echo '    right: -100% !important;';
        echo '    top: 0 !important;';
        echo '    transform: none !important;';
        echo '    border-radius: 0 !important;';
        echo '    height: 100vh !important;';
        echo '    max-height: 100vh !important;';
        echo '    z-index: 999999 !important;';
        echo '  }';
        echo '  #ai-writer-toggle:hover {';
        echo '    transform: translateY(-50%) translateX(-6px) !important;';
        echo '  }';
        echo '  #ai-writer-toggle.generating:hover {';
        echo '    transform: translateY(-50%) !important;';
        echo '  }';
        echo '}';
        echo '</style>';

        echo '<script>';
        echo <<<'EOT'
(function() {
  var toggleBtn = document.getElementById("ai-writer-toggle");
  var panel = document.getElementById("ai-writer-panel");
  var closeBtn = document.getElementById("ai-close-btn");
  var generateBtn = document.getElementById("ai-generate-btn");
  var loadingEl = document.getElementById("ai-loading");
  var promptInput = document.getElementById("ai-prompt-input");
  var articleTypeSelected = document.getElementById("ai-article-type-selected");
  var articleTypeDropdown = document.getElementById("ai-article-type-dropdown");
  var styleSelected = document.getElementById("ai-style-selected");
  var styleDropdown = document.getElementById("ai-style-dropdown");
  var usageSelected = document.getElementById("ai-usage-selected");
  var usageDropdown = document.getElementById("ai-usage-dropdown");
  
  var isPanelOpen = false;
  var isGenerating = false;
  var selectedArticleType = "技术博客";
  var selectedStyle = "正式";
  var selectedUsage = usageSelected ? usageSelected.getAttribute("data-value") || "blog" : "blog";
  var selectedLength = "medium";
  var selectedStructures = [];
  var originalBtnText = toggleBtn ? toggleBtn.innerHTML : "";
  var actionUrl = window.location.pathname.replace(/\/admin\/.*$/, "") + "/index.php/AIPen/Action";

  function showMessage(message, type) {
    type = type || "info";
    var container = document.getElementById("ai-message-container");
    var msgEl = document.createElement("div");
    msgEl.className = "ai-message " + type;
    var icons = { success: "✓", error: "✕", warning: "⚠", info: "ℹ" };
    msgEl.innerHTML = "<span style='font-size:16px;margin-right:4px;'>" + icons[type] + "</span><span>" + message + "</span>";
    container.appendChild(msgEl);
    setTimeout(function() { msgEl.classList.add("fadeOut"); }, 2700);
    setTimeout(function() { if (msgEl.parentNode) msgEl.parentNode.removeChild(msgEl); }, 3000);
  }

  if (articleTypeSelected) {
    articleTypeSelected.addEventListener("click", function(e) {
      e.stopPropagation();
      var isOpen = articleTypeDropdown.style.display === "block";
      articleTypeDropdown.style.display = isOpen ? "none" : "block";
      articleTypeSelected.classList.toggle("open", !isOpen);
      styleDropdown.style.display = "none";
      styleSelected.classList.remove("open");
      usageDropdown.style.display = "none";
      usageSelected.classList.remove("open");
    });
  }

  var articleTypeOptions = document.querySelectorAll(".ai-article-type-option");
  for (var i = 0; i < articleTypeOptions.length; i++) {
    articleTypeOptions[i].addEventListener("click", function() {
      selectedArticleType = this.getAttribute("data-value");
      articleTypeSelected.textContent = selectedArticleType;
      articleTypeDropdown.style.display = "none";
      articleTypeSelected.classList.remove("open");
      var opts = document.querySelectorAll(".ai-article-type-option");
      for (var j = 0; j < opts.length; j++) opts[j].style.background = "";
      this.style.background = "#f1f5f9";
    });
  }

  if (styleSelected) {
    styleSelected.addEventListener("click", function(e) {
      e.stopPropagation();
      var isOpen = styleDropdown.style.display === "block";
      styleDropdown.style.display = isOpen ? "none" : "block";
      styleSelected.classList.toggle("open", !isOpen);
      articleTypeDropdown.style.display = "none";
      articleTypeSelected.classList.remove("open");
      usageDropdown.style.display = "none";
      usageSelected.classList.remove("open");
    });
  }

  var styleOptions = document.querySelectorAll(".ai-style-option");
  for (var i = 0; i < styleOptions.length; i++) {
    styleOptions[i].addEventListener("click", function() {
      selectedStyle = this.getAttribute("data-value");
      styleSelected.textContent = selectedStyle;
      styleDropdown.style.display = "none";
      styleSelected.classList.remove("open");
      var opts = document.querySelectorAll(".ai-style-option");
      for (var j = 0; j < opts.length; j++) opts[j].style.background = "";
      this.style.background = "#f1f5f9";
    });
  }

  if (usageSelected) {
    usageSelected.addEventListener("click", function(e) {
      e.stopPropagation();
      var isOpen = usageDropdown.style.display === "block";
      usageDropdown.style.display = isOpen ? "none" : "block";
      usageSelected.classList.toggle("open", !isOpen);
      articleTypeDropdown.style.display = "none";
      articleTypeSelected.classList.remove("open");
      styleDropdown.style.display = "none";
      styleSelected.classList.remove("open");
    });
  }

  var usageOptions = document.querySelectorAll(".ai-usage-option");
  for (var i = 0; i < usageOptions.length; i++) {
    usageOptions[i].addEventListener("click", function() {
      selectedUsage = this.getAttribute("data-value");
      usageSelected.textContent = this.textContent.split(" - ")[0];
      usageSelected.setAttribute("data-value", selectedUsage);
      usageDropdown.style.display = "none";
      usageSelected.classList.remove("open");
      var opts = document.querySelectorAll(".ai-usage-option");
      for (var j = 0; j < opts.length; j++) opts[j].style.background = "";
      this.style.background = "#f1f5f9";
    });
  }

  var lengthOptions = document.querySelectorAll(".ai-length-option");
  for (var i = 0; i < lengthOptions.length; i++) {
    lengthOptions[i].addEventListener("click", function() {
      selectedLength = this.getAttribute("data-value");
      var opts = document.querySelectorAll(".ai-length-option");
      for (var j = 0; j < opts.length; j++) {
        opts[j].style.background = "#f8fafc";
        opts[j].style.color = "#475569";
        opts[j].style.borderColor = "#e2e8f0";
      }
      this.style.background = "#0ea5e9";
      this.style.color = "white";
      this.style.borderColor = "#0ea5e9";
    });
  }

  var structureOptions = document.querySelectorAll(".ai-structure-option");
  for (var i = 0; i < structureOptions.length; i++) {
    structureOptions[i].addEventListener("click", function() {
      var value = this.getAttribute("data-value");
      var index = selectedStructures.indexOf(value);
      if (index > -1) {
        selectedStructures.splice(index, 1);
        this.style.background = "#fff";
        this.style.color = "#475569";
        this.style.borderColor = "#e2e8f0";
      } else {
        selectedStructures.push(value);
        this.style.background = "#e0f2fe";
        this.style.color = "#0369a1";
        this.style.borderColor = "#7dd3fc";
      }
    });
  }

  document.addEventListener("click", function() {
    if (articleTypeDropdown) articleTypeDropdown.style.display = "none";
    if (articleTypeSelected) articleTypeSelected.classList.remove("open");
    if (styleDropdown) styleDropdown.style.display = "none";
    if (styleSelected) styleSelected.classList.remove("open");
    if (usageDropdown) usageDropdown.style.display = "none";
    if (usageSelected) usageSelected.classList.remove("open");
  });

  function togglePanel() {
    isPanelOpen = !isPanelOpen;
    if (isPanelOpen) {
      panel.style.setProperty("right", "0", "important");
      toggleBtn.style.opacity = "0";
      toggleBtn.style.pointerEvents = "none";
      if (window.innerWidth <= 768) {
        document.body.style.overflow = "hidden";
      }
    } else {
      panel.style.setProperty("right", window.innerWidth <= 768 ? "-100%" : "-420px", "important");
      toggleBtn.style.opacity = "1";
      toggleBtn.style.pointerEvents = "auto";
      document.body.style.overflow = "";
    }
  }

  if (toggleBtn) toggleBtn.addEventListener("click", togglePanel);
  if (closeBtn) closeBtn.addEventListener("click", togglePanel);

  function appendToEditor(content) {
    var editor = document.getElementById("text") || document.querySelector("textarea[name=text]") || document.querySelector("#editor");
    if (editor && editor.tagName === "TEXTAREA") {
      editor.value += content;
      var event = new Event("input", { bubbles: true });
      editor.dispatchEvent(event);
      editor.scrollTop = editor.scrollHeight;
      return true;
    }
    return false;
  }

  if (generateBtn) {
    generateBtn.addEventListener("click", function() {
      var prompt = promptInput.value.trim();
      if (!prompt) { showMessage("请输入文章主题或大纲", "warning"); return; }
      
      generateBtn.disabled = true;
      generateBtn.textContent = "生成中...";
      loadingEl.style.display = "block";
      isGenerating = true;

      if (isPanelOpen) { togglePanel(); }
      toggleBtn.innerHTML = "生成中...";
      toggleBtn.classList.add("generating");
      toggleBtn.style.opacity = "1";
      toggleBtn.style.pointerEvents = "none";

      var formData = new URLSearchParams();
      formData.append("prompt", prompt);
      formData.append("articleType", selectedArticleType);
      formData.append("style", selectedStyle);
      formData.append("usage", selectedUsage);
      formData.append("length", selectedLength);
      formData.append("structures", selectedStructures.join(","));

      fetch(actionUrl, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: formData.toString()
      }).then(function(response) {
        var reader = response.body.getReader();
        var decoder = new TextDecoder();
        var buffer = "";
        var firstChunk = true;

        function readChunk() {
          reader.read().then(function(result) {
            if (result.done) {
              showMessage("内容生成完成！", "success");
              isGenerating = false;
              generateBtn.disabled = false;
              generateBtn.textContent = "开始创作";
              loadingEl.style.display = "none";
              toggleBtn.innerHTML = originalBtnText;
              toggleBtn.classList.remove("generating");
              toggleBtn.style.pointerEvents = "auto";
              return;
            }

            buffer += decoder.decode(result.value, { stream: true });
            var lines = buffer.split("\n");
            buffer = lines.pop() || "";

            for (var k = 0; k < lines.length; k++) {
              var line = lines[k];
              if (!line.trim()) continue;

              try {
                var chunk = JSON.parse(line);
                if (chunk.type === "content") {
                  if (firstChunk) {
                    var editor = document.getElementById("text") || document.querySelector("textarea[name=text]");
                    if (editor && editor.tagName === "TEXTAREA") {
                      editor.value = "";
                    }
                    firstChunk = false;
                  }
                  appendToEditor(chunk.data);
                } else if (chunk.type === "error") {
                  showMessage("生成失败：" + chunk.data, "error");
                  isGenerating = false;
                  generateBtn.disabled = false;
                  generateBtn.textContent = "开始创作";
                  loadingEl.style.display = "none";
                  toggleBtn.innerHTML = originalBtnText;
                  toggleBtn.classList.remove("generating");
                  toggleBtn.style.pointerEvents = "auto";
                  return;
                }
              } catch (e) {
                console.error("解析错误:", e, line);
              }
            }
            readChunk();
          });
        }
        readChunk();
      }).catch(function(error) {
        showMessage("请求失败：" + error.message, "error");
        isGenerating = false;
        generateBtn.disabled = false;
        generateBtn.textContent = "开始创作";
        loadingEl.style.display = "none";
        toggleBtn.innerHTML = originalBtnText;
        toggleBtn.classList.remove("generating");
        toggleBtn.style.pointerEvents = "auto";
      });
    });
  }
})();
EOT;
        echo '</script>';
    }

    /**
     * 解析风格配置
     */
    private static function parseStyles($stylesText)
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
}
