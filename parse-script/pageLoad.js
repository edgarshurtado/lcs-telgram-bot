var page = require('webpage').create();

var url = "http://www.lolesports.com/en_US/eu-lcs/eu_2017_spring/schedule/regular_season";

page.open( url, function () {
    setTimeout(function(){
        console.log(page.content);
        phantom.exit();
    }, 5000);
});
