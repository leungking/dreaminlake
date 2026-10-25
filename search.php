<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>
<section class="search-head">
    <h1 class="entry-title"><?php $this->archiveTitle(array('search' => '搜索“%s”的结果'), '', ''); ?></h1>
    <?php $this->need('search-form.php'); ?>
</section>
<?php if ($this->have()): ?>
<?php while ($this->next()): ?>
<?php $this->need('post-block.php'); ?>
<?php endwhile; ?>
<?php else: ?>
<?php $this->need('random-cards.php'); ?>
<?php endif; ?>
<?php $this->pageNav('&laquo;', '&raquo;'); ?>
<?php $this->need('footer.php'); ?>
