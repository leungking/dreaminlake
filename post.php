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
    <div><span>上一篇</span><?php $this->thePrev('%s', '<em>已经是第一篇</em>'); ?></div>
    <div class="next"><span>下一篇</span><?php $this->theNext('%s', '<em>已经是最后一篇</em>'); ?></div>
</nav>
<?php $this->need('comments.php'); ?>
<?php $this->need('footer.php'); ?>
