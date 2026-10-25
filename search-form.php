<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<form class="dl-search-form" method="get" action="<?php $this->options->siteUrl(); ?>" data-base="<?php $this->options->siteUrl(); ?>" role="search">
    <input type="search" name="s" value="<?php if (method_exists($this, 'keywords')) $this->keywords(); ?>" placeholder="搜索" aria-label="搜索" required>
    <button type="submit">搜索</button>
</form>
<script>
(function () {
    document.querySelectorAll('.dl-search-form').forEach(function (f) {
        f.addEventListener('submit', function (e) {
            var q = f.querySelector('input[name="s"]').value.trim();
            if (q) { e.preventDefault(); window.location.href = f.getAttribute('data-base') + 'search/' + encodeURIComponent(q) + '/'; }
        });
    });
})();
</script>
