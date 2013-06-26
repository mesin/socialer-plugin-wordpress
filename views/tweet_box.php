<br>
<div id="socialer-tweet-box" class="postbox ">
    <div class="handlediv" title="Click to toggle"><br></div><h3 class="hndle">
                <span>
                    <img style="width: 16px; height: 16px;" src="<?php echo $this->img_plugin_base_url . 'twitter-bird-light-bgs.png' ?>" />
                    Socialer
                </span>
    </h3>
    <div class="inside">
        <div class="tagsdiv" id="post_tweet_box">
            <div class="jaxtag">
                <div id="socialer-message"><?php echo Socialer_Session::getInstance()->getMessages() ?></div>
                <div id="socialer-custom-messages" class="updated" style="display: none; padding: 8px;"></div>
                <h3>Tweet from @<?php echo Socialer_Session::getInstance()->getTwitterScreenName() ?>: Enter Tweet text without url.
                    Tweet will be sent when published.</h3>
                <textarea
                    name="socialer_tweet_body"
                    rows="3"
                    style="width: 100%"
                    class="the-tags"
                    id="socialer-tweet-body"
                    maxlength="<?php echo $this->tweet_maxlen ?>"
                    ><?php
                    if ( Socialer_Session::getInstance()->getLastTweetText() ) {
                        echo Socialer_Session::getInstance()->getLastTweetText(true);
                    } elseif ( isset($_POST['text']) ) {
                        echo $_POST['text'];
                    } else {
                        //echo $this->post_title;
                    }
                    ?></textarea>
                <p class="howto">Maximum <?php echo $this->tweet_maxlen ?> characters. Available: <span id="socialer-tweet-chars-left"></span>.
                    Permalink will be added automatically
                    <?php if (@$_GET['post']): ?>
                        ( <?php echo $this->permalink ?> )
                    <?php else: ?>
                        when post will be published.
                    <?php endif ?>
                </p>
                    <?php if ( get_post($_REQUEST['post'])->post_status == Socialer::POST_STATUS_PUBLISHED ): ?>
                    <label for="socialer-tweeting-enabled">
                        Tweeting on Update:
                        <input
                            type="checkbox"
                            id="socialer-tweeting-enabled"
                            name="socialer-tweeting-enabled"
                            >
                    </label>
                    <br><br>
                        <a class="button button-primary" id="socialer-ajax-push-tweet">
                            Send Tweet Right Now
                        </a>
                        <img style="display: none" id="socialer-ajax-push-tweet-wait" src="<?php echo $this->img_plugin_base_url . 'ajax-loader.gif' ?>" />
                    <?php endif ?>

                <?php if (!Socialer::isDraft() && !Socialer::isNewPost()): ?>
                    <hr>
                    <h3>Scheduling</h3>

                    <br>
                    <label for="socialer-tweet-schedule">
                    Schedule:
                    <input
                        type="checkbox"
                        id="socialer-tweet-schedule"
                        name="socialer-tweet-schedule"
                        <?php if ( Socialer::isFuture() ): ?>
                        checked="checked"
                        <?php endif ?>
                    >
                    </label>
                    <br>
                    <label id="socialer-tweet-delay-label" for="socialer-tweet-delay">
                    Delay in hours after publishing:
                    <select id="socialer-tweet-delay" name="socialer-tweet-delay">
                        <?php for ($hour = 1; $hour < 13; $hour++): ?>
                        <option
                            value="<?php echo $hour ?>"
                            <?php if (
                                $hour == Socialer_Settings::getDefaultScheduleHours()
                                || $hour == @$_REQUEST['socialer-tweet-delay']
                            ): ?>
                                selected="selected"
                            <?php endif ?>
                        ><?php echo $hour ?> hr</option>
                        <?php endfor ?>
                    </select>
                    </label>
                    <?php if (
                        @$_REQUEST['post']
                    ): ?>
                    <br><br>
                    <a class="button button-primary" id="socialer-ajax-schedule-tweet">
                        Schedule Tweet Right Now
                    </a>
                    <img style="display: none" id="socialer-ajax-schedule-tweet-wait" src="<?php echo $this->img_plugin_base_url . 'ajax-loader.gif' ?>" />
                    <?php endif ?>

                <?php endif // isDraft? ?>

                    <hr>
                    <br>
                    <?php if (Socialer_Settings::isShowDashboardButton()): ?>
                    <a class="button button-primary" href="<?php echo Socialer::get_socialer_register_url(false) ?>" target="_blank">
                        Go to Dashboard
                    </a>
                    <?php endif ?>
            </div>
        </div>
    </div>
</div>