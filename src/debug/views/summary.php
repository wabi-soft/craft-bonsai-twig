<?php
/** @var wabisoft\bonsaitwig\debug\BonsaiTwigPanel $panel */
/** @var int $count */
?>
<div class="yii-debug-toolbar__block">
    <a href="<?= $panel->getUrl() ?>">
        TPL <span class="yii-debug-toolbar__label yii-debug-toolbar__label_<?= $count > 0 ? 'info' : 'default' ?>"><?= $count ?></span>
    </a>
</div>
