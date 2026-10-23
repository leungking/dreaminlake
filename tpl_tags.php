<?php
/**
 * _tpl tags_
 *
 *@package custom
 *
 */
?>

<?php $this->need('header.php');?>

    <?php
        $db = Typecho_Db::get();
        $options = Typecho_Widget::widget('Widget_Options');
        $tags= $db->fetchAll($db->select()->from('table.metas')
                ->where('table.metas.type = ?', 'tag')
                ->order('table.metas.order', Typecho_Db::SORT_DESC));
        foreach($tags AS $tag) {
            $type = $tag['type'];
            $routeExists = (NULL != Typecho_Router::get($type));
            $tag['pathinfo'] = $routeExists ? Typecho_Router::url($type, $tag) : '#';
            $tag['permalink'] = Typecho_Common::url($tag['pathinfo'], $options->index);
            echo "<a href=\"".$tag['permalink']."\">".$tag['name']."</a> ";
        }
    ?>

<?php $this->need('footer.php');?>