var page = require('webpage').create();
var system = require('system');

var week = system.args[1];


var url = "http://www.lolesports.com/en_US/eu-lcs/eu_2017_spring/schedule/regular_season";

url += "/" + week;

page.open( url, function () {
    setTimeout(function(){
        console.log(page.content);
        phantom.exit();
    }, 5000);
});
