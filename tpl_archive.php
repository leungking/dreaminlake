<?php
/**
 * 归档
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
    $this->widget('Widget_Contents_Post_Recent', 'pageSize=10000')->to($archives);
    $grouped = [];
    $total = 0;
    while ($archives->next()) {
        // 用挂件自带的 date()（带站点时区），不用原生 date()（服务器是 UTC，会差一天）
        ob_start(); $archives->date('Y'); $y = ob_get_clean();
        ob_start(); $archives->date('m'); $m = ob_get_clean();
        ob_start(); $archives->date('d'); $d = ob_get_clean();
        $grouped[$y][$m][] = [
            'day' => $d,
            'permalink' => $archives->permalink,
            'title' => $archives->title,
        ];
        $total++;
    }
    if ($total > 0):
        $yrs = array_keys($grouped);
        $range = count($yrs) > 1 ? end($yrs) . ' – ' . $yrs[0] . ' 年' : $yrs[0] . ' 年';
    ?>
    <p class="dl-arch-summary">共 <?php echo $total; ?> 篇文章 · <?php echo $range; ?></p>
    <div id="archives">
    <?php foreach ($grouped as $year => $months):
        $yearTotal = 0;
        foreach ($months as $posts) { $yearTotal += count($posts); }
    ?>
        <h3 class="al_year"><?php echo $year; ?> 年（<?php echo $yearTotal; ?> 篇）</h3>
        <ul class="al_mon_list">
        <?php foreach ($months as $mon => $posts): ?>
            <li><span class="al_mon"><?php echo (int) $mon; ?> 月（<?php echo count($posts); ?> 篇）</span>
            <ul class="al_post_list">
            <?php foreach ($posts as $post): ?>
                <li><span class="al-date"><?php echo $post['day']; ?>日</span><a href="<?php echo htmlspecialchars($post['permalink'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?></a></li>
            <?php endforeach; ?>
            </ul></li>
        <?php endforeach; ?>
        </ul>
    <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p class="dl-empty">还没有文章。</p>
    <?php endif; ?>
    </div>
</article>
<?php $this->need('footer.php'); ?>
