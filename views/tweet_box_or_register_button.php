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
        data-post_status="<?php echo get_post(get_the_ID())->post_status ?>"
        data-post_permalink="<?php echo get_permalink($_GET['post']) ?>"
    <?php endif ?>

    data-user_display_name=<?php echo json_encode(wp_get_current_user()->display_name) ?>

    <?php if ( Socialer_Session::getInstance()->isSendTweetOnUpdate() ): ?>
        data-autosend-tweet='1'
        <?php Socialer_Session::getInstance()->unsendTweetOnUpdate() ?>
    <?php endif ?>

    <?php if (
        Socialer_Session::getInstance()->isScheduleTweetOnUpdate()
        && Socialer_Session::getInstance()->hasScheduleInfo()
    ): ?>
        <?php $schedule_info = Socialer_Session::getInstance()->getScheduleInfo(true) ?>
        data-schedule-tweet='1'
        data-schedule-date='<?php echo $schedule_info['date'] ?>'
        data-schedule-delay='<?php echo $schedule_info['delay'] ?>'
    <?php endif ?>
></div>

<script type="text/javascript" src="<?php echo $this->js_base_url . 'jquery/jquery.js'; ?>"></script>
<script type="text/javascript" src="<?php echo $this->js_plugin_base_url . 'socialer.js?v='.Socialer::JS_VERSION ?>"></script>