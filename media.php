<?php
if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * DreamInLake 音/视频短代码（借鉴 FriendCircle media.php，不依赖任何插件）
 *
 *   [Music title="歌名" author="歌手" url="音频地址" pic="封面" lrc="歌词"/]  → APlayer
 *   [Video url="视频地址" pic="封面"/] 或 [play]地址[/play]（多行多地址=分集） → ArtPlayer
 *   [bilibili bvid="BVxxxx"/] 或 [bilibili]BVxxxx[/bilibili]                    → B站播放（自备 Worker，主题设置里填；留空回退官方播放器）
 *
 * 前台渲染：post.php / page.php / index.php 输出正文前调用 DL_Media::parse()，
 * 另挂 contentEx/excerptEx 钩子兜底 feed 等路径。parse() 幂等。
 */
class DL_Media
{
    /** B站播放地址：优先主题设置里的自备 Worker 前缀，留空回退 B 站官方播放器 */
    private static function biliSrc($bvid)
    {
        $base = '';
        try {
            $options = Typecho_Widget::widget('Widget_Options');
            if (isset($options->biliWorker)) {
                $base = trim((string) $options->biliWorker);
            }
        } catch (Exception $e) {
        }
        if ('' !== $base) {
            return rtrim($base, '/') . '/' . $bvid;
        }
        return 'https://player.bilibili.com/player.html?bvid=' . $bvid . '&page=1&high_quality=1&danmaku=0';
    }

    private static function parseAtts($text)
    {
        $atts = array();
        preg_match_all(
            '/([\w-]+)\s*=\s*"([^"]*)"|([\w-]+)\s*=\s*\'([^\']*)\'|([\w-]+)\s*=\s*([^\s\'"]+)/',
            (string) $text,
            $matches,
            PREG_SET_ORDER
        );
        foreach ($matches as $m) {
            if ($m[1] !== '') {
                $atts[$m[1]] = $m[2];
            } elseif ($m[3] !== '') {
                $atts[$m[3]] = $m[4];
            } elseif ($m[5] !== '') {
                $atts[$m[5]] = $m[6];
            }
        }
        return $atts;
    }

    private static function cleanValue($value)
    {
        $value = str_replace('\\/', '/', (string) $value);
        return trim(html_entity_decode(strip_tags($value), ENT_QUOTES, 'UTF-8'));
    }

    private static function esc($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    /** 从任意文本中提取 BV 号 / av 号 */
    public static function extractBvid($text)
    {
        $text = (string) $text;
        if (preg_match('/BV[0-9A-Za-z]{10}/', $text, $m)) {
            return $m[0];
        }
        if (preg_match('/av(\d+)/i', $text, $m)) {
            return 'av' . $m[1];
        }
        return '';
    }

    public static function parse($html)
    {
        $html = (string) $html;
        if (false === strpos($html, '[')) {
            return $html;
        }
        /* 预处理：短代码若与文字同段（<p>文字<br>[shortcode]</p>），先拆段，避免 div 套 p 非法嵌套 */
        $html = preg_replace('/<br\s*\/?>\s*(\[(?:Music|Video|bilibili|play))/i', '</p>$1', $html);
        $html = preg_replace_callback('/(?:<p[^>]*>)?\[Music([^\]]*)\](?:<\/p>)?/i', array('DL_Media', 'renderMusic'), $html);
        $html = preg_replace_callback(
            '/(?:<p[^>]*>)?\[(?:play|video)\]([\s\S]*?)\[\/(?:play|video)\](?:<\/p>)?/i',
            array('DL_Media', 'renderPlay'),
            $html
        );
        $html = preg_replace_callback('/(?:<p[^>]*>)?\[Video([^\]]*)\](?:<\/p>)?/i', array('DL_Media', 'renderVideo'), $html);
        $html = preg_replace_callback('/(?:<p[^>]*>)?\[bilibili([^\]]*)\](?:<\/p>)?/i', array('DL_Media', 'renderBili'), $html);
        $html = preg_replace_callback(
            '/(?:<p[^>]*>)?\[bilibili\]([\s\S]*?)\[\/bilibili\](?:<\/p>)?/i',
            array('DL_Media', 'renderBiliBody'),
            $html
        );
        $html = preg_replace_callback(
            '/(?:<p[^>]*>)?\[gallery\]([\s\S]*?)\[\/gallery\](?:<\/p>)?/i',
            array('DL_Media', 'renderGallery'),
            $html
        );
        /* 后处理：删掉媒体块div后悬空的 </p>（递归正则处理嵌套 div） */
        $html = preg_replace(
            '/(<div class="dl-(?:bili|video-wrap|aplayer|play-multi)"[^>]*>(?:[^<]|<(?!\\/?div)|(?R))*<\/div>)\s*<\/p>/i',
            '$1',
            $html
        );
        return $html;
    }

    public static function render($text, $widget, $last = null)
    {
        $html = (null !== $last && '' !== $last) ? (string) $last : (string) $text;
        return self::parse($html);
    }

    private static function renderMusic($m)
    {
        $a = self::parseAtts($m[1]);
        $url = isset($a['url']) ? self::cleanValue($a['url']) : '';
        if ('' === $url) {
            return $m[0];
        }
        return '<div class="aplayer dl-aplayer"'
            . ' data-name="' . self::esc(isset($a['title']) ? self::cleanValue($a['title']) : '') . '"'
            . ' data-artist="' . self::esc(isset($a['author']) ? self::cleanValue($a['author']) : '') . '"'
            . ' data-url="' . self::esc($url) . '"'
            . ' data-cover="' . self::esc(isset($a['pic']) ? self::cleanValue($a['pic']) : '') . '"'
            . ' data-lrc="' . self::esc(isset($a['lrc']) ? self::cleanValue($a['lrc']) : '') . '"></div>';
    }

    private static function renderVideo($m)
    {
        $a = self::parseAtts($m[1]);
        $url = isset($a['url']) ? self::cleanValue($a['url']) : '';
        if ('' === $url) {
            return $m[0];
        }
        $pic = isset($a['pic']) ? self::cleanValue($a['pic']) : '';
        return self::videoHtml($url, $pic);
    }

    private static function renderPlay($m)
    {
        $inner = preg_replace('/<br\s*\/?>/i', "\n", $m[1]);
        $urls = array();
        foreach (preg_split('/\s*\n\s*/', trim($inner)) as $url) {
            $url = self::cleanValue($url);
            if ('' === $url) {
                continue;
            }
            /* B站链接在分集里同样支持，转点击播放 */
            $bvid = self::extractBvid($url);
            $urls[] = $bvid ? 'bilibili:' . $bvid : $url;
        }
        if (empty($urls)) {
            return $m[0];
        }
        if (1 === count($urls)) {
            return self::videoHtml($urls[0], '');
        }
        $out = '<div class="dl-play-multi"><div class="dl-video-wrap">'
            . self::videoHtml($urls[0], '') . '</div><div class="dl-video-tabs">';
        foreach ($urls as $i => $u) {
            $out .= '<button type="button" class="dl-video-tab' . (0 === $i ? ' active' : '')
                . '" data-url="' . self::esc($u) . '">第' . ($i + 1) . '集</button>';
        }
        return $out . '</div></div>';
    }

    /** 单个视频容器：bilibili:xxx 走 B站点击播放，其余走 ArtPlayer */
    private static function videoHtml($url, $pic)
    {
        if (0 === strpos($url, 'bilibili:')) {
            return self::biliHtml(substr($url, 9));
        }
        $native = '<video class="dl-video-native" controls preload="metadata" playsinline src="' . self::esc($url) . '"'
            . ('' === $pic ? '' : ' poster="' . self::esc($pic) . '"') . '></video>';
        return '<div class="dl-video-wrap"><div class="dl-video" data-url="' . self::esc($url) . '"'
            . ('' === $pic ? '' : ' data-pic="' . self::esc($pic) . '"') . '>' . $native . '</div></div>';
    }

    private static function renderBili($m)
    {
        $a = self::parseAtts($m[1]);
        $bvid = '';
        if (isset($a['bvid'])) {
            $bvid = self::extractBvid($a['bvid']);
        } elseif (isset($a['bv'])) {
            $bvid = self::extractBvid($a['bv']);
        } elseif (isset($a['url'])) {
            $bvid = self::extractBvid($a['url']);
        }
        if ('' === $bvid) {
            return $m[0];
        }
        return self::biliHtml($bvid);
    }

    private static function renderBiliBody($m)
    {
        $bvid = self::extractBvid(self::cleanValue($m[1]));
        if ('' === $bvid) {
            return $m[0];
        }
        return self::biliHtml($bvid);
    }

    /** 九宫格图库：[gallery]内每行一个图片地址（或 Markdown 图片语法） */
    private static function renderGallery($m)
    {
        $inner = preg_replace('/<br\s*\/?>/i', "\n", $m[1]);
        $imgs = array();
        foreach (preg_split('/\s*\n\s*/', trim($inner)) as $line) {
            $line = trim($line);
            if ('' === $line) continue;
            /* 支持 Markdown 图片语法 ![alt](url) */
            if (preg_match('/!\[[^\]]*\]\(([^)]+)\)/', $line, $mm)) {
                $line = trim($mm[1]);
            }
            /* 支持 <img src="..."> */
            elseif (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $line, $mm)) {
                $line = trim($mm[1]);
            }
            $line = self::cleanValue(strip_tags($line));
            if ('' === $line) continue;
            $imgs[] = $line;
        }
        if (empty($imgs)) return $m[0];
        $out = '<div class="dl-gallery">';
        foreach ($imgs as $src) {
            $out .= '<figure class="dl-gallery-item"><img src="' . self::esc($src) . '" alt="" /></figure>';
        }
        return $out . '</div>';
    }

    /** B站嵌入播放器（自备 Worker 代理页优先，微信/iOS 可播；留空为官方播放器） */
    private static function biliHtml($bvid)
    {
        $src = self::biliSrc($bvid);
        return '<div class="dl-bili"><iframe src="' . self::esc($src) . '"'
            . ' allowfullscreen="true" allow="autoplay; fullscreen; picture-in-picture"'
            . ' referrerpolicy="no-referrer-when-downgrade"></iframe></div>';
    }

    /** 后台编辑器按钮：插入音乐 / 插入视频 / 插入B站 */
    public static function editorButton($post = null)
    {
        echo <<<'EOT'
<script>
(function () {
    function init() {
    var row = document.getElementById('wmd-button-row');
    if (!row || row.dataset.dlMedia) return;
    row.dataset.dlMedia = '1';

    function insertText(text) {
        var field = document.getElementById('text');
        if (!field) return;
        var start = field.selectionStart || 0;
        var end = field.selectionEnd || 0;
        field.value = field.value.substring(0, start) + text + field.value.substring(end, field.value.length);
        field.selectionStart = field.selectionEnd = start + text.length;
        field.focus();
    }

    function panel(title, fields, onOk) {
        var overlay = document.createElement('div');
        overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:99999;display:flex;align-items:center;justify-content:center;';
        var box = document.createElement('div');
        box.style.cssText = 'background:#fff;border-radius:8px;box-shadow:0 8px 30px rgba(0,0,0,.25);padding:20px 24px;width:420px;max-width:92vw;font-size:14px;';
        var head = document.createElement('p');
        head.style.cssText = 'margin:0 0 14px;font-weight:600;';
        head.textContent = title;
        box.appendChild(head);
        var inputs = [];
        fields.forEach(function (f) {
            var label = document.createElement('label');
            label.style.cssText = 'display:block;margin:0 0 10px;font-size:12px;color:#666;';
            label.textContent = f.label + (f.required ? '（必填）' : '（选填）');
            var input = f.multiline ? document.createElement('textarea') : document.createElement('input');
            if (f.multiline) {
                input.rows = 5;
                input.style.cssText = 'width:100%;box-sizing:border-box;margin-top:4px;padding:6px 8px;border:1px solid #d9d9d9;border-radius:4px;font-size:13px;resize:vertical;';
            } else {
                input.type = 'text';
                input.style.cssText = 'width:100%;box-sizing:border-box;margin-top:4px;padding:6px 8px;border:1px solid #d9d9d9;border-radius:4px;font-size:13px;';
            }
            label.appendChild(input);
            box.appendChild(label);
            inputs.push(input);
        });
        var bar = document.createElement('div');
        bar.style.cssText = 'text-align:right;margin-top:14px;';
        var ok = document.createElement('button');
        ok.type = 'button'; ok.textContent = '确定';
        ok.style.cssText = 'margin-right:8px;padding:6px 18px;background:#467B96;color:#fff;border:0;border-radius:4px;cursor:pointer;';
        var cancel = document.createElement('button');
        cancel.type = 'button'; cancel.textContent = '取消';
        cancel.style.cssText = 'padding:6px 18px;background:#eee;border:0;border-radius:4px;cursor:pointer;';
        bar.appendChild(ok); bar.appendChild(cancel); box.appendChild(bar);
        overlay.appendChild(box);
        document.body.appendChild(overlay);
        inputs[0].focus();
        function close() { overlay.remove(); }
        cancel.onclick = close;
        overlay.addEventListener('click', function (e) { if (e.target === overlay) close(); });
        ok.onclick = function () {
            var values = inputs.map(function (i) { return i.value.trim(); });
            for (var i = 0; i < fields.length; i++) {
                if (fields[i].required && !values[i]) { inputs[i].focus(); return; }
            }
            var out = onOk(values);
            if (out) insertText(out);
            close();
        };
    }

    function makeButton(title, label, fields, onOk) {
        var li = document.createElement('li');
        li.className = 'wmd-button';
        li.title = title;
        li.innerHTML = '<span style="display:flex;align-items:center;justify-content:center;height:20px;font-size:14px;line-height:20px;">' + label + '</span>';
        li.onclick = function () { panel(title, fields, onOk); };
        row.appendChild(li);
    }

    makeButton('插入音乐（APlayer）', '♪', [
        { label: '歌曲名', required: false },
        { label: '歌手', required: false },
        { label: '音频文件地址', required: true },
        { label: '封面图地址', required: false }
    ], function (v) {
        if (!v[2]) return '';
        var tag = '[Music title="' + v[0] + '" author="' + v[1] + '" url="' + v[2] + '"';
        if (v[3]) tag += ' pic="' + v[3] + '"';
        return tag + '/]';
    });

    makeButton('插入视频（ArtPlayer，mp4）', '▶', [
        { label: '视频文件地址（每行一个，多个将生成分集）', required: true, multiline: true },
        { label: '封面图地址', required: false }
    ], function (v) {
        var lines = v[0].split(/\s*\n\s*/).filter(function (s) { return s; });
        if (!lines.length) return '';
        if (lines.length === 1) {
            return '[Video url="' + lines[0] + '"' + (v[1] ? ' pic="' + v[1] + '"' : '') + '/]';
        }
        return '[play]\n' + lines.join('\n') + '\n[/play]';
    });

    makeButton('插入B站视频', 'B', [
        { label: 'B站 BV 号或视频链接（如 BV1chKS6oEhu）', required: true }
    ], function (v) {
        var m = v[0].match(/BV[0-9A-Za-z]{10}/);
        if (!m) return '';
        return '[bilibili bvid="' + m[0] + '"/]';
    });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    if (!document.getElementById('wmd-button-row')) {
        new MutationObserver(function (m, obs) {
            if (document.getElementById('wmd-button-row')) {
                obs.disconnect();
                init();
            }
        }).observe(document.body, { childList: true, subtree: true });
    }
})();
</script>
EOT;
    }
}

Typecho_Plugin::factory('admin/write-post.php')->bottom = array('DL_Media', 'editorButton');
Typecho_Plugin::factory('admin/write-page.php')->bottom = array('DL_Media', 'editorButton');
Typecho_Plugin::factory('Widget\Base\Contents')->contentEx = array('DL_Media', 'render');
Typecho_Plugin::factory('Widget\Base\Contents')->excerptEx = array('DL_Media', 'render');
