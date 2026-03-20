# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

AIPen is a Typecho CMS plugin that provides AI-powered writing assistance. It integrates OpenAI-compatible APIs to generate article content directly within the Typecho editor interface.

**Author:** 优优 (YouYou) | **Website:** https://blog.uuhb.cn

## Architecture

The plugin follows a simple three-file architecture:

- **Plugin.php** - Main plugin class implementing `Typecho_Plugin_Interface`
  - Handles plugin lifecycle (activate/deactivate via `Helper::addRoute`/`removeRoute`)
  - Registers admin panel hooks: `admin/write-post.php` and `admin/write-page.php`
  - Renders the AI assistant panel inline via the `render()` method
  - Contains all frontend CSS/JavaScript embedded directly (no external assets)

- **Action.php** - Backend API handler extending `Typecho_Widget` and implementing `Widget_Interface_Do`
  - Handles the `/AIPen/Action` route registered in Plugin.php
  - Validates administrator permissions via `$this->user->pass('administrator')`
  - Makes cURL requests to external AI APIs
  - Returns JSON responses to frontend

- **editor.php** - (Currently unused) The UI is embedded directly in Plugin.php's `render()` method to avoid file path issues

## Key Integration Points

### Typecho Plugin System
- Plugins implement `Typecho_Plugin_Interface` with `activate()`, `deactivate()`, `config()`, and `personalConfig()` methods
- Admin panels hook into specific pages via `Typecho_Plugin::factory('page-path')->event_location`
- Routes are registered with `Helper::addRoute($name, $url, $widgetClass, $actionMethod)`
- Configuration is accessed via `Typecho_Widget::widget('Widget_Options')->plugin('PluginName')`

### Security Patterns
- Always use `defined('__TYPECHO_ROOT_DIR__') or exit` at the top of PHP files
- Administrator-only actions must verify with `$this->user->pass('administrator')`
- Sanitize output with `htmlspecialchars()` for HTML attributes
- Use cURL with timeouts (60s) for external API calls

### API Integration
The plugin uses OpenAI-compatible chat completion format:
```php
$data = array(
    'model' => $model,
    'messages' => array(array('role' => 'user', 'content' => $prompt)),
    'max_tokens' => $maxTokens,
    'temperature' => $temperature
);
```

Response is parsed from `$result['choices'][0]['message']['content']`.

## Configuration Format

**Writing styles** are stored as newline-separated `name:prompt` pairs:
```
正式:以正式、专业的语调撰写文章，适合商务场景
轻松:以轻松、幽默的语调撰写，适合博客分享
```

Parsed by splitting on `:` and `\n` - see `parseStyles()` method in both Plugin.php and Action.php.

## Development Notes

- No build process or package manager - pure PHP plugin
- Requires PHP with cURL extension
- Test by uploading to `/usr/plugins/AIPen/` and activating in Typecho admin
- The frontend JavaScript uses `fetch()` to POST to `/AIPen/Action` with form-encoded data
- Editor insertion targets `#text` or `textarea[name=text]`
