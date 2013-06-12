<br>
<div id="socialer-tweet-box" class="postbox ">
    <div class="handlediv" title="Click to toggle"><br></div><h3 class="hndle">
                <span>
                    <img style="width: 16px; height: 16px;" src="<?php echo $this->img_plugin_base_url . 'twitter-bird-light-bgs.png' ?>" />
                    Socialer Tweet Box
                </span>
    </h3>
    <div class="inside">
        <div class="tagsdiv" id="post_tweet_box">
            <div class="jaxtag">
                <div id="socialer-message"><?php echo Socialer::showMessage() ?></div>
                <div id="socialer-custom-messages" class="updated" style="display: none; padding: 8px;"></div>
                <p>Enter tweet text without URL:</p>
                <textarea
                    name="socialer_tweet_body"
                    rows="3"
                    style="width: 100%"
                    class="the-tags"
                    id="socialer-tweet-body"
                    maxlength="<?php echo $this->tweet_maxlen ?>"
                    ><?php
                    if (isset($_SESSION['soc_last_tweet_status']) && $_SESSION['soc_last_tweet_status'] == false ) {
                        if (isset($_SESSION['soc_last_tweet_text'])){
                            echo $_SESSION['soc_last_tweet_text'];
                        } else {
                            echo $this->post_title;
                        }
                    }
                    ?></textarea>
                <p class="howto">Maximum <?php echo $this->tweet_maxlen ?> characters. Available: <span id="socialer-tweet-chars-left"></span>.
                    Permalink will be added to message automatically
                    <?php if (@$_GET['post']): ?>
                        ( <?php echo $this->permalink ?> )
                    <?php else: ?>
                        when post will be published.
                    <?php endif ?>
                </p>
                <p>
                    <?php if (
                            @get_post(@$_REQUEST['post'])->post_status == Socialer::POST_STATUS_PUBLISHED
                        ): ?>
                        <a class="button button-primary" id="socialer-ajax-push-tweet">
                            Send Tweet Right Now
                        </a>
                        <img style="display: none" id="socialer-ajax-push-tweet-wait" src="<?php echo $this->img_plugin_base_url . 'ajax-loader.gif' ?>" />
                    <?php endif ?>

                    <hr>
                    <h4>Options</h4>
                    <label for="socialer-tweet-onoff">
                    Tweeting on Update:
                    <input
                        type="checkbox"
                        id="socialer-tweet-onoff"
                        name="socialer-tweet-onoff"
                        <?php if ( @get_post(get_the_ID())->post_status == Socialer::POST_STATUS_FUTURE ): ?>
                            checked="checked"
                        <?php endif ?>
                        >
                    </label>

                    <br>
                    <label for="socialer-tweet-type">
                    Schedule:
                    <input
                        type="checkbox"
                        id="socialer-tweet-type"
                        name="socialer-tweet-type"
                        <?php if ( @get_post(get_the_ID())->post_status == Socialer::POST_STATUS_FUTURE ): ?>
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
                            <?php if ( $hour == Socialer_Settings::getDefaultScheduleHours() ): ?>
                                selected="selected"
                            <?php endif ?>
                        ><?php echo $hour ?> hr</option>
                        <?php endfor ?>
                    </select>
                    </label>

                    <hr>
                    <br>
                    <?php if (Socialer_Settings::isShowDashboardButton()): ?>
                    <a class="button button-primary" href="<?php echo Socialer::get_socialer_register_url(false) ?>" target="_blank">
                        Go to Dashboard
                    </a>
                    <?php endif ?>
                </p>
            </div>
        </div>
    </div>
</div>