#!/bin/bash

while true; do

# Obtain page from Riot
phantomjs pageLoad.js > page.html

# Parse the result HTML
php LcsParser.php

sleep 30

done