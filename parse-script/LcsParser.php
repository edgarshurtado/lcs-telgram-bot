<?php

include 'simple_html_dom.php';

include __DIR__ . "/../config.php"; // Loads $mysql_credentials

// Set connection to the DB
$dns = "mysql:dbname=" . $mysql_credentials['database']
  . ";host=" . $mysql_credentials["host"];

$conn = new PDO(
  $dns,
  $mysql_credentials["user"] ,
  $mysql_credentials["password"]
);

// Parse HTML file
$html = file_get_html('page.html');
$matches_results = getMatchesData($html);

// Get week number from the html
$week_number = $html->find("div.selected", 0)->plaintext;
$week_number = intval($week_number, 10);

foreach ($matches_results as $match) {

    $blue_team_acronym = $match["blue-team"]["acronym"];
    $red_team_acronym = $match["red-team"]["acronym"];

    if(!is_null($match["blue-team"]["result"]) // Because the game has not been
                                               // played yet
        && !resultAlreadyInDB($conn, $match, $week_number)){

      // Get teams Id for DB persistance
      $blue_team_id = getTeamIdByAcronym($conn, $blue_team_acronym);
      $red_team_id = getTeamIdByAcronym($conn, $red_team_acronym);
      $winner_id = getTeamIdByAcronym($conn, $match["winner"]);

      // Persist into DB
      insertResult($conn, $blue_team_id, $red_team_id, $winner_id, $week_number);
      echo "Match $blue_team_acronym Vs. $red_team_acronym added\n";
    }

    echo "Match $blue_team_acronym Vs. $red_team_acronym discarded\n";
}

function getMatchesData($simple_html_dom_object)
{
  $matches_results = [];

  foreach($simple_html_dom_object->find('.schedule-item') as $game){

      /**
       * The blue and red team have diferent array position to retrieve the
       * information (0 and 1), this is because of the DOM structure of the
       * LCS web.
       */
      $blue_team = getTeamData($game->find(".blue-team", 0));
      $red_team = getTeamData($game->find(".red-team", 1));
      $winner = $blue_team["result"] == "VICTORY"
                  ? $blue_team["acronym"]
                  : $red_team["acronym"];

      // Prepare information
      $matches_results[] = array(
          "blue-team" => $blue_team,
          "red-team" => $red_team,
          "winner" => $winner
      );
  }

  return $matches_results;
}

/**
 * Returns an array with the data of a given team.
 * @param $team_object (simple_html_dom_parser object containing a single team)
 */
function getTeamData($team_object){

  // Generic team data
  $team_name = $team_object->find(".team-name", 0)->plaintext;
  $team_acronym = $team_object->find(".team-acronym", 0)->plaintext;


   // Get if the team won or lose
   $team_result = $team_object->find(".defeat", 0);

   if(is_null($team_result)){
    $team_result = $team_object->find(".victory", 0);
   }

   $team_result = $team_result->plaintext;


   $team_match_data = array(
    "name" => $team_name,
    "acronym" => $team_acronym,
    "result" => $team_result
   );

   return $team_match_data;
}

/**
 * Checks if the combination of match and week number is already in the DB.
 * @param $conn PDO conection
 * @param $matchResult Array with data of a match retrieved from `getMatchesData`
 */
function resultAlreadyInDB($conn, $matchResult, $weekNumber)
{
  $stmt = $conn->prepare("
    SELECT *
      FROM lcs_results r
      JOIN lcs_teams bt ON bt.id = r.team_blue
      JOIN lcs_teams rt ON rt.id = r.team_red
      JOIN lcs_teams w ON w.id = r.winner
    WHERE bt.acronym = :blueTeam
      AND rt.acronym = :redTeam
      and week = :week
  ");

  $stmt->bindParam(":blueTeam", $matchResult["blue-team"]["acronym"]);
  $stmt->bindParam(":redTeam", $matchResult["red-team"]["acronym"]);
  $stmt->bindParam(":week", $weekNumber);

  $stmt->execute();

  $results = $stmt->fetchAll();

  return !empty($results);
}

/**
 * Gets the team ID from an acronym
 * @param $conn PDO conection
 * @param $acronym String representing the short name for a team
 */
function getTeamIdByAcronym($conn, $acronym){
  $stmt = $conn->prepare("
    SELECT id
      FROM lcs_teams
      WHERE acronym = :acronym
  ");

  $stmt->bindParam(":acronym", $acronym);
  $stmt->execute();

  $result = intval($stmt->fetchAll()[0]["id"]);

  return $result;
}

/**
 * Inserts into the DB the match
 * @param $conn PDO conection
 * @param $blue_team_id
 * @param $red_team_id
 * @param $winner_id
 * @param $week_number
 */
function insertResult(
  $conn, $blue_team_id, $red_team_id, $winner_id, $week_number
){
    $stmt = $conn->prepare("
      INSERT INTO lcs_results (team_blue, team_red, winner, week)
      values (:blue_team_id, :red_team_id, :winner_id, :week_number)
    ");

    $stmt->bindParam(":blue_team_id", $blue_team_id);
    $stmt->bindParam(":red_team_id", $red_team_id);
    $stmt->bindParam(":winner_id", $winner_id);
    $stmt->bindParam(":week_number", $week_number);

    $stmt->execute();
}
