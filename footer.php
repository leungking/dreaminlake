<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?></main><?php $this->need('sidebar.php'); ?></div><div id="content_bottom"></div><footer id="dreaminlake_footer"><p>&copy; <?php echo date('Y'); ?> <a href="<?php $this->options->siteUrl(); ?>"><?php $this->options->title(); ?></a></p><p><a href="<?php $this->options->feedUrl(); ?>">文章 RSS</a> · <a href="<?php $this->options->commentsFeedUrl(); ?>">评论 RSS</a> · <a href="https://typecho.org/">Typecho</a></p></footer></div>

<div id="dl-lightbox" hidden><img id="dl-lightbox-img" alt="" /><span id="dl-lightbox-close" aria-label="关闭">×</span></div>
<script>window.DL_ASSETS='<?php $this->options->themeUrl('assets/lib/'); ?>';window.DL_BILI_WORKER=<?php echo json_encode(isset($this->options->biliWorker) ? trim((string) $this->options->biliWorker) : ''); ?>;</script>
<script src="<?php $this->options->themeUrl('media.js?v=20261023a'); ?>"></script>
<?php $this->footer(); ?></body></html>
