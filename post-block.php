<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $dlThumb = theme_post_thumb($this); ?>
<?php ob_start(); $this->title(); $dlT = ob_get_clean(); ?>
<article class="post-block<?php if ('' !== $dlThumb): ?> has-thumb<?php endif; ?>">
    <div class="post-block-main">
        <header class="entry-heading">
            <?php $this->need('post-date.php'); ?>
            <h2 class="entry-title"><?php $this->title(); ?></h2>
        </header>
        <?php $dlEx = theme_text_excerpt($this, 280); if ('' !== $dlEx): ?><p class="entry-excerpt"><?php echo $dlEx; ?></p><?php endif; ?>
    </div>
    <?php if ('' !== $dlThumb): ?><div class="post-block-thumb"><img src="<?php echo htmlspecialchars($dlThumb, ENT_QUOTES, 'UTF-8'); ?>" alt="" loading="lazy"></div><?php endif; ?>
    <a class="post-block-link" href="<?php $this->permalink(); ?>" aria-label="<?php echo htmlspecialchars($dlT, ENT_QUOTES, 'UTF-8', false); ?>"></a>
</article>
