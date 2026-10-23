<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$blocks = $this->options->sidebarBlock;
if (!is_array($blocks)) {
    $blocks = empty($blocks) ? array() : (array) $blocks;
}
?>
<?php if (!empty($blocks)): ?>
<aside class="sidebar" aria-label="底部信息">
<?php if (in_array('ShowRecentPosts', $blocks)): ?>
<section class="widget"><h2>最新文章</h2><ul><?php $this->widget('Widget_Contents_Post_Recent', 'pageSize=5')->parse('<li><a href="{permalink}">{title}</a></li>'); ?></ul></section>
<?php endif; ?>
<?php if (in_array('ShowRecentComments', $blocks)): ?>
<section class="widget"><h2>最近回复</h2><ul><?php $this->widget('Widget_Comments_Recent', 'pageSize=5')->to($comments); while ($comments->next()): ?><li><a href="<?php $comments->permalink(); ?>"><?php $comments->excerpt(30, '…'); ?></a></li><?php endwhile; ?></ul></section>
<?php endif; ?>
<?php if (in_array('ShowCategory', $blocks)): ?>
<section class="widget"><h2>分类</h2><?php $this->widget('Widget_Metas_Category_List')->listCategories(); ?></section>
<?php endif; ?>
<?php if (in_array('ShowArchive', $blocks)): ?>
<section class="widget"><h2>归档</h2><ul><?php $this->widget('Widget_Contents_Post_Date', 'type=month&format=Y 年 m 月')->parse('<li><a href="{permalink}">{date}</a></li>'); ?></ul></section>
<?php endif; ?>
<?php if (in_array('ShowOther', $blocks)): ?>
<section class="widget"><h2>其他</h2><ul><li><a href="<?php $this->options->feedUrl(); ?>">文章 RSS</a></li><li><a href="<?php $this->options->commentsFeedUrl(); ?>">评论 RSS</a></li><?php if ($this->user->hasLogin()): ?><li><a href="<?php $this->options->adminUrl(); ?>">进入后台</a></li><?php else: ?><li><a href="<?php $this->options->adminUrl('login.php'); ?>">登录</a></li><?php endif; ?></ul></section>
<?php endif; ?>
</aside>
<?php endif; ?>
