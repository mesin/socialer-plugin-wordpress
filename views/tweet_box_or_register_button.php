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
        data-post_id=<?php echo get_the_ID() ?>
        data-post_date=<?php echo json_encode(get_post(get_the_ID())->post_date) ?>
        data-post_title="<?php echo get_post(get_the_ID())->post_title ?>"
        data-post_permalink="<?php echo get_permalink($_GET['post']) ?>"
    <?php endif ?>

    <?php if ( isset($_SESSION['send_tweet_on_update']) ): ?>
        data-autosend-tweet='1'
        <?php unset($_SESSION['send_tweet_on_update']); ?>
    <?php endif ?>

    <?php if ( isset($_SESSION['schedule_tweet_on_update']) ): ?>
        data-schedule-tweet='1'
        data-schedule-date='<?php echo $_SESSION['schedule_post_date'] ?>'
        data-schedule-delay='<?php echo $_SESSION['schedule_delay'] ?>'
        <?php unset($_SESSION['schedule_tweet_on_update']); ?>
        <?php unset($_SESSION['schedule_post_date']); ?>
        <?php unset($_SESSION['schedule_delay']); ?>
    <?php endif ?>
></div>

<script type="text/javascript" src="<?php echo $this->js_base_url . 'jquery/jquery.js'; ?>"></script>
<script type="text/javascript" src="<?php echo $this->js_plugin_base_url . 'socialer.js?v='.Socialer::JS_VERSION ?>"></script>