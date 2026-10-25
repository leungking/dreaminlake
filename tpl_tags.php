<?php
/**
 * 标签云
 *
 * @package custom
 */
?>
<?php $this->need('header.php'); ?>
<article class="single-post page-entry">
    <h1 class="entry-title"><?php $this->title(); ?></h1>
    <div class="entry-content">
    <?php ob_start(); $this->content(); echo DL_Media::parse(ob_get_clean()); ?>
    <?php
    $db = Typecho_Db::get();
    $options = Typecho_Widget::widget('Widget_Options');
    $tags = $db->fetchAll($db->select()->from('table.metas')
        ->where('table.metas.type = ?', 'tag')
        ->order('table.metas.count', Typecho_Db::SORT_DESC));
    if (!empty($tags)):
        $counts = array_column($tags, 'count');
        $max = max($counts);
        $min = min($counts);
        $spread = $max - $min;
    ?>
    <div class="dl-tag-sphere-wrap">
        <div class="dl-tag-sphere" id="dl-tag-sphere" aria-label="标签球">
    <?php foreach ($tags as $tag):
        $type = $tag['type'];
        $routeExists = (null != Typecho_Router::get($type));
        $tag['pathinfo'] = $routeExists ? Typecho_Router::url($type, $tag) : '#';
        $tag['permalink'] = Typecho_Common::url($tag['pathinfo'], $options->index);
        $size = ($spread > 0) ? (0.9 + ($tag['count'] - $min) / $spread * 0.7) : 1.1;
    ?>
        <a class="dl-tag3d" data-count="<?php echo (int) $tag['count']; ?>" title="<?php echo (int) $tag['count']; ?> 篇文章" style="font-size:<?php echo round($size, 2); ?>rem" href="<?php echo htmlspecialchars($tag['permalink'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($tag['name'], ENT_QUOTES, 'UTF-8'); ?></a>
    <?php endforeach; ?>
        </div>
    </div>
    <?php else: ?>
    <p class="dl-empty">还没有标签。</p>
    <?php endif; ?>
    </div>
    <?php $this->need('random-cards.php'); ?>
</article>
<?php $this->need('footer.php'); ?>
