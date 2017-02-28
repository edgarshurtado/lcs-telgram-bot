var page = require('webpage').create();
var system = require('system');

checkArguments(system.args);

var week = system.args.length === 2
            ? system.args[1]
            : -1;

var url = "http://www.lolesports.com/en_US/eu-lcs/eu_2017_spring/schedule/regular_season";

if(week >= 1 ){
    url += "/" + week;
}

page.open( url, function () {
    setTimeout(function(){
        console.log(page.content);
        phantom.exit();
    }, 5000);
});

function checkArguments(arguments){

    var nArguments = arguments.length;

    var argumentIsNaN = isNaN(arguments[1]);

    if((nArguments > 2) || argumentIsNaN){
        console.log("Invalid argument");
        phantom.exit(1);
    }

}
