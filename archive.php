<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>
<?php if ($this->have()): ?>
<?php while ($this->next()): ?>
<?php $this->need('post-block.php'); ?>
<?php endwhile; ?>
<?php else: ?>
<section class="empty-state"><h2>没有找到相关内容</h2></section>
<?php endif; ?>
<?php $this->pageNav('&laquo;', '&raquo;'); ?>
<?php $this->need('footer.php'); ?>
