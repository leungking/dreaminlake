<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>

<h2>404 - <?php _e('页面没找到'); ?></h2>

<form method="post">
<div><input type="text" name="s" class="text" size="20" /> <input type="submit" class="submit" value="<?php _e('搜索'); ?>" /></div>
</form>

<?php $this->need('footer.php'); ?>