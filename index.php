<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
/**
 * DreamInLake — 羊皮纸美术博客主题：浅色/深色双模式（顶部灯泡切换、跟随系统）、
 * PJAX 无刷新翻页、音乐/视频/B站/九宫格短代码、图片灯箱、三端响应式。
 *
 * @package DreamInLake
 * @author 梦里依稀的小湖
 * @version 20261025
 * @link https://610000.xyz/
 */
?>
<?php $this->need('header.php'); ?>
<?php
/* 置顶：仅首页第一页，把 sticky=1 的文章提到最前面（带“置顶”牌子）；
   主列表里跳过它们，避免重复出现（被置顶的旧文在其原日期页会少占一位）。 */
$dlStickyCids = theme_sticky_cids();
if (1 === $this->getCurrentPage() && !empty($dlStickyCids)):
    /* 注意：need() 只在 Archive 挂件上有，Recent 挂件没有 —— 必须用带独立别名的
       第二个 Archive 挂件（@sticky 别名保证与主列表不是同一实例），它取出的行含
       全字段（含正文），卡片的缩略图和摘要才能正常渲染。 */
    $this->widget('Widget_Archive@sticky', 'pageSize=1000')->to($dlSticky);
    while ($dlSticky->next()):
        if (!in_array((int) $dlSticky->cid, $dlStickyCids, true)) continue;
?>
<div class="post-sticky-wrap"><span class="post-sticky-label">置顶</span><?php $dlSticky->need('post-block.php'); ?></div>
<?php
    endwhile;
endif;
?>
<?php while ($this->next()): ?>
<?php if (in_array((int) $this->cid, $dlStickyCids, true)) continue; ?>
<?php $this->need('post-block.php'); ?>
<?php endwhile; ?>
<?php $this->pageNav('&laquo;', '&raquo;'); ?>
<?php $this->need('footer.php'); ?>
