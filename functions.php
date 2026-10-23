<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function themeConfig($form) {
    $logoUrl = new Typecho_Widget_Helper_Form_Element_Text('logoUrl', NULL, NULL, _t('站点LOGO地址'), _t('在这里填入一个图片URL地址, 以在网站标题前加上一个LOGO'));
    $form->addInput($logoUrl);

    $biliWorker = new Typecho_Widget_Helper_Form_Element_Text('biliWorker', NULL, NULL, _t('B站播放 Worker 地址'), _t('可选。[bilibili] 短代码通过一个自备的 Cloudflare Worker 代理页播放（微信 / iOS 内可播）。填入你自己部署的 Worker 地址前缀，如 https://example.workers.dev/video/，主题会把 BV 号拼在其后。留空则回退为 B 站官方播放器直嵌（普通浏览器可用，微信 / iOS 内置浏览器可能受限）。Worker 的部署方法见主题 README 的「B站播放」一节。'));
    $form->addInput($biliWorker);

    $sidebarBlock = new Typecho_Widget_Helper_Form_Element_Checkbox('sidebarBlock',
    array('ShowRecentPosts' => _t('显示最新文章'),
    'ShowRecentComments' => _t('显示最近回复'),
    'ShowCategory' => _t('显示分类'),
    'ShowArchive' => _t('显示归档'),
    'ShowOther' => _t('显示其它杂项')),
    array('ShowRecentPosts', 'ShowRecentComments', 'ShowCategory', 'ShowArchive', 'ShowOther'), _t('侧边栏显示'));

    $form->addInput($sidebarBlock->multiMode());
}

function theme_random_posts(){
$defaults = array(
'number' => 5, //输出文章条数
'before' => '<ul class="list">', // ul选择器,自行修改
'after' => '</ul>',
'xformat' => '<li><a href="{permalink}">{title}</a></li>'
);
$db = Typecho_Db::get();
$sql = $db->select()->from('table.contents')
->where('status = ?','publish')
->where('type = ?', 'post')
->limit($defaults['number'])
->order('RAND()');
$result = $db->fetchAll($sql);
echo $defaults['before'];
foreach($result as $val){
$val = Typecho_Widget::widget('Widget_Abstract_Contents')->filter($val);
echo str_replace(array('{permalink}', '{title}'),array($val['permalink'], $val['title']), $defaults['xformat']);
}
echo $defaults['after'];
}

/*
function themeFields($layout) {
    $logoUrl = new Typecho_Widget_Helper_Form_Element_Text('logoUrl', NULL, NULL, _t('站点LOGO地址'), _t('在这里填入一个图片URL地址, 以在网站标题前加上一个LOGO'));
    $layout->addItem($logoUrl);
}
*/


/* 音视频短代码扩展 */
require_once __DIR__ . '/media.php';
