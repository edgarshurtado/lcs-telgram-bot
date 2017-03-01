#!/bin/bash

phantomjs pageLoad.js > page.html
php LcsParser.php