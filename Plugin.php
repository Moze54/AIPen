<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * AIPen - AI 智能写作助手
 *
 * @author 优优
 * @website https://blog.uuhb.cn
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
            'https://open.bigmodel.cn/api/paas/v4/chat/completions',
            _t('API 请求地址'),
            _t('AI 服务的 API 地址，例如 OpenAI 兼容接口')
        );
        $form->addInput($apiUrl);

        // API Key
        $apiKey = new Typecho_Widget_Helper_Form_Element_Text(
            'apiKey',
            NULL,
            'fc52569fdc7144059b37368692c30e24.zSJGVFj3tLAkjyFw',
            _t('API 密钥'),
            _t('请输入你的 API Key')
        );
        $apiKey->input->setAttribute('style', 'width: 100%');
        $form->addInput($apiKey);

        // 模型名称
        $modelName = new Typecho_Widget_Helper_Form_Element_Text(
            'modelName',
            NULL,
            'glm-4',
            _t('模型名称'),
            _t('要使用的 AI 模型，如 gpt-3.5-turbo, gpt-4, glm-4 等')
        );
        $form->addInput($modelName);

        // 风格预设
        $styles = new Typecho_Widget_Helper_Form_Element_Textarea(
            'styles',
            NULL,
            "正式:以正式、专业的语调撰写文章，适合商务场景\n轻松:以轻松、幽默的语调撰写，适合博客分享\n学术:以学术、严谨的语调撰写，适合研究论文\n营销:以营销、推广的语调撰写，突出产品优势",
            _t('写作风格预设'),
            _t('每行一个风格，格式：风格名称:提示词。一行一个')
        );
        $form->addInput($styles);

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

        // 解析风格预设，使用默认值如果配置为空
        if ($plugin && !empty($plugin->styles)) {
            $stylesText = $plugin->styles;
            $styles = self::parseStyles($stylesText);
        } else {
            // 使用硬编码的默认值
            $styles = array(
                '正式' => '以正式、专业的语调撰写文章',
                '轻松' => '以轻松、幽默的语调撰写'
            );
        }

        // 直接输出 HTML，避免文件路径问题
        echo '<div id="ai-message-container" style="position: fixed; top: 20px; left: 50%; transform: translateX(-50%); z-index: 999999; pointer-events: none;"></div>';

        echo '<div id="ai-writer-toggle" style="position: fixed; right: 0; top: 50%; transform: translateY(-50%); background: linear-gradient(135deg, #0ea5e9 0%, #14b8a6 100%); color: white; padding: 18px 12px; border-radius: 14px 0 0 14px; cursor: pointer; z-index: 99999; box-shadow: -4px 2px 20px rgba(14, 165, 233, 0.4); writing-mode: vertical-rl; text-orientation: mixed; font-weight: 600; letter-spacing: 3px; font-size: 14px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);">';
        echo '  ✨ AI 助手';
        echo '</div>';

        echo '<div id="ai-writer-panel" style="position: fixed; right: -420px; top: 50%; transform: translateY(-50%); width: 420px; background: #fff; border-radius: 20px 0 0 20px; box-shadow: -10px 0 60px rgba(0, 0, 0, 0.15); z-index: 99998; transition: right 0.4s cubic-bezier(0.4, 0, 0.2, 1); max-height: 90vh; overflow: hidden;">';
        echo '<div style="padding: 28px; max-height: 90vh; overflow-y: auto; background: linear-gradient(180deg, #f0fdfa 0%, #ffffff 100%);">';

        echo '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; padding-bottom: 20px; border-bottom: 1px solid #ccfbf1;">';
        echo '<div style="display: flex; align-items: center; gap: 12px;">';
        echo '<div style="width: 42px; height: 42px; background: linear-gradient(135deg, #0ea5e9 0%, #14b8a6 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: bold; color: white; box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);">AI</div>';
        echo '<div>';
        echo '<h3 style="margin: 0; background: linear-gradient(135deg, #0ea5e9 0%, #14b8a6 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; font-size: 20px; font-weight: 700;">AIPen 智能写作</h3>';
        echo '<p style="margin: 2px 0 0 0; font-size: 12px; color: #94a3b8;">AI 驱动的内容创作助手</p>';
        echo '</div>';
        echo '</div>';
        echo '<button id="ai-close-btn" style="background: #f1f5f9; border: none; width: 36px; height: 36px; border-radius: 10px; cursor: pointer; color: #64748b; font-size: 20px; transition: all 0.2s; display: flex; align-items: center; justify-content: center;">×</button>';
        echo '</div>';

        echo '<div style="margin-bottom: 24px;">';
        echo '<label style="display: flex; align-items: center; gap: 8px; font-weight: 600; margin-bottom: 12px; color: #334155; font-size: 14px;">';
        echo '<span style="font-size: 16px;">🎨</span> 写作风格';
        echo '</label>';

        // 自定义下拉选择器
        echo '<div id="ai-style-select" style="position: relative; width: 100%;">';
        echo '  <div id="ai-style-selected" style="padding: 14px 48px 14px 18px; border: 1.5px solid #e2e8f0; border-radius: 12px; font-size: 14px; background: #fff; color: #1e293b; cursor: pointer; font-weight: 500; line-height: 22px; box-sizing: border-box; background-image: url(\'data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'16\' height=\'16\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%2394a3b8\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'><polyline points=\'6 9 12 15 18 9\'></polyline></svg>\'); background-repeat: no-repeat; background-position: right 16px center; background-size: 16px; transition: all 0.2s;">正式</div>';
        echo '  <div id="ai-style-dropdown" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1.5px solid #e2e8f0; border-radius: 12px; margin-top: 6px; z-index: 999999; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.12); max-height: 220px; overflow-y: auto;">';

        $first = true;
        foreach ($styles as $name => $prompt) {
            $bgStyle = $first ? 'background: #f1f5f9;' : '';
            echo '    <div class="ai-style-option" data-value="' . htmlspecialchars($name) . '" style="padding: 12px 18px; cursor: pointer; font-size: 14px; color: #334155; transition: all 0.15s;' . $bgStyle . '">' . htmlspecialchars($name) . '</div>';
            $first = false;
        }

        echo '  </div>';
        echo '</div>';
        echo '</div>';

        echo '<div style="margin-bottom: 24px;">';
        echo '<label style="display: flex; align-items: center; gap: 8px; font-weight: 600; margin-bottom: 12px; color: #334155; font-size: 14px;">';
        echo '<span style="font-size: 16px;">📝</span> 文章主题';
        echo '</label>';
        echo '<textarea id="ai-prompt-input" rows="5" style="width: 100%; padding: 16px; border: 1.5px solid #e2e8f0; border-radius: 14px; font-size: 14px; resize: vertical; min-height: 120px; font-family: inherit; transition: all 0.2s; line-height: 1.7; color: #1e293b; font-weight: 400; box-sizing: border-box;" placeholder="描述你想写的文章主题、大纲或要点..."></textarea>';
        echo '</div>';

        echo '<button id="ai-generate-btn" type="button" style="width: 100%; padding: 16px; background: linear-gradient(135deg, #0ea5e9 0%, #14b8a6 100%); color: white; border: none; border-radius: 14px; cursor: pointer; font-size: 15px; font-weight: 600; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 4px 20px rgba(14, 165, 233, 0.35); letter-spacing: 1px;">';
        echo '✨ 开始创作';
        echo '</button>';

        echo '<div id="ai-loading" style="display: none; margin-top: 20px; padding: 20px; background: linear-gradient(135deg, #f0fdfa 0%, #ecfeff 100%); border-radius: 14px; text-align: center; border: 1px solid #ccfbf1;">';
        echo '  <div style="display: flex; align-items: center; justify-content: center; gap: 12px;">';
        echo '    <div style="width: 24px; height: 24px; border: 3px solid #ccfbf1; border-top-color: #0ea5e9; border-radius: 50%; animation: spin 0.8s linear infinite;"></div>';
        echo '    <span style="color: #0ea5e9; font-size: 14px; font-weight: 500;">AI 正在创作中...</span>';
        echo '  </div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';

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
        echo '#ai-style-selected:hover { border-color: #0ea5e9 !important; box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1); }';
        echo '#ai-style-selected.open { border-color: #0ea5e9 !important; box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.15); }';
        echo '#ai-style-selected.open + #ai-style-dropdown { display: block; }';
        echo '.ai-style-option:hover { background: #f0fdfa !important; }';
        echo '.ai-style-option:first-child { border-radius: 10px 10px 0 0; }';
        echo '.ai-style-option:last-child { border-radius: 0 0 10px 10px; }';
        echo '#ai-prompt-input:hover { border-color: #0ea5e9 !important; }';
        echo '#ai-prompt-input:focus { border-color: #0ea5e9 !important; outline: none; box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.15); background: #fff; }';
        echo '#ai-prompt-input::placeholder { color: #94a3b8; }';
        echo '#ai-generate-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 25px rgba(14, 165, 233, 0.45); }';
        echo '#ai-generate-btn:active { transform: translateY(0); }';
        echo '#ai-generate-btn:disabled { background: linear-gradient(135deg, #cbd5e0 0%, #94a3b8 100%); cursor: not-allowed; transform: none; box-shadow: none; }';
        echo '#ai-writer-toggle:hover { background: linear-gradient(135deg, #14b8a6 0%, #0ea5e9 100%); transform: translateX(-6px); box-shadow: -6px 4px 25px rgba(20, 184, 166, 0.5); }';
        echo '#ai-writer-toggle.generating { background: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%); animation: pulse 1.5s ease-in-out infinite; cursor: default; }';
        echo '#ai-writer-toggle.generating:hover { transform: none; box-shadow: -4px 2px 20px rgba(245, 158, 11, 0.4); }';
        echo '@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.7; } }';
        echo '#ai-close-btn:hover { background: #e2e8f0; color: #475569; }';
        echo '@media (max-width: 768px) {';
        echo '  #ai-writer-toggle {';
        echo '    top: 50% !important;';
        echo '    transform: translateY(-50%) !important;';
        echo '    border-radius: 14px 0 0 14px !important;';
        echo '    writing-mode: vertical-rl !important;';
        echo '    text-orientation: mixed !important;';
        echo '    padding: 18px 12px !important;';
        echo '    left: auto !important;';
        echo '    right: 0 !important;';
        echo '    font-size: 14px !important;';
        echo '    letter-spacing: 3px !important;';
        echo '    z-index: 99999 !important;';
        echo '  }';
        echo '  #ai-writer-panel {';
        echo '    width: 100% !important;';
        echo '    max-width: 100% !important;';
        echo '    right: -100% !important;';
        echo '    top: 0 !important;';
        echo '    transform: none !important;';
        echo '    border-radius: 0 !important;';
        echo '    max-height: 100vh !important;';
        echo '    height: 100vh !important;';
        echo '    z-index: 999999 !important;';
        echo '  }';
        echo '  #ai-writer-toggle:hover {';
        echo '    transform: translateY(-50%) translateX(-6px) !important;';
        echo '  }';
        echo '  #ai-writer-toggle.generating:hover {';
        echo '    transform: translateY(-50%) !important;';
        echo '  }';
        echo '}';
        echo '@media (max-width: 480px) {';
        echo '  #ai-writer-panel > div {';
        echo '    padding: 16px !important;';
        echo '    max-height: 100vh !important;';
        echo '    height: 100% !important;';
        echo '    overflow-y: auto !important;';
        echo '  }';
        echo '  .ai-message {';
        echo '    min-width: auto !important;';
        echo '    width: 90vw !important;';
        echo '  }';
        echo '}';
        echo '</style>';

        echo '<script>';
        echo '(function() {';
        echo '  const toggleBtn = document.getElementById("ai-writer-toggle");';
        echo '  const panel = document.getElementById("ai-writer-panel");';
        echo '  const closeBtn = document.getElementById("ai-close-btn");';
        echo '  const generateBtn = document.getElementById("ai-generate-btn");';
        echo '  const loadingEl = document.getElementById("ai-loading");';
        echo '  const promptInput = document.getElementById("ai-prompt-input");';
        echo '  const styleSelected = document.getElementById("ai-style-selected");';
        echo '  const styleDropdown = document.getElementById("ai-style-dropdown");';
        echo '  let isPanelOpen = false;';
        echo '  let isGenerating = false;';
        echo '  let selectedStyle = "正式";';
        echo '  let originalBtnText = toggleBtn.innerHTML;';
        echo '  const actionUrl = window.location.pathname.replace(/\/admin\/.*$/, "") + "/index.php/AIPen/Action";';

        echo '  function showMessage(message, type) {';
        echo '    type = type || "info";';
        echo '    const container = document.getElementById("ai-message-container");';
        echo '    const msgEl = document.createElement("div");';
        echo '    msgEl.className = "ai-message " + type;';
        echo '    const icons = { success: "✓", error: "✕", warning: "⚠", info: "ℹ" };';
        echo '    msgEl.innerHTML = "<span style=\'font-size: 16px; margin-right: 4px;\'>" + icons[type] + "</span><span>" + message + "</span>";';
        echo '    container.appendChild(msgEl);';
        echo '    setTimeout(function() { msgEl.classList.add(\'fadeOut\'); }, 2700);';
        echo '    setTimeout(function() { if (msgEl.parentNode) msgEl.parentNode.removeChild(msgEl); }, 3000);';
        echo '  }';

        echo '  styleSelected.addEventListener("click", function(e) {';
        echo '    e.stopPropagation();';
        echo '    const isOpen = styleDropdown.style.display === "block";';
        echo '    styleDropdown.style.display = isOpen ? "none" : "block";';
        echo '    styleSelected.classList.toggle("open", !isOpen);';
        echo '  });';

        echo '  document.querySelectorAll(".ai-style-option").forEach(function(option) {';
        echo '    option.addEventListener("click", function() {';
        echo '      selectedStyle = this.getAttribute("data-value");';
        echo '      styleSelected.textContent = selectedStyle;';
        echo '      styleDropdown.style.display = "none";';
        echo '      styleSelected.classList.remove("open");';
        echo '      document.querySelectorAll(".ai-style-option").forEach(function(opt) { opt.style.background = ""; });';
        echo '      this.style.background = "#e8f4fd";';
        echo '    });';
        echo '  });';

        echo '  document.addEventListener("click", function() {';
        echo '    styleDropdown.style.display = "none";';
        echo '    styleSelected.classList.remove("open");';
        echo '  });';

        echo '  function togglePanel() {';
        echo '    isPanelOpen = !isPanelOpen;';
        echo '    if (isPanelOpen) {';
        echo '      panel.style.setProperty("right", "0", "important");';
        echo '      toggleBtn.style.opacity = "0";';
        echo '      toggleBtn.style.pointerEvents = "none";';
        echo '      if (window.innerWidth <= 768) {';
        echo '        document.body.style.overflow = "hidden";';
        echo '      }';
        echo '    } else {';
        echo '      panel.style.setProperty("right", window.innerWidth <= 768 ? "-100%" : "-420px", "important");';
        echo '      toggleBtn.style.opacity = "1";';
        echo '      toggleBtn.style.pointerEvents = "auto";';
        echo '      document.body.style.overflow = "";';
        echo '    }';
        echo '  }';

        echo '  toggleBtn.addEventListener("click", togglePanel);';
        echo '  closeBtn.addEventListener("click", togglePanel);';

        echo '  function appendToEditor(content) {';
        echo '    let editor = document.getElementById("text") || document.querySelector("textarea[name=text]") || document.querySelector("#editor");';
        echo '    if (editor && editor.tagName === "TEXTAREA") {';
        echo '      editor.value += content;';
        echo '      const event = new Event("input", { bubbles: true });';
        echo '      editor.dispatchEvent(event);';
        echo '      editor.scrollTop = editor.scrollHeight;';
        echo '      return true;';
        echo '    }';
        echo '    return false;';
        echo '  }';

        echo '  generateBtn.addEventListener("click", async function() {';
        echo '    const prompt = promptInput.value.trim();';
        echo '    if (!prompt) { showMessage("请输入文章主题或大纲", "warning"); return; }';
        echo '    const style = selectedStyle;';
        echo '    generateBtn.disabled = true;';
        echo '    generateBtn.textContent = "生成中...";';
        echo '    loadingEl.style.display = "block";';
        echo '    isGenerating = true;';

        echo '    if (isPanelOpen) {';
        echo '      togglePanel();';
        echo '    }';
        echo '    toggleBtn.innerHTML = "⏳ 生成中";';
        echo '    toggleBtn.classList.add("generating");';
        echo '    toggleBtn.style.opacity = "1";';
        echo '    toggleBtn.style.pointerEvents = "none";';

        echo '    try {';
        echo '      const response = await fetch(actionUrl, {';
        echo '        method: "POST",';
        echo '        headers: { "Content-Type": "application/x-www-form-urlencoded" },';
        echo '        body: "prompt=" + encodeURIComponent(prompt) + "&style=" + encodeURIComponent(style)';
        echo '      });';

        echo '      const reader = response.body.getReader();';
        echo '      const decoder = new TextDecoder();';
        echo '      let buffer = "";';
        echo '      let firstChunk = true;';

        echo '      while (true) {';
        echo '        const { done, value } = await reader.read();';
        echo '        if (done) break;';

        echo '        buffer += decoder.decode(value, { stream: true });';
        echo '        const lines = buffer.split("\\n");';
        echo '        buffer = lines.pop() || "";';

        echo '        for (const line of lines) {';
        echo '          if (!line.trim()) continue;';

        echo '          try {';
        echo '            const chunk = JSON.parse(line);';

        echo '            if (chunk.type === "content") {';
        echo '              if (firstChunk) {';
        echo '                let editor = document.getElementById("text") || document.querySelector("textarea[name=text]");';
        echo '                if (editor && editor.tagName === "TEXTAREA") {';
        echo '                  editor.value = "";';
        echo '                }';
        echo '                firstChunk = false;';
        echo '              }';
        echo '              appendToEditor(chunk.data);';
        echo '            } else if (chunk.type === "error") {';
        echo '              showMessage("生成失败：" + chunk.data, "error");';
        echo '              isGenerating = false;';
        echo '              break;';
        echo '            } else if (chunk.type === "done") {';
        echo '              showMessage("内容生成完成！", "success");';
        echo '              isGenerating = false;';
        echo '            }';
        echo '          } catch (e) {';
        echo '            console.error("解析错误:", e, line);';
        echo '          }';
        echo '        }';
        echo '      }';
        echo '    } catch (error) {';
        echo '      showMessage("请求失败：" + error.message, "error");';
        echo '      isGenerating = false;';
        echo '    } finally {';
        echo '      generateBtn.disabled = false;';
        echo '      generateBtn.textContent = "开始创作";';
        echo '      loadingEl.style.display = "none";';
        echo '      toggleBtn.innerHTML = originalBtnText;';
        echo '      toggleBtn.classList.remove("generating");';
        echo '      toggleBtn.style.pointerEvents = "auto";';
        echo '    }';
        echo '  });';
        echo '})();';
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
