(function() {
    var photos = document.getElementById('photos');
    if (!photos) return;

    var count = Number(photos.dataset.count);
    var page = Number(photos.dataset.page);
    var perPage = Number(photos.dataset.perPage);
    if (![count, page, perPage].every(function(value) {
        return Number.isSafeInteger(value) && value > 0;
    })) return;
    var html = document.documentElement;

    try {
        var params = new URLSearchParams(window.location.search);
        var initDir = params.get('lbDir');
        if (initDir === 'next' || initDir === 'prev') html.dataset.lbDir = initDir;
    } catch (e) {}

    function parseIdxFromHash(hash) {
        var m = String(hash).match(/^#?lb-(\d+)$/);
        return m ? parseInt(m[1], 10) : NaN;
    }

    function go(dir, hash) {
        html.dataset.lbDir = dir;
        var idx = parseIdxFromHash(hash);
        if (!Number.isFinite(idx)) {
            location.hash = hash;
            return;
        }
        var targetPage = Math.floor(idx / perPage) + 1;
        if (targetPage !== page) {
            location.href = '?page=' + targetPage + '&lbDir=' + dir + '#' + hash;
        } else {
            location.hash = hash;
        }
    }

    document.addEventListener('click', function(e) {
        if (e.target.closest('.lightbox-next')) html.dataset.lbDir = 'next';
        else if (e.target.closest('.lightbox-prev')) html.dataset.lbDir = 'prev';
        else delete html.dataset.lbDir;
    }, true);

    document.addEventListener('keydown', function(e) {
        var h = location.hash;
        var idx = parseIdxFromHash(h);
        if (!Number.isSafeInteger(idx) || idx < 0 || idx >= count) return;
        if (e.key === 'Escape') {
            delete html.dataset.lbDir;
            location.hash = 'photos';
        } else if (e.key === 'ArrowRight') {
            go('next', 'lb-' + ((idx + 1) % count));
        } else if (e.key === 'ArrowLeft') {
            go('prev', 'lb-' + ((idx - 1 + count) % count));
        }
    });
}());
