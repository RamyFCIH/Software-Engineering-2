<?php

require_once('./Observer.php');
require_once './Subject.php';

class Client implements Observer {
    
    private $subject;
    private $userInfo;


    public function __construct($subject, $userInfo) {
        $this->subject = $subject;
        $this->userInfo = $userInfo;
    }

    public function update($message) {
            echo $this->userInfo." received a message: ( ";
            echo $message;
            echo " )<hr />";
    }

    public function subscribe() {
        $this->subject->attach($this);
        echo "<hr />";
        echo $this->userInfo." has subscribed successfully.";
        echo "<hr />";
    }

    public function unsubscribe() {
        $this->subject->detach($this);
        echo "<hr />";
        echo $this->userInfo." has unsubscribed successfully.";
        echo "<hr />";
    }

};
