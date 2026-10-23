<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>
<?php if ($this->have()): ?>
<?php while ($this->next()): ?>
<article class="post-card">
    <header class="entry-heading">
        <?php $this->need('post-date.php'); ?>
        <h2 class="entry-title"><a href="<?php $this->permalink(); ?>" title="<?php $this->title(); ?>"><?php $this->title(); ?></a></h2>
    </header>
    <div class="entry-content"><?php $this->content('继续阅读'); ?></div>
</article>
<?php endwhile; ?>
<?php else: ?>
<section class="empty-state"><h2>没有找到相关内容</h2></section>
<?php endif; ?>
<?php $this->pageNav('&laquo;', '&raquo;'); ?>
<?php $this->need('footer.php'); ?>
