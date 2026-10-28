<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function themeConfig($form) {
    $logoUrl = new Typecho_Widget_Helper_Form_Element_Text('logoUrl', NULL, NULL, _t('站点LOGO地址'), _t('在这里填入一个图片URL地址, 以在网站标题前加上一个LOGO'));
    $form->addInput($logoUrl);

    $biliWorker = new Typecho_Widget_Helper_Form_Element_Text('biliWorker', NULL, NULL, _t('B站播放 Worker 地址'), _t('可选。[bilibili] 短代码通过一个自备的 Cloudflare Worker 代理页播放（微信 / iOS 内可播）。填入你自己部署的 Worker 地址前缀，如 https://example.workers.dev/video/，主题会把 BV 号拼在其后。留空则回退为 B 站官方播放器直嵌（普通浏览器可用，微信 / iOS 内置浏览器可能受限）。Worker 的部署方法见主题 README 的「B站播放」一节。'));
    $form->addInput($biliWorker);

    $navPages = new Typecho_Widget_Helper_Form_Element_Checkbox('navPages',
    array('ShowArchive' => _t('显示归档页'),
    'ShowTags' => _t('显示标签页')),
    array('ShowArchive', 'ShowTags'), _t('菜单附加页'), _t('控制菜单栏是否显示归档页与标签页。关掉后页面本身仍可直接访问，只是不在菜单里出现。默认两者都显示。'));
    $form->addInput($navPages->multiMode());

    $autoTag = new Typecho_Widget_Helper_Form_Element_Radio('autoTag',
    array('1' => _t('开启'),
    '0' => _t('关闭')),
    '1', _t('自动生成标签'), _t('保存文章时，从标题和正文提取高频实词自动补为标签，与手动输入的标签合并（手动标签始终保留，可自行删改）。关闭后仅使用手动标签。'));
    $form->addInput($autoTag);

    $sidebarBlock = new Typecho_Widget_Helper_Form_Element_Checkbox('sidebarBlock',
    array('ShowRecentPosts' => _t('显示最新文章'),
    'ShowRecentComments' => _t('显示最近回复'),
    'ShowCategory' => _t('显示分类'),
    'ShowArchive' => _t('显示归档'),
    'ShowOther' => _t('显示其它杂项')),
    array(), _t('侧边栏显示'));

    $form->addInput($sidebarBlock->multiMode());
}

function theme_random_posts(){
$defaults = array(
'number' => 5, //输出文章条数
'before' => '<ul class="list">', // ul选择器,自行修改
'after' => '</ul>',
'xformat' => '<li><a href="{permalink}">{title}</a></li>'
);
$db = Typecho_Db::get();
$sql = $db->select()->from('table.contents')
->where('status = ?','publish')
->where('type = ?', 'post')
->limit($defaults['number'])
->order('RAND()');
$result = $db->fetchAll($sql);
echo $defaults['before'];
foreach($result as $val){
$val = Typecho_Widget::widget('Widget_Abstract_Contents')->filter($val);
echo str_replace(array('{permalink}', '{title}'),array($val['permalink'], $val['title']), $defaults['xformat']);
}
echo $defaults['after'];
}

/**
 * 纯文本摘要：取核心的纯文本摘要，再清掉短代码残留（多集播放器在摘要里留下的
 * 连续“第N集”按钮文字、未解析的短代码标记）和多余空白。用于首页摘要与 SEO 描述。
 * $widget 为文章挂件，$length 为字符数。
 */
function theme_text_excerpt($widget, int $length = 280): string
{
    ob_start();
    $widget->excerpt($length, '…');
    $text = (string) ob_get_clean();
    $text = (string) preg_replace('/(第\d+集){2,}/u', '', $text);
    $text = (string) preg_replace('/\[\/?(?:Music|Video|play|bilibili|gallery)[^\]]*\]/iu', '', $text);
    $text = trim((string) preg_replace('/\s+/u', ' ', $text));
    return $text;
}

/*
function themeFields($layout) {
    $logoUrl = new Typecho_Widget_Helper_Form_Element_Text('logoUrl', NULL, NULL, _t('站点LOGO地址'), _t('在这里填入一个图片URL地址, 以在网站标题前加上一个LOGO'));
    $layout->addItem($logoUrl);
}
*/


/* 取文章第一张图做列表缩略图；无图返回空 */
function theme_post_thumb($widget) {
    $text = isset($widget->text) ? (string) $widget->text : '';
    $url = '';
    if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', $text, $m)) {
        $url = $m[1];
    } elseif (preg_match('/!\[[^\]]*\]\(([^)\s]+)\)/', $text, $m)) {
        $url = $m[1];
    } elseif (preg_match('/\[gallery\](.*?)\[\/gallery\]/is', $text, $m)) {
        /* 兜底：[gallery] 图册的首张图（代码示例里的 &#91; 转义写法不会命中） */
        foreach (preg_split('/\s*\n\s*/', trim($m[1])) as $line) {
            $line = trim($line);
            if ('' === $line) continue;
            if (preg_match('/!\[[^\]]*\]\(([^)]+)\)/', $line, $mm)) $line = trim($mm[1]);
            elseif (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $line, $mm)) $line = trim($mm[1]);
            $line = trim(strip_tags($line));
            if ('' !== $line) { $url = $line; break; }
        }
    }
    if ('' === $url) return '';
    if (0 !== strpos($url, 'http') && 0 !== strpos($url, '/')) {
        ob_start(); $widget->options->siteUrl(); $base = ob_get_clean();
        $url = rtrim($base, '/') . '/' . ltrim($url, '/');
    }
    /* 卡片只显示 230px（2 倍屏 460 足够），Pexels 图降到 w=480 省流量；
       文章正文仍用原图，不受影响。 */
    if (false !== strpos($url, 'images.pexels.com')) {
        $url = preg_replace('/([?&]w=)\d+/', '${1}480', $url, 1);
    }
    return $url;
}

/* 保存文章时自动生成标签：标题加权 + 正文高频实词，与手动标签合并（只增不减，关掉开关即停用） */
class DL_AutoTag {
    private static function stopwords() {
        return array_flip(['的','了','是','在','和','有','就','不','也','都','要','对','等','及','或','但','而','与','从','到','为','以','这','那','个','上','下','中','我','你','他','她','它','们','自己','一个','没有','还是','就是','这样','这些','那些','以及','或者','因为','所以','如果','但是','不过','而且','通过','使用','支持','显示','设置','功能','页面','网站','博客','文章','内容','可以','进行','实现','提供','包括','同时','然后','之后','之前','这里','那里','什么','怎么','为什么','多少','时候','现在','我们','你们','他们','这个','那个','一些','一点','一种','一样','一直','一定','可能','需要','应该','能够','会','将','被','把','给','让','向','往','于','之','其','即','既','如','若','虽','然','且','乃','则','乎','者','所','能','可','着','过','起','出','入','来','去','回','开','关','看','听','说','想','做','用','发','放','收','拿','走','跑','吃','喝','玩','学','教','问','答','写','读','画','唱','跳','本站','本博','笔者','作者','自动']);
    }

    /* 从标题和正文提取高频实词；$siteTitle 用于排除站名本身（站名词给所有文章打标签没有意义） */
    public static function extract($title, $text, $siteTitle, $limit = 5) {
        $t = (string) $text;
        $t = preg_replace('/<!--markdown-->/', ' ', $t);
        $t = preg_replace('/```[\s\S]*?```/', ' ', $t);
        $t = preg_replace('/\[[^\]]*\]/', ' ', $t);
        $t = preg_replace('/\([^)]*\)/', ' ', $t);
        $t = preg_replace('/[#>*`_~|=\-+\\/\\\\]+/', ' ', $t);
        $t = strip_tags($t);
        $t = preg_replace('/https?:\/\/\S+/', ' ', $t);
        $stop = self::stopwords();
        // 站名本身的 2-4 字片段也视为停用词
        $st = (string) $siteTitle;
        $stLen = mb_strlen($st, 'UTF-8');
        for ($n = 2; $n <= 4; $n++) {
            for ($i = 0; $i + $n <= $stLen; $i++) {
                $stop[mb_substr($st, $i, $n, 'UTF-8')] = 1;
            }
        }
        $scores = [];
        $countGrams = function ($str, $weight) use (&$scores, $stop) {
            $len = mb_strlen($str, 'UTF-8');
            for ($n = 2; $n <= 4; $n++) {
                for ($i = 0; $i + $n <= $len; $i++) {
                    $g = mb_substr($str, $i, $n, 'UTF-8');
                    if (!preg_match('/^[\x{4e00}-\x{9fff}]+$/u', $g)) continue;
                    if (isset($stop[$g])) continue;
                    $bad = false;
                    for ($k = 0; $k < $n; $k++) {
                        if (isset($stop[mb_substr($g, $k, 1, 'UTF-8')])) { $bad = true; break; }
                    }
                    if ($bad) continue;
                    $scores[$g] = ($scores[$g] ?? 0) + $weight;
                }
            }
        };
        $countGrams((string) $title, 3);
        $countGrams($t, 1);
        preg_match_all('/[A-Za-z][A-Za-z0-9]{1,}/', (string) $title . ' ' . $t, $m);
        $enStop = ['the' => 1, 'and' => 1, 'for' => 1, 'with' => 1, 'this' => 1, 'that' => 1, 'from' => 1, 'into' => 1, 'http' => 1, 'https' => 1, 'www' => 1, 'com' => 1, 'org' => 1];
        foreach ($m[0] as $w) {
            $w = strtolower($w);
            if (isset($enStop[$w])) continue;
            $scores[$w] = ($scores[$w] ?? 0) + (stripos((string) $title, $w) !== false ? 3 : 1);
        }
        $cand = [];
        foreach ($scores as $g => $s) {
            if ($s >= 2) $cand[$g] = $s;
        }
        uksort($cand, function ($a, $b) use ($cand) {
            if ($cand[$a] !== $cand[$b]) return $cand[$b] <=> $cand[$a];
            return mb_strlen($b, 'UTF-8') <=> mb_strlen($a, 'UTF-8');
        });
        $picked = [];
        foreach (array_keys($cand) as $g) {
            $skip = false;
            foreach ($picked as $p) {
                if (mb_strlen($g, 'UTF-8') < mb_strlen($p, 'UTF-8') && mb_strpos($p, $g, 0, 'UTF-8') !== false) { $skip = true; break; }
            }
            if ($skip) continue;
            $picked[] = $g;
            if (count($picked) >= $limit) break;
        }
        return $picked;
    }

    /* write 过滤器：保存前把自动标签并入手动标签 */
    public static function filter($contents, $widget) {
        $on = $widget->options->autoTag;
        if ('0' === (string) $on) return $contents;
        $title = $contents['title'] ?? '';
        $text = $contents['text'] ?? '';
        $siteTitle = '';
        try { $siteTitle = (string) $widget->options->title; } catch (Exception $e) {}
        $auto = self::extract($title, $text, $siteTitle, 5);
        if (empty($auto)) return $contents;
        $manual = [];
        $raw = isset($contents['tags']) ? (string) $contents['tags'] : '';
        foreach (preg_split('/[,，]/', $raw) as $tag) {
            $tag = trim($tag);
            if ('' !== $tag) $manual[] = $tag;
        }
        $lower = [];
        foreach ($manual as $tag) { $lower[mb_strtolower($tag, 'UTF-8')] = 1; }
        foreach ($auto as $tag) {
            if (!isset($lower[mb_strtolower($tag, 'UTF-8')])) {
                $manual[] = $tag;
                $lower[mb_strtolower($tag, 'UTF-8')] = 1;
            }
        }
        $contents['tags'] = implode(',', $manual);
        return $contents;
    }
}
Typecho_Plugin::factory('Widget\\Contents\\Post\\Edit')->write = ['DL_AutoTag', 'filter'];

/* 音视频短代码扩展 */
require_once __DIR__ . '/media.php';

/* 置顶：自定义字段 sticky=1 的已发布文章（按发布时间倒序）。
   取消置顶 = 删除该自定义字段，或把值改成非 1 —— 之后文章自动回到原日期位置。 */
function theme_sticky_cids() {
    $db = Typecho_Db::get();
    $rows = $db->fetchAll($db->select('table.fields.cid')->from('table.fields')
        ->join('table.contents', 'table.fields.cid = table.contents.cid')
        ->where('table.fields.name = ?', 'sticky')
        ->where('table.fields.str_value = ?', '1')
        ->where('table.contents.type = ?', 'post')
        ->where('table.contents.status = ?', 'publish')
        ->order('table.contents.created', Typecho_Db::SORT_DESC));
    $cids = array();
    foreach ($rows as $row) $cids[] = (int) $row['cid'];
    return $cids;
}

/* 上下篇（替代核心 thePrev/theNext，修同秒文章互不可见的问题）：
   核心只用 created 严格比较（< 或 >），同一秒发布的文章互相跳过。
   这里以 (created, cid) 双键排序：同秒文章按 cid 衔接，全站形成一条不断链。
   实现上分两步查（先同秒、再跨时间），与双键排序等价，且避开 OR 条件与
   options->time 的不确定性；其余过滤与核心一致（已发布、同类型、无密码；
   下一篇另限 created < 当前时间，排除未来文章）。 */
function theme_adjacent_post($widget, $prev = true) {
    $db = Typecho_Db::get();
    $row = null;
    if ($prev) {
        /* 上一篇：同秒的按 cid 倒序优先，否则按时间倒序（同秒按 cid 倒序） */
        $row = $db->fetchRow($db->select('cid', 'title', 'slug')->from('table.contents')
            ->where('status = ?', 'publish')
            ->where('type = ?', $widget->type)
            ->where("password IS NULL OR password = ''")
            ->where('created = ? AND cid < ?', $widget->created, $widget->cid)
            ->order('cid', Typecho_Db::SORT_DESC)->limit(1));
        if (!$row) {
            $row = $db->fetchRow($db->select('cid', 'title', 'slug')->from('table.contents')
                ->where('status = ?', 'publish')
                ->where('type = ?', $widget->type)
                ->where("password IS NULL OR password = ''")
                ->where('created < ?', $widget->created)
                ->order('created', Typecho_Db::SORT_DESC)->order('cid', Typecho_Db::SORT_DESC)->limit(1));
        }
    } else {
        /* 下一篇：同秒的按 cid 正序优先，否则按时间正序（同秒按 cid 正序） */
        $row = $db->fetchRow($db->select('cid', 'title', 'slug')->from('table.contents')
            ->where('status = ?', 'publish')
            ->where('type = ?', $widget->type)
            ->where("password IS NULL OR password = ''")
            ->where('created = ? AND cid > ?', $widget->created, $widget->cid)
            ->order('cid', Typecho_Db::SORT_ASC)->limit(1));
        if (!$row) {
            $row = $db->fetchRow($db->select('cid', 'title', 'slug')->from('table.contents')
                ->where('status = ?', 'publish')
                ->where('type = ?', $widget->type)
                ->where("password IS NULL OR password = ''")
                ->where('created > ? AND created < ?', $widget->created, time())
                ->order('created', Typecho_Db::SORT_ASC)->order('cid', Typecho_Db::SORT_ASC)->limit(1));
        }
    }
    if (!$row) return null;
    $path = Typecho_Router::url('post', array('cid' => $row['cid'], 'slug' => $row['slug']));
    $siteOptions = Typecho_Widget::widget('Widget_Options');
    $index = isset($siteOptions->index) ? $siteOptions->index : null;
    if (!$index && isset($widget->options->index)) $index = $widget->options->index;
    return array('title' => $row['title'], 'permalink' => Typecho_Common::url($path, $index));
}
