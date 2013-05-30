<br>
<div id="socialer-tweet-box" class="postbox ">
    <div class="handlediv" title="Click to toggle"><br></div><h3 class="hndle">
                <span>
                    <img style="width: 16px; height: 16px;" src="<?php echo $this->js_plugin_base_url . 'img/twitter-bird-light-bgs.png' ?>" />
                    Socialer Tweet Box
                </span>
    </h3>
    <div class="inside">
        <div class="tagsdiv" id="post_tweet_box">
            <div class="jaxtag">
                <div id="socialer-message"><?php echo Socialer::showMessage() ?></div>
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
                    <!--a class="button button-primary" href="<?php echo Socialer::get_socialer_register_url(false) ?>" target="_blank">
                                Go to Dashboard
                            </a-->
                    <?php if (@$_GET['post']): ?>
                        <a class="button button-primary" id="socialer-ajax-push-tweet">
                            Send Tweet
                        </a>
                        <img style="display: none" id="socialer-ajax-push-tweet-wait" src="<?php echo $this->js_plugin_base_url . 'img/ajax-loader.gif' ?>" />
                    <?php endif ?>
                </p>
            </div>
        </div>
    </div>
</div>