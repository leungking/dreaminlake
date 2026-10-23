<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
/**
 * DreamInLake — 羊皮纸美术博客主题：浅色/深色双模式（顶部灯泡切换、跟随系统）、
 * PJAX 无刷新翻页、音乐/视频/B站/九宫格短代码、图片灯箱、三端响应式。
 *
 * @package DreamInLake
 * @author 梦里依稀的小湖
 * @version 20261021
 * @link https://610000.xyz/
 */
?>
<?php $this->need('header.php'); ?>
<?php while ($this->next()): ?>
<article class="post-card">
    <header class="entry-heading">
        <?php $this->need('post-date.php'); ?>
        <h2 class="entry-title"><a href="<?php $this->permalink(); ?>" title="<?php $this->title(); ?>"><?php $this->title(); ?></a></h2>
    </header>
    <div class="entry-content"><?php ob_start(); $this->content('继续阅读'); echo DL_Media::parse(ob_get_clean()); ?></div>
</article>
<?php endwhile; ?>
<?php $this->pageNav('&laquo;', '&raquo;'); ?>
<?php $this->need('footer.php'); ?>
