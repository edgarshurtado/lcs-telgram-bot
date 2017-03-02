#!/bin/bash

while true; do

phantomjs pageLoad.js > page.html
php LcsParser.php

sleep 5m;

done