<?php
class Socialer_Session {

    const MESSAGE_INFO      = 1;
    const MESSAGE_ERROR     = 2;

    public function __construct() {
        if ( !session_id() ) {
            session_start();
        }
    }

    /**
     * @return Socialer_Session
     */
    public static function getInstance() {
        static $i = null;

        if ( null === $i ) {
            $i = new self;
        }

        return $i;
    }

    /**
     * @param $text
     * @return $this
     */
    public function setLastTweetText( $text ) {
        $_SESSION['soc_last_tweet_text'] = $text;
        return $this;
    }

    /**
     * @param bool $clear
     * @return mixed
     */
    public function getLastTweetText( $clear = false ) {
        $text = @$_SESSION['soc_last_tweet_text'];

        if ( true == $clear ) {
            unset($_SESSION['soc_last_tweet_text']);
        }

        return $text;
    }

    /**
     * @return $this
     */
    public function clearLastTweetText() {
        unset($_SESSION['soc_last_tweet_text']);
        return $this;
    }

    /**
     * @param bool $send
     * @return $this
     */
    public function sendTweetOnUpdate( $send = true ) {
        $_SESSION['send_tweet_on_update'] = (Boolean)$send;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSendTweetOnUpdate() {
        return (
            isset($_SESSION['send_tweet_on_update'])
            && $_SESSION['send_tweet_on_update'] == true
        );
    }

    /**
     * @return $this
     */
    public function unsendTweetOnUpdate() {
        unset($_SESSION['send_tweet_on_update']);
        return $this;
    }

    /**
     * @param bool $schedule
     * @return $this
     */
    public function scheduleTweetOnUpdate( $schedule = true ) {
        $_SESSION['schedule_tweet_on_update'] = (Boolean)$schedule;
        return $this;
    }

    /**
     * @return bool
     */
    public function isScheduleTweetOnUpdate() {
        return (
            isset($_SESSION['schedule_tweet_on_update'])
            && $_SESSION['schedule_tweet_on_update'] == true
        );
    }

    /**
     * @return $this
     */
    public function unscheduleTweetOnUpdate() {
        unset($_SESSION['schedule_tweet_on_update']);
        return $this;
    }

    /**
     * @param $date
     * @param $delay
     * @return $this
     */
    public function saveScheduleInfo( $date, $delay ) {
        $_SESSION['schedule_post_date']     = $date;
        $_SESSION['schedule_delay']         = $delay;

        return $this;
    }

    /**
     * @param bool $clear
     * @return array
     */
    public function getScheduleInfo( $clear = false ) {
        $date       = @$_SESSION['schedule_post_date'];
        $delay      = @$_SESSION['schedule_delay'];

        if ( $clear ) {
            unset($_SESSION['schedule_post_date']);
            unset($_SESSION['schedule_delay']);
            $this->unscheduleTweetOnUpdate();
        }

        return array('date' => $date, 'delay' => $delay);
    }

    /**
     * @return bool
     */
    public function hasScheduleInfo() {
        $date       = @$_SESSION['schedule_post_date'];
        $delay      = @$_SESSION['schedule_delay'];

        return ( $date && $delay );
    }

    /**
     * @param $message
     * @param int $type
     * @return $this
     */
    public function addMessage( $message, $type = self::MESSAGE_INFO ) {
        if ( !isset($_SESSION['soc_notices']) ) {
            $_SESSION['soc_notices'] = array();
        }

        $_SESSION['soc_notices'][] = array(
            'm'     => $message,
            't'     => $type
        );

        return $this;
    }

    /**
     * @return bool
     */
    public function hasMessages() {
        if ( !isset($_SESSION['soc_notices']) || !is_array($_SESSION['soc_notices']) ) {
            return false;
        }

        return true;
    }

    /**
     * @return string
     */
    public function getMessages() {
        static $view = null;

        if ( null == $view ) {
            $view = Socialer::getView();
        }

        if ( !isset($_SESSION['soc_notices']) || !is_array($_SESSION['soc_notices']) ) {
            $_SESSION['soc_notices'] = array();
            return '';
        }

        $rendered_messages = '';

        foreach ( $_SESSION['soc_notices'] as $message_info ) {
            $type = @$message_info['t'];
            $view->assign('message', @$message_info['m']);

            switch ( $type ) {
                default:
                case self::MESSAGE_INFO:
                    $rendered_messages .= $view->render('messages/info.php');
                    break;

                case self::MESSAGE_ERROR:
                    $rendered_messages .= $view->render('messages/error.php');
                    break;
            }
        }

        $_SESSION['soc_notices'] = array();

        return $rendered_messages;
    }

    /**
     * @return string
     */
    public static function getTwitterScreenName() {
        return @$_SESSION['twitter_screen_name'];
    }

    /**
     * @param $name
     * @return $this
     */
    public function setTwitterScreenName($name) {
        $_SESSION['twitter_screen_name'] = $name;
        return $this;
    }
}