<?php

require_once("Subject.php");

class Server implements Subject {

    private $observers = array();
    
    public function attach($observer) {
        if(!in_array($observer, $this->observers)) {
            $this->observers[] = $observer;
            return true;
        } else {
            return false;
        }
    }

    public function detach($observer) {
        if(!in_array($observer, $this->observers)) {
            return false;
        } else {
            $key = array_search($observer, $this->observers);
            unset($this->observers[$key]);
            $this->observers = array_values($this->observers); //Reindex array after unset
            return true;
        }
    }


    public function notify($message) {
        
        foreach($this->observers as $observer) {
            $observer->update($message);
        }
    }
}
