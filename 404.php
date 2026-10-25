<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
/** 404 不单独显示，直接跳到搜索页（302 临时跳转，不用 301，避免把坏地址永久指向搜索页）。 */
$this->response->redirect(Typecho_Common::url('search.html', $this->options->index));
exit;
