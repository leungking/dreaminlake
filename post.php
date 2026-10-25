<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>
<article class="single-post">
    <header class="entry-heading">
        <?php $this->need('post-date.php'); ?>
        <h1 class="entry-title"><?php $this->title(); ?></h1>
    </header>
    <div class="entry-content"><?php ob_start(); $this->content(); echo DL_Media::parse(ob_get_clean()); ?></div>
</article>
<nav class="post-navigation" aria-label="文章导航">
    <div><span>上一篇</span><?php $dlPrev = theme_adjacent_post($this, true); if ($dlPrev): ?><a href="<?php echo htmlspecialchars($dlPrev['permalink'], ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars($dlPrev['title'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($dlPrev['title'], ENT_QUOTES, 'UTF-8'); ?></a><?php else: ?><em>已经是第一篇</em><?php endif; ?></div>
    <div class="next"><span>下一篇</span><?php $dlNext = theme_adjacent_post($this, false); if ($dlNext): ?><a href="<?php echo htmlspecialchars($dlNext['permalink'], ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars($dlNext['title'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($dlNext['title'], ENT_QUOTES, 'UTF-8'); ?></a><?php else: ?><em>已经是最后一篇</em><?php endif; ?></div>
</nav>
<?php $this->need('comments.php'); ?>
<?php $this->need('footer.php'); ?>
