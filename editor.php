<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * AIPen 前端界面
 *
 * @author 优优
 * @website https://blog.uuhb.cn
 */
?>
<div id="ai-writer-panel" style="margin-top: 20px; border: 1px solid #ddd; padding: 15px; border-radius: 5px; background: #f9f9f9;">
    <h3 style="margin-top: 0; color: #467B96;">✨ AIPen - AI 智能写作助手</h3>

    <div style="margin-bottom: 15px;">
        <label style="display: block; font-weight: bold; margin-bottom: 5px;">选择写作风格：</label>
        <select id="ai-style-select" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px;">
            <?php foreach ($styles as $name => $prompt): ?>
            <option value="<?php echo htmlspecialchars($name); ?>"><?php echo htmlspecialchars($name); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div style="margin-bottom: 15px;">
        <label style="display: block; font-weight: bold; margin-bottom: 5px;">输入文章主题或大纲：</label>
        <textarea id="ai-prompt-input" rows="4" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px;" placeholder="例如：写一篇关于人工智能发展历史的文章，包括起源、重要里程碑和未来展望..."></textarea>
    </div>

    <div style="display: flex; gap: 10px;">
        <button id="ai-generate-btn" type="button" style="padding: 10px 20px; background: #467B96; color: white; border: none; border-radius: 3px; cursor: pointer;">
            🚀 生成文章
        </button>
        <button id="ai-insert-btn" type="button" style="padding: 10px 20px; background: #5CB85C; color: white; border: none; border-radius: 3px; cursor: pointer; display: none;">
            📥 插入编辑器
        </button>
        <button id="ai-clear-btn" type="button" style="padding: 10px 20px; background: #D9534F; color: white; border: none; border-radius: 3px; cursor: pointer; display: none;">
            🗑️ 清空
        </button>
    </div>

    <div id="ai-loading" style="display: none; margin-top: 15px; text-align: center; color: #467B96;">
        <span style="display: inline-block; animation: spin 1s linear infinite;">⏳</span>
        AI 正在生成中，请稍候...
    </div>

    <div id="ai-result-container" style="display: none; margin-top: 15px;">
        <label style="display: block; font-weight: bold; margin-bottom: 5px;">生成结果：</label>
        <div id="ai-result-content" style="width: 100%; min-height: 200px; padding: 10px; border: 1px solid #ddd; border-radius: 3px; background: white; max-height: 400px; overflow-y: auto; white-space: pre-wrap;"></div>
    </div>
</div>

<style>
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>

<script>
(function() {
    const generateBtn = document.getElementById('ai-generate-btn');
    const insertBtn = document.getElementById('ai-insert-btn');
    const clearBtn = document.getElementById('ai-clear-btn');
    const loadingEl = document.getElementById('ai-loading');
    const resultContainer = document.getElementById('ai-result-container');
    const resultContent = document.getElementById('ai-result-content');
    const promptInput = document.getElementById('ai-prompt-input');
    const styleSelect = document.getElementById('ai-style-select');

    let generatedContent = '';

    // 生成按钮点击事件
    generateBtn.addEventListener('click', async function() {
        const prompt = promptInput.value.trim();
        if (!prompt) {
            alert('请输入文章主题或大纲');
            return;
        }

        const style = styleSelect.value;

        // 显示加载状态
        generateBtn.disabled = true;
        loadingEl.style.display = 'block';
        resultContainer.style.display = 'none';

        try {
            const response = await fetch('<?php echo $options->index; ?>/AIPen/Action', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'prompt=' + encodeURIComponent(prompt) + '&style=' + encodeURIComponent(style)
            });

            const data = await response.json();

            if (data.success) {
                generatedContent = data.content;
                resultContent.textContent = data.content;
                resultContainer.style.display = 'block';
                insertBtn.style.display = 'inline-block';
                clearBtn.style.display = 'inline-block';
            } else {
                alert('生成失败：' + (data.error || '未知错误'));
            }
        } catch (error) {
            alert('请求失败：' + error.message);
        } finally {
            generateBtn.disabled = false;
            loadingEl.style.display = 'none';
        }
    });

    // 插入编辑器按钮点击事件
    insertBtn.addEventListener('click', function() {
        const editor = document.getElementById('text') || document.querySelector('textarea[name="text"]');
        if (editor && generatedContent) {
            editor.value = generatedContent;
            alert('内容已插入编辑器！');
        } else {
            alert('未找到编辑器');
        }
    });

    // 清空按钮点击事件
    clearBtn.addEventListener('click', function() {
        resultContent.textContent = '';
        resultContainer.style.display = 'none';
        insertBtn.style.display = 'none';
        clearBtn.style.display = 'none';
        generatedContent = '';
    });
})();
</script>
