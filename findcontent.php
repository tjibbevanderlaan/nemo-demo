<?php
/**
 * Findcontent
 * This PHP-scripts searches for all topics inside the topics directory.
 * Furthermore, it creates a list of links to the specific topics, with
 * help of the viewer.php script
 */

$localDir = './topics';
$filename = "json";
$dirDepth = 5; // Depth of the the directory, base on struct.: Vak -> Modules(CC) -> Onderwerpen -> _web -> .json

function getDirTree($dir) { 
    $result = array(); 
    $cdir = scandir($dir); 
    foreach ($cdir as $key => $value) { 
        if (!in_array($value,array(".",".."))) {
            if (is_dir($dir . DIRECTORY_SEPARATOR . $value)) {
                $result[$value] = getDirTree($dir . DIRECTORY_SEPARATOR . $value); 
            } else {
                $result[] = $value; 
            }
        }
    }
     
   return $result; 
}

$dirArray = getDirTree($localDir);
$content = ''; // a container for the menu (will contain HTML data)

// Loop over each dir
foreach ($dirArray as $topic => $topicFiles) {

    if (file_exists($localDir.'/'.$topic.'/_web/properties.json')) {

        // Get JSON file and decode the file into an php-array
        $json = file_get_contents($localDir.'/'.$topic.'/_web/properties.json');
        $jsonArray = json_decode($json, TRUE);

        // Check if topic has a title and content. No? Skip this file
        $topictitle = $jsonArray["title"];
        $animatedcontent = $jsonArray["content"];
        // if(!isset($topictitle) || !isset($animatedcontent)) continue;

        // Create a properites array for the topic
        $topicproperties = array(
            "title" => $topictitle,
            "root" => $localDir.'/'.$topic.'/_web'
        );

        // Serialize data, for inserting into link
        $serialized_tp = base64_encode(serialize($topicproperties));
        $serialized_ac = base64_encode(serialize($animatedcontent));

        // Create a topic description
        $topicdesc = "";
        if(isset($jsonArray["abstract"])) {
            $topicdesc = $jsonArray["abstract"];
        } else {
            $topicdesc = 'Behandelt de onderwerpen';
            $first = false;
            foreach ($animatedcontent as $ac) {
                $name = strtolower($ac["name"]);
                if(!$first) {
                    $topicdesc .= ' '.$name;
                    $first = true;
                } elseif(end($animatedcontent) !== $ac) {
                    $topicdesc .= ', '.$name;
                } else {
                    $topicdesc .= ' en '.$name.'.';
                }
            }
        }

        // Insert all data into the content container
        $content .= '<a class="topiclink" href="/demo/viewer.php?tp='.$serialized_tp.'&ac='.$serialized_ac.'" title="'.$topictitle.'"><h2>'.$topictitle.'</h2><p>'.$topicdesc.'</p></a>';
    }
}

if($content !== '') {
    echo $content;
} else {
    header("HTTP/1.0 404 Not Found");
}
?>