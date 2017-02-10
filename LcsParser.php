<?php

include 'simple_html_dom.php';


function getMatchesData($simple_html_dom_object)
{
  $matchesResults = [];

  foreach($simple_html_dom_object->find('.schedule-item') as $game){
      $matchesResults[] = array(
          "blue-team" => getTeamData($game->find(".blue-team", 0)),
          "red-team" => getTeamData($game->find(".red-team", 1)),
      );
  }

  return $matchesResults;
}

function getTeamData($teamObject){
   $teamName = $teamObject->find(".team-name", 0)->plaintext;
   $teamAcronym = $teamObject->find(".team-acronym", 0)->plaintext;
   $teamResult = $teamObject->find(".defeat", 0);
   if($teamResult == null){
    $teamResult = $teamObject->find(".victory", 0);
   }
   $teamResult = $teamResult->plaintext;

   $teamMatchData = array(
    "name" => $teamName,
    "acronym" => $teamAcronym,
    "result" => $teamResult
   );

   return $teamMatchData;
}

$html = file_get_html('page.html');
$matchesResults = getMatchesData($html);

$weekNumber = $html->find("div.current", 0)->plaintext;
$weekNumber = intval($weekNumber, 10);

foreach ($matchesResults as $match) {
    if(is_null($match["blue-team"]["result"])) continue; // Match not played yet

    var_dump($match);
}
var_dump($weekNumber);

