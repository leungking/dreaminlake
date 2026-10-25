/* DreamInLake 4.0 音视频前端：APlayer / ArtPlayer / B站点击播放 / 分集切换 */
(function () {
    'use strict';

    var aplayers = [];
    var artplayers = [];

    /* 按需加载：页面真有播放器元素时才拉库，纯文字页省 216KB */
    var DL_BASE = window.DL_ASSETS || (function () {
        var s = document.currentScript ? document.currentScript.src : '';
        return s ? s.split('?')[0].replace(/[^/]*$/, '') + 'assets/lib/' : 'assets/lib/';
    })();
    function dlLoadScript(src) {
        return new Promise(function (res, rej) {
            var s = document.createElement('script');
            s.src = src; s.onload = res; s.onerror = rej;
            document.head.appendChild(s);
        });
    }
    function dlLoadCss(href) {
        if (document.querySelector('link[href="' + href + '"]')) return;
        var l = document.createElement('link');
        l.rel = 'stylesheet'; l.href = href;
        document.head.appendChild(l);
    }
    var apLibPromise = null, artLibPromise = null;
    function ensureAPlayer() {
        if (typeof APlayer !== 'undefined') return Promise.resolve();
        if (!apLibPromise) { dlLoadCss(DL_BASE + 'aplayer.min.css'); apLibPromise = dlLoadScript(DL_BASE + 'aplayer.min.js'); }
        return apLibPromise;
    }
    function ensureArtPlayer() {
        if (typeof ArtPlayer !== 'undefined') return Promise.resolve();
        if (!artLibPromise) {
            artLibPromise = dlLoadScript(DL_BASE + 'artplayer.min.js').then(function () {
                /* 官方 dist 是 Parcel 包，不挂 window.ArtPlayer（这是视频黑屏的真根源）：
                   从 parcelRequire 缓存里按类特征（instances/version 静态属性）取出入口类 */
                if (typeof ArtPlayer !== 'undefined') return;
                Object.keys(window).forEach(function (k) {
                    if (!/^parcelRequire/.test(k) || typeof window[k] !== 'function') return;
                    var cache = window[k].cache || {};
                    Object.keys(cache).forEach(function (id) {
                        var exp = cache[id] && cache[id].exports;
                        if (!exp) return;
                        var cand = exp.default || exp;
                        if (typeof cand === 'function' && Array.isArray(cand.instances) && cand.version) {
                            window.ArtPlayer = cand;
                        }
                    });
                });
            });
        }
        return artLibPromise;
    }

    function initAPlayers(scope) {
        if (typeof APlayer === 'undefined') return;
        scope.querySelectorAll('.dl-aplayer:not([data-inited])').forEach(function (el) {
            el.setAttribute('data-inited', '1');
            try {
                aplayers.push(new APlayer({
                    container: el,
                    audio: [{
                        name: el.getAttribute('data-name') || '未知曲目',
                        artist: el.getAttribute('data-artist') || '',
                        url: el.getAttribute('data-url'),
                        cover: el.getAttribute('data-cover') || '',
                        lrc: el.getAttribute('data-lrc') || ''
                    }]
                }));
            } catch (e) {}
        });
    }

    function initArtPlayer(el) {
        var url = el.getAttribute('data-url') || '';
        if (!url || url.indexOf('bilibili:') === 0) return null;
        if (typeof ArtPlayer === 'undefined') return null;
        try {
            var native = el.querySelector('video.dl-video-native');
            var p = new ArtPlayer({
                container: el,
                url: url,
                poster: el.getAttribute('data-pic') || '',
                playsInline: true,
                autoSize: false,
                aspectRatio: true,
                setting: true,
                pip: true,
                fullscreen: true,
                fastForward: true
            });
            artplayers.push(p);
            if (native && native.parentNode) native.parentNode.removeChild(native);
            return p;
        } catch (e) { return null; }
    }

    function bindBili(scope) {
        var els = scope.classList && scope.classList.contains('dl-bili')
            ? [scope]
            : scope.querySelectorAll('.dl-bili:not([data-inited])');
        Array.prototype.forEach.call(els, function (el) {
            el.setAttribute('data-inited', '1');
            el.addEventListener('click', function () {
                var src = el.getAttribute('data-src');
                if (!src || el.querySelector('iframe')) return;
                var f = document.createElement('iframe');
                f.src = src;
                f.setAttribute('allowfullscreen', '');
                f.setAttribute('allow', 'autoplay; fullscreen');
                f.setAttribute('referrerpolicy', 'no-referrer-when-downgrade');
                el.innerHTML = '';
                el.appendChild(f);
                el.style.cursor = 'default';
            });
        });
    }

    function initVideos(scope) {
        scope.querySelectorAll('.dl-video:not([data-inited])').forEach(function (el) {
            el.setAttribute('data-inited', '1');
            initArtPlayer(el);
        });
        scope.querySelectorAll('.dl-play-multi').forEach(function (box) {
            if (box.getAttribute('data-tabs-inited')) return;
            box.setAttribute('data-tabs-inited', '1');
            var wrap = box.querySelector('.dl-video-wrap');
            box.querySelectorAll('.dl-video-tab').forEach(function (tab) {
                tab.addEventListener('click', function () {
                    box.querySelectorAll('.dl-video-tab').forEach(function (t) { t.classList.remove('active'); });
                    tab.classList.add('active');
                    var url = tab.getAttribute('data-url') || '';
                    artplayers.forEach(function (p) {
                        try { if (p.template && wrap.contains(p.template.$container)) p.destroy(); } catch (e) {}
                    });
                    wrap.innerHTML = '';
                    if (url.indexOf('bilibili:') === 0) {
                        var bvid = url.substring(9);
                        var bili = document.createElement('div');
                        bili.className = 'dl-bili';
                        var f = document.createElement('iframe');
                        f.src = window.DL_BILI_WORKER ? window.DL_BILI_WORKER.replace(/\/?$/, '/') + bvid : 'https://player.bilibili.com/player.html?bvid=' + bvid + '&page=1&high_quality=1&danmaku=0';
                        f.setAttribute('allowfullscreen', 'true');
                        f.setAttribute('allow', 'autoplay; fullscreen; picture-in-picture');
                        f.setAttribute('referrerpolicy', 'no-referrer-when-downgrade');
                        bili.appendChild(f);
                        wrap.appendChild(bili);
                    } else {
                        var v = document.createElement('div');
                        v.className = 'dl-video';
                        v.setAttribute('data-url', url);
                        v.setAttribute('data-inited', '1');
                        wrap.appendChild(v);
                        initArtPlayer(v);
                    }
                });
            });
        });
    }

    /* 图片灯箱：点击正文图片全屏查看 */
    var lightbox, lightboxImg;
    /* ---------- 灯箱：同组多图可切换（九宫格内成组，否则同一篇文章内成组） ---------- */
    var lbList = [], lbIdx = 0;
    /* 灯箱看原图：图册格子用的是 w=600 小图，点开时换回 w=1200；非 Pexels 图（无 w 参数）原样 */
    function dlFullSrc(u) {
        return u.replace(/([?&]w=)\d+/, function (m, p1) { return p1 + '1200'; });
    }
    function lbShow(i) {
        if (!lbList.length) return;
        lbIdx = (i + lbList.length) % lbList.length;
        lightboxImg.src = dlFullSrc(lbList[lbIdx]);
        var multi = lbList.length > 1;
        var c = document.getElementById('dl-lightbox-count');
        if (c) { c.textContent = (lbIdx + 1) + ' / ' + lbList.length; c.style.display = multi ? '' : 'none'; }
        ['dl-lightbox-prev', 'dl-lightbox-next'].forEach(function (id) {
            var b = document.getElementById(id);
            if (b) b.style.display = multi ? '' : 'none';
        });
        lightboxImg.classList.remove('dl-lb-in');
        void lightboxImg.offsetWidth;
        lightboxImg.classList.add('dl-lb-in');
        if (multi) [lbIdx - 1, lbIdx + 1].forEach(function (j) {
            var im = new Image();
            im.src = dlFullSrc(lbList[(j + lbList.length) % lbList.length]);
        });
    }
    function lbOpen(list, idx) {
        lbList = list;
        lbShow(idx);
        lightbox.hidden = false;
        document.body.style.overflow = 'hidden';
    }
    function lbClose() {
        lightbox.hidden = true;
        document.body.style.overflow = '';
    }
    function initLightbox(scope) {
        if (!lightbox) {
            lightbox = document.getElementById('dl-lightbox');
            lightboxImg = document.getElementById('dl-lightbox-img');
            if (!lightbox) return;
            var prev = document.createElement('button');
            prev.id = 'dl-lightbox-prev'; prev.className = 'dl-lb-btn'; prev.type = 'button';
            prev.setAttribute('aria-label', '上一张'); prev.innerHTML = '&lsaquo;';
            var next = document.createElement('button');
            next.id = 'dl-lightbox-next'; next.className = 'dl-lb-btn'; next.type = 'button';
            next.setAttribute('aria-label', '下一张'); next.innerHTML = '&rsaquo;';
            var count = document.createElement('span');
            count.id = 'dl-lightbox-count';
            lightbox.appendChild(prev); lightbox.appendChild(next); lightbox.appendChild(count);
            prev.addEventListener('click', function (e) { e.stopPropagation(); lbShow(lbIdx - 1); });
            next.addEventListener('click', function (e) { e.stopPropagation(); lbShow(lbIdx + 1); });
            var closeBtn = document.getElementById('dl-lightbox-close');
            if (closeBtn) closeBtn.addEventListener('click', function (e) { e.stopPropagation(); lbClose(); });
            lightbox.addEventListener('click', function (e) { if (e.target === lightbox) lbClose(); });
            document.addEventListener('keydown', function (e) {
                if (!lightbox || lightbox.hidden) return;
                if (e.key === 'Escape') lbClose();
                else if (e.key === 'ArrowLeft') lbShow(lbIdx - 1);
                else if (e.key === 'ArrowRight') lbShow(lbIdx + 1);
            });
            var touchX = null;
            lightbox.addEventListener('touchstart', function (e) {
                touchX = e.touches && e.touches.length ? e.touches[0].clientX : null;
            }, { passive: true });
            lightbox.addEventListener('touchend', function (e) {
                if (touchX === null || !e.changedTouches || !e.changedTouches.length) return;
                var dx = e.changedTouches[0].clientX - touchX;
                touchX = null;
                if (Math.abs(dx) > 40) lbShow(lbIdx + (dx < 0 ? 1 : -1));
            }, { passive: true });
        }
        scope.querySelectorAll('.entry-content img:not([data-lb])').forEach(function (img) {
            img.setAttribute('data-lb', '1');
            img.addEventListener('click', function () {
                var group = (img.closest && (img.closest('.dl-gallery') || img.closest('.entry-content'))) || null;
                var imgs = group ? Array.prototype.slice.call(group.querySelectorAll('img')) : [img];
                var list = imgs.map(function (im) { return im.currentSrc || im.src; });
                lbOpen(list, imgs.indexOf(img));
            });
        });
    }

    /* iOS Safari 对原生 loading=lazy 触发不可靠（PJAX 注入的内容更甚），用 IntersectionObserver 主动接管：
       临近视口就转 eager，仍未加载的再重设一次 src 强制触发 */
    function lazyRescue(scope) {
        var imgs = scope.querySelectorAll('img[loading="lazy"]:not([data-lz])');
        if (!imgs.length) return;
        function promote(im) {
            im.setAttribute('data-lz', '1');
            try { im.loading = 'eager'; } catch (e) {}
            setTimeout(function () {
                if (!im.complete || im.naturalWidth === 0) {
                    var s = im.getAttribute('src');
                    if (s) im.src = s;
                }
            }, 800);
        }
        if (!('IntersectionObserver' in window)) {
            imgs.forEach(promote);
            return;
        }
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) {
                if (en.isIntersecting) { io.unobserve(en.target); promote(en.target); }
            });
        }, { rootMargin: '800px 0px' });
        imgs.forEach(function (im) { io.observe(im); });
    }

    /* 标签球：标签按斐波那契球面分布，绕球心缓慢旋转；字号 = 引用次数 × 透视缩放；
       悬停暂停、拖动可转（拖动后不触发点击）；无 JS 时退化为平铺列表；减动效偏好时静止 */
    var tagSphereRAF = null, tagSphereActive = null;
    window.addEventListener('pointermove', function (e) {
        var c = tagSphereActive;
        if (!c || !c.dragging) return;
        if (Math.abs(e.clientX - c.lastX) + Math.abs(e.clientY - c.lastY) > 5) c.moved = true;
        c.rotY += (e.clientX - c.lastX) * 0.005;
        c.rotX = Math.max(-1.2, Math.min(1.2, c.rotX + (e.clientY - c.lastY) * 0.005));
        c.lastX = e.clientX; c.lastY = e.clientY;
    });
    window.addEventListener('pointerup', function () {
        var c = tagSphereActive;
        if (!c) return;
        if (c.dragging && c.moved) c.justDragged = true;
        c.dragging = false;
    });
    function initTagSphere(scope) {
        var sphere = scope.querySelector ? scope.querySelector('#dl-tag-sphere') : null;
        if (!sphere) return;
        if (tagSphereRAF) cancelAnimationFrame(tagSphereRAF);
        var tags = Array.prototype.slice.call(sphere.querySelectorAll('.dl-tag3d'));
        if (!tags.length) return;
        sphere.classList.add('is-3d');
        var counts = tags.map(function (el) { return parseInt(el.getAttribute('data-count'), 10) || 0; });
        var min = Math.min.apply(null, counts), max = Math.max.apply(null, counts), spread = max - min;
        var pts = tags.map(function (el, i) {
            var n = tags.length, y = n > 1 ? 1 - (i / (n - 1)) * 2 : 0;
            var r = Math.sqrt(Math.max(0, 1 - y * y)), th = 2.399963 * i;
            return { x: Math.cos(th) * r, y: y, z: Math.sin(th) * r, el: el,
                base: spread > 0 ? 13 + (counts[i] - min) / spread * 13 : 16 };
        });
        var c = { sphere: sphere, pts: pts, rotX: -0.35, rotY: 0,
            hovering: false, dragging: false, moved: false, justDragged: false, lastX: 0, lastY: 0,
            reduceMotion: !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) };
        tagSphereActive = c;
        sphere.addEventListener('mouseenter', function () { c.hovering = true; });
        sphere.addEventListener('mouseleave', function () { c.hovering = false; });
        sphere.addEventListener('pointerdown', function (e) { c.dragging = true; c.moved = false; c.lastX = e.clientX; c.lastY = e.clientY; });
        sphere.addEventListener('click', function (e) {
            if (c.justDragged) { e.preventDefault(); e.stopPropagation(); c.justDragged = false; }
        }, true);
        function place() {
            var w = sphere.clientWidth || 320, R = w / 2 - 60, cx = w / 2, cy = w / 2;
            var small = w < 400, cosX = Math.cos(c.rotX), sinX = Math.sin(c.rotX), cosY = Math.cos(c.rotY), sinY = Math.sin(c.rotY);
            pts.forEach(function (p) {
                var y1 = p.y * cosX - p.z * sinX, z1 = p.y * sinX + p.z * cosX;
                var x2 = p.x * cosY + z1 * sinY, z2 = -p.x * sinY + z1 * cosY;
                var s = 0.85 + 0.3 * z2;
                p.el.style.left = (cx + x2 * R * s) + 'px';
                p.el.style.top = (cy + y1 * R * s) + 'px';
                p.el.style.fontSize = ((small ? p.base * 0.85 : p.base) * s) + 'px';
                p.el.style.opacity = (0.35 + 0.65 * (z2 + 1) / 2).toFixed(2);
                p.el.style.zIndex = Math.round((z2 + 1) * 100);
            });
        }
        function frame() {
            if (!document.body.contains(sphere)) return;
            if (!c.dragging && !c.hovering && !c.reduceMotion) c.rotY += 0.0035;
            place();
            tagSphereRAF = requestAnimationFrame(frame);
        }
        frame();
    }

    /* 深色/浅色：灯泡切换（手动选择存 localStorage）；未手动选过时跟随系统变化 */
    function initTheme() {
        var bulb = document.getElementById('bulbHit') || document.getElementById('bulb');
        if (bulb && !bulb.getAttribute('data-theme-bound')) {
            bulb.setAttribute('data-theme-bound', '1');
            var toggle = function () {
                var next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-theme', next);
                try { localStorage.setItem('dl-theme', next); } catch (e) {}
            };
            bulb.addEventListener('click', toggle);
            bulb.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(); }
            });
        }
        if (window.matchMedia && !window.__dlThemeMq) {
            window.__dlThemeMq = 1;
            var mq = window.matchMedia('(prefers-color-scheme: dark)');
            var follow = function (ev) {
                var saved = null;
                try { saved = localStorage.getItem('dl-theme'); } catch (e) {}
                if (saved !== 'dark' && saved !== 'light') {
                    document.documentElement.setAttribute('data-theme', ev.matches ? 'dark' : 'light');
                }
            };
            if (mq.addEventListener) mq.addEventListener('change', follow);
            else if (mq.addListener) mq.addListener(follow);
        }
    }

    /* 代码块一键复制：只给正文代码块右上角加小按钮，点后短暂显示“已复制”；
       PJAX 换页后 init() 会重跑，已加过的用 data-copy 跳过 */
    function initCodeCopy(scope) {
        scope.querySelectorAll('.entry-content pre:not([data-copy])').forEach(function (pre) {
            var code = pre.querySelector('code');
            pre.setAttribute('data-copy', '1');
            if (!code) return;
            var text = code.textContent;
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'dl-code-copy';
            btn.textContent = '复制';
            btn.setAttribute('aria-label', '复制代码');
            btn.addEventListener('click', function () {
                function done(ok) {
                    btn.textContent = ok ? '已复制' : '复制失败';
                    btn.classList.add('copied');
                    setTimeout(function () { btn.textContent = '复制'; btn.classList.remove('copied'); }, 1600);
                }
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(function () { done(true); }, function () { done(false); });
                } else {
                    try {
                        var ta = document.createElement('textarea');
                        ta.value = text;
                        ta.style.position = 'fixed'; ta.style.opacity = '0';
                        document.body.appendChild(ta);
                        ta.select();
                        var ok = document.execCommand('copy');
                        document.body.removeChild(ta);
                        done(ok);
                    } catch (e) { done(false); }
                }
            });
            pre.appendChild(btn);
        });
    }

    function init() {
        initTheme();
        initCodeCopy(document);
        bindBili(document);
        lazyRescue(document);
        initLightbox(document);
        initTagSphere(document);
        if (document.querySelector('.dl-aplayer:not([data-inited])')) {
            ensureAPlayer().then(function () { initAPlayers(document); }, function () {});
        }
        if (document.querySelector('.dl-video:not([data-inited]),.dl-play-multi')) {
            ensureArtPlayer().then(function () { initVideos(document); }, function () { initVideos(document); });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    /* ---------- PJAX：站内链接只换正文区，不整页刷新 ---------- */
    function destroyPlayers() {
        aplayers.forEach(function (p) { try { p.pause(); } catch (e) {} try { p.destroy(); } catch (e) {} });
        aplayers = [];
        artplayers.forEach(function (p) { try { p.pause(); } catch (e) {} try { p.destroy(); } catch (e) {} });
        artplayers = [];
    }
    var pjaxCapable = !!(window.fetch && window.DOMParser && window.history && history.pushState);
    function pjaxUrlOk(u) {
        if (u.origin !== location.origin) return false;
        var p = u.pathname;
        if (/\/admin(\/|$)/.test(p) || p.indexOf('/action/') !== -1) return false;
        if (p.indexOf('/feed') !== -1 || p.indexOf('/usr/uploads/') !== -1) return false;
        if (/\.(zip|rar|7z|jpe?g|png|gif|webp|mp4|mp3|pdf)$/i.test(p)) return false;
        if (u.search.indexOf('replyTo') !== -1) return false;
        return true;
    }
    function pjaxGo(url, push) {
        document.body.classList.add('dl-loading');
        fetch(url, { credentials: 'same-origin', headers: { 'X-PJAX': 'true' } })
            .then(function (r) { if (!r.ok) throw new Error('bad'); return r.text(); })
            .then(function (html) {
                var doc = new DOMParser().parseFromString(html, 'text/html');
                var nw = doc.querySelector('.content-wrap');
                var ow = document.querySelector('.content-wrap');
                if (!nw || !ow) throw new Error('no wrap');
                destroyPlayers();
                ow.innerHTML = nw.innerHTML;
                document.title = doc.title;
                if (push) history.pushState({ pjax: 1 }, '', url);
                window.scrollTo(0, 0);
                ow.querySelectorAll('script').forEach(function (old) {
                    var s = document.createElement('script');
                    if (old.src) s.src = old.src; else s.textContent = old.textContent;
                    old.parentNode.replaceChild(s, old);
                });
                document.querySelectorAll('.site-nav a').forEach(function (a) {
                    a.classList.toggle('current', a.href === location.href);
                });
                init();
                document.body.classList.remove('dl-loading');
            })
            .catch(function () { location.href = url; });
    }
    if (pjaxCapable) {
        document.addEventListener('click', function (e) {
            if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
            var a = e.target && e.target.closest ? e.target.closest('a[href]') : null;
            if (!a || a.target || a.hasAttribute('download')) return;
            var u;
            try { u = new URL(a.href, location.href); } catch (err) { return; }
            if (u.href === location.href) return;
            if (u.hash && u.pathname === location.pathname && u.search === location.search) return;
            if (!pjaxUrlOk(u)) return;
            e.preventDefault();
            pjaxGo(u.href, true);
        });
        window.addEventListener('popstate', function () { pjaxGo(location.href, false); });
    }
})();
