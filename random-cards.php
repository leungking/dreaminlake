<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
/* 随机文章卡片（标签页、搜索页共用）：每次打开重新抽取 3 篇已发布文章，首页同款卡片样式；
   调用方须把本文件放在 entry-content 外面，卡片不继承正文排版样式。 */
$db = Typecho_Db::get();
$options = Typecho_Widget::widget('Widget_Options');
$allCids = $db->fetchAll($db->select('cid')->from('table.contents')
    ->where('table.contents.type = ?', 'post')
    ->where('table.contents.status = ?', 'publish')
    ->where('table.contents.created < ?', time()));
shuffle($allCids);
$pick = array_slice(array_column($allCids, 'cid'), 0, 3);
$rand = [];
if (!empty($pick)) {
    $rows = $db->fetchAll($db->select('cid', 'title', 'slug', 'created', 'text')->from('table.contents')
        ->where('table.contents.cid IN ?', $pick));
    $byCid = [];
    foreach ($rows as $row) $byCid[$row['cid']] = $row;
    foreach ($pick as $cid) if (isset($byCid[$cid])) $rand[] = $byCid[$cid];
}
if (!empty($rand)):
?>
<div class="dl-random-cards">
    <?php foreach ($rand as $post):
        $routeExists = (null != Typecho_Router::get('post'));
        $post['pathinfo'] = $routeExists ? Typecho_Router::url('post', $post) : '#';
        $post['permalink'] = Typecho_Common::url($post['pathinfo'], $options->index);
        /* 缩略图：复用首页卡片的 theme_post_thumb（随机行是数组，用 text + options 拼一个最小挂件样子传进去） */
        $fake = new stdClass();
        $fake->text = $post['text'];
        $fake->options = $options;
        $dlThumb = theme_post_thumb($fake);
        $dlT = $post['title'];
        /* 摘要：贴近首页卡片的 280 字摘要（随机行调不了挂件 excerpt，手工清洗 markdown，显示效果一致） */
        $ex = $post['text'];
        $ex = preg_replace('/\[gallery\][\s\S]*?\[\/gallery\]/', ' ', $ex);
        $ex = preg_replace('/<!--markdown-->/', ' ', $ex);
        $ex = preg_replace('/!\[[^\]]*\]\([^)]*\)/', ' ', $ex);
        $ex = preg_replace('/\[([^\]]*)\]\([^)]*\)/', '$1', $ex);
        $ex = preg_replace('/https?:\/\/\S+/', ' ', $ex);
        $ex = preg_replace('/\[[^\]]*\]/', ' ', $ex);
        $ex = strip_tags($ex);
        $ex = preg_replace('/(第\d+集){2,}/u', '', $ex);
        $ex = preg_replace('/\s+/', ' ', trim($ex));
        if (mb_strlen($ex, 'UTF-8') > 280) $ex = mb_substr($ex, 0, 280, 'UTF-8') . '…';
        /* 日期章：与 post-date.php 同款式（随机行是数组，手工拼 time 标签，时间按站点时区换算） */
        $tz = (int) $options->timezone;
        $ts = $post['created'] + $tz;
        $sign = $tz >= 0 ? '+' : '-'; $abs = abs($tz);
        $off = sprintf('%s%02d:%02d', $sign, intdiv($abs, 3600), intdiv($abs % 3600, 60));
        $tone = ((int) $post['cid'] % 6) + 1;
    ?>
<article class="post-block<?php if ('' !== $dlThumb): ?> has-thumb<?php endif; ?>">
    <div class="post-block-main">
        <header class="entry-heading">
            <time class="entry-date entry-date-tone-<?php echo $tone; ?>" datetime="<?php echo gmdate('Y-m-d\TH:i:s', $ts) . $off; ?>" aria-label="发布于 <?php echo gmdate('Y年m月d日', $ts); ?>">
                <span class="entry-date-ym"><?php echo gmdate('Y.m', $ts); ?></span>
                <strong class="entry-date-day"><?php echo gmdate('d', $ts); ?></strong>
            </time>
            <h2 class="entry-title"><?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
        </header>
        <?php if ('' !== $ex): ?><p class="entry-excerpt"><?php echo htmlspecialchars($ex, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
    </div>
    <?php if ('' !== $dlThumb): ?><div class="post-block-thumb"><img src="<?php echo htmlspecialchars($dlThumb, ENT_QUOTES, 'UTF-8'); ?>" alt="" loading="lazy"></div><?php endif; ?>
    <a class="post-block-link" href="<?php echo htmlspecialchars($post['permalink'], ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo htmlspecialchars($dlT, ENT_QUOTES, 'UTF-8', false); ?>"></a>
</article>
    <?php endforeach; ?>
</div>
<?php endif; ?>
