<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>
<article class="single-post page-entry">
    <h1 class="entry-title"><?php $this->title(); ?></h1>
    <div class="entry-content"><?php ob_start(); $this->content(); echo DL_Media::parse(ob_get_clean()); ?></div>
</article>
<?php $this->need('comments.php'); ?>
<?php $this->need('footer.php'); ?>
