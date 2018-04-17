<?php

interface Observer {
    public function update($message);
    public function subscribe();
    public function unsubscribe(); 
};
