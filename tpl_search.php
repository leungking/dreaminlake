<?php
/**
 * 搜索
 *
 * @package custom
 */
?>
<?php $this->need('header.php'); ?>
<article class="single-post page-entry">
    <h1 class="entry-title"><?php $this->title(); ?></h1>
    <div class="entry-content">
    <?php ob_start(); $this->content(); echo DL_Media::parse(ob_get_clean()); ?>
    <?php $this->need('search-form.php'); ?>
    </div>
    <?php $this->need('random-cards.php'); ?>
</article>
<?php $this->need('footer.php'); ?>
