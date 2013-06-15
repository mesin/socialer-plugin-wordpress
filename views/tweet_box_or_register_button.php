<script type="text/javascript" src="<?php echo $this->js_base_url . 'jquery/jquery.js'; ?>"></script>
<script type="text/javascript" src="<?php echo $this->js_plugin_base_url . 'socialer.js?v='.Socialer::JS_VERSION ?>"></script>
<div id="socialer-container" style="display: none"></div>
<div id="socialer-container-wait" style="display: none">
    <br>
    <img src="<?php echo $this->img_plugin_base_url . 'ajax-loader.gif' ?>" />
</div>
<div style="display: none"
    class="alljs-dispatcher"
    id="alljs-dispatcher-socialer"
    data-function="alljs.socialer.get_correct_box"
    data-base-url="<?php echo $this->plugin_base_url . 'socialer_ajax.php?action=' ?>"
    <?php if (@$_GET['post']): ?>
    data-post-id="<?php echo @$_GET['post'] ?>"
    data-post-date="<?php echo get_post(get_the_ID())->post_date ?>"
    data-url="<?php echo get_permalink(get_the_ID()) ?>"
    data-title="<?php echo get_post(get_the_ID())->post_title ?>"
        <?php if (isset($_SESSION['send_tweet_on_update'])): ?>
            data-autosend-tweet="1"
            <?php unset($_SESSION['send_tweet_on_update']); ?>
        <?php endif ?>
    <?php endif ?>
></div>