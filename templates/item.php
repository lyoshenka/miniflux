<article
    id="item-<?= $item['id'] ?>"
    class="feed-<?= $item['feed_id'] ?>"
    data-item-id="<?= $item['id'] ?>"
    data-item-status="<?= $item['status'] ?>"
    data-item-bookmark="<?= $item['bookmark'] ?>"
    data-item-page="<?= $menu ?>"
    <?= $hide ? 'data-hide="true"' : '' ?>
    >
    <h2 <?= Helper\isRTL($item) ? 'dir="rtl"' : '' ?>>
        <span class="bookmark-icon"></span>
        <span class="read-icon"></span>
        <?= Helper\favicon($favicons, $item['feed_id']) ?>
        <a
            href="?action=show&amp;menu=<?= $menu ?>&amp;id=<?= $item['id'] ?>"
            class="show"
        ><?= Helper\escape($item['title']) ?></a>
    </h2>
    <?php if ($display_mode === 'full'): ?>
        <div class="preview" <?= Helper\isRTL($item) ? 'dir="rtl"' : '' ?>>
            <?= $item['content'] ?>
        </div>
    <?php else: ?>
        <p class="preview" <?= Helper\isRTL($item) ? 'dir="rtl"' : '' ?>>
            <?= Helper\escape(Helper\summary(strip_tags($item['content']), 50, 300)) ?>
        </p>
    <?php endif ?>
    <ul class="item-menu">
        <li>
            <?php if (! isset($item['feed_title'])): ?>
                <?= Helper\get_host_from_url($item['url']) ?>
            <?php else: ?>
                <a href="?action=feed-items&amp;feed_id=<?= $item['feed_id'] ?>" title="<?= t('Show only this subscription') ?>"><?= Helper\escape($item['feed_title']) ?></a>
            <?php endif ?>
        </li>
        <li class="hide-mobile">
            <span title="<?= dt('%e %B %Y %k:%M', $item['updated']) ?>"><?= Helper\relative_time($item['updated']) ?></span>
        </li>
        <li class="hide-mobile">
            <a href="<?= $item['url'] ?>" class="original" rel="noreferrer" target="_blank" data-action="original-link"><?= t('original link') ?></a>
        </li>
        <?php if ($item['enclosure']): ?>
            <li>
                <a href="<?= $item['enclosure'] ?>" rel="noreferrer" target="_blank"><?= t('attachment') ?></a>
            </li>
        <?php endif ?>
        <?= \PicoFarad\Template\load('bookmark_links', array('item' => $item, 'menu' => $menu, 'offset' => $offset, 'source' => '')) ?>
        <?= \PicoFarad\Template\load('status_links', array('item' => $item, 'redirect' => $menu, 'offset' => $offset)) ?>
    </ul>
</article>
