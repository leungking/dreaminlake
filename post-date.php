<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<time class="entry-date entry-date-tone-<?php echo ((int) $this->cid % 6) + 1; ?>" datetime="<?php $this->date('c'); ?>" aria-label="发布于 <?php $this->date('Y年m月d日'); ?>">
    <span class="entry-date-ym"><?php $this->date('Y.m'); ?></span>
    <strong class="entry-date-day"><?php $this->date('d'); ?></strong>
</time>
