<?php

$time_start = microtime(true); 

// FINNER LOKALHISTORIE

// Declare variables

$tittel = '';
$forfatter = '';
$bokhyllahtml = '';
$bokhyllatreff = '';
$bildertreff = '';
$bilderhtml = '';
$stedsinfo = '';
$aviserhtml = '';
$dc = '';


// turn on for debug

/*
ini_set('display_startup_errors',1);
ini_set('display_errors',1);
error_reporting(-1);
*/

// INNSTILLINGER
$makstreff = 500; // Her kliner vi til i fullskjermsøk
$bokhyllaft = 'false'; // fulltekstsøk i Bokhylla? (gir myriader av treff)
$bilderft = 'true'; // fulltekstsøk i bilder? Kan like gjerne stå på
$aviserft = 'true'; // fulltekstsøk i aviser? Kan like gjerne stå på

// vi trenger funksjoner
require_once ('includes/functions.php');

// Get Search
$search_string = urlencode(stripslashes(strip_tags($_REQUEST['query'])));
$search_string = str_replace ("\"", "%22" , $search_string);
$search_string = str_replace (" ", "%20" , $search_string);

// Define Output HTML Formatting of single item


$singlehtml = "<tr>\n";
$singlehtml .= "<td><a href=\"urlString\" target=\"_blank\">titleString</a></td>\n";
$singlehtml .= "<td>materialString</td>\n";
$singlehtml .= "<td>authorString</td>\n";
$singlehtml .= "<td>publishedString</td>\n";
$singlehtml .= "<td>yearString</td>\n";
$singlehtml .= "<td>kildeString</td>\n";
$singlehtml .= "</tr>\n\n";


$timenow = microtime(true);
$execution_time = ($timenow - $time_start);
//echo "<h2>Begynner å søke nå. Tidsforbruk: " . $execution_time . "</h2>";


// Søke i bokhylla emne lokalhistorie

$rawurl = "http://www.nb.no/services/search/v2/search?q=<!QUERY!>&fq=contentClasses:(bokhylla%20OR%20public)&fq=subject:lokalhistorie&fq=digital:Ja&itemsPerPage=" . $makstreff . "&ft=" . $bokhyllaft;

//$rawurl = "http://www.nb.no/services/search/v2/search?q=<!QUERY!>&fq=mediatype:(Bøker)&fq=contentClasses:(bokhylla%20OR%20public)&fq=subject:lokalhistorie&fq=digital:Ja&itemsPerPage=" . $makstreff . "&ft=" . $bokhyllaft;

$rawurl = str_replace ("<!QUERY!>" , $search_string , $rawurl); // sette inn søketerm

// LASTE TREFFLISTE SOM XML
$xml = get_content($rawurl);
$xmldata = simplexml_load_string($xml);

//echo "<pre>";
//print_r ($xmldata);
//echo "</pre>";


// FINNE ANTALL TREFF
$bokhyllaantalltreff = substr(stristr($xmldata->subtitle, " of ") , 4);

// ... SÅ HVERT ENKELT TREFF
	$teller = 0;
	foreach ($xmldata->entry as $entry) {

		if ($teller < $makstreff) {

			// METADATA SOM XML FOR DETTE TREFFET
			$childxml = ($entry->link[0]->attributes()->href); // Dette er XML med metadata
			$xmlfile = get_content($childxml);
			$childxmldata = simplexml_load_string($xmlfile);
			$namespaces = $entry->getNameSpaces(true);
			$nb = $entry->children($namespaces['nb']);

//echo "<pre>";
//print_r ($childxmldata);
//echo "</pre>";
	
			$bokhyllatreff[$teller]['tittel'] = $childxmldata->titleInfo->title;
			$bokhyllatreff[$teller]['forfatter'] = $nb->namecreator;
	
			// BOKOMSLAG, SE http://www-sul.stanford.edu/iiif/image-api/1.1/#parameters
			if (stristr($nb->urn , ";")) {
				$tempura = explode (";" , $nb->urn);
				$urn = trim($tempura[1]); // vi tar nummer 2 
			} else {
				$urn = $nb->urn[0];
			}
			if ($urn != "") {
				//$bokhyllatreff[$teller]['bokomslag'] = "http://www.nb.no/services/image/resolver?url_ver=geneza&urn=" . $urn . "_C1&maxLevel=6&level=1&col=0&row=0&resX=6000&resY=6000&tileWidth=2048&tileHeight=2048";
				$bokhyllatreff[$teller]['bokomslag'] = "http://www.nb.no/services/iiif/api/" . $urn . "_C1/full/160,/0/native.jpg";
			} else {
				$bokhyllatreff[$teller]['bokomslag'] = $generiskbokomslag; // DEFAULTOMSLAG
			}
	
			$bokhyllatreff[$teller]['url'] = "http://urn.nb.no/" . $urn;
			$bokhyllatreff[$teller]['kilde'] = "bokhylla.no";

			// Publiseringssted kan være array

			if (!empty($childxmldata->originInfo->place[1]->placeTerm)) {
				$bokhyllatreff[$teller]['utgitt'] = $childxmldata->originInfo->place[1]->placeTerm . " : " . $childxmldata->originInfo->publisher;
			} else {
				$bokhyllatreff[$teller]['utgitt'] = $childxmldata->originInfo->place[0]->placeTerm . " : " . $childxmldata->originInfo->publisher;
			}

			// Publiseringsår kan være array

			if (!empty($childxmldata->originInfo->dateIssued[1])) {
				$bokhyllatreff[$teller]['utgittaar'] = $childxmldata->originInfo->dateIssued[1];
			} else {
				$bokhyllatreff[$teller]['utgittaar'] = $childxmldata->originInfo->dateIssued[0];
			}

			$teller++;
		}
	} // SLUTT PÅ HVERT ENKELT TREFF

foreach ($bokhyllatreff as $singeltreff) {
        $bokhyllatreffhtml = str_replace ("urlString" , $singeltreff['url'] , $singlehtml);
        $bokhyllatreffhtml = str_replace ("titleString" , trunc($singeltreff['tittel'], 12) , $bokhyllatreffhtml);
        $bokhyllatreffhtml = str_replace ("authorString" , trunc($singeltreff['forfatter'], 5) , $bokhyllatreffhtml);
		$bokhyllatreffhtml = str_replace ("publishedString" , $singeltreff['utgitt'] , $bokhyllatreffhtml);
		$bokhyllatreffhtml = str_replace ("yearString" , $singeltreff['utgittaar'] , $bokhyllatreffhtml);
		$bokhyllatreffhtml = str_replace ("materialString" , "Bok" , $bokhyllatreffhtml);
		$bokhyllatreffhtml = str_replace ("classString" , "bokhyllatreff" , $bokhyllatreffhtml);
		$bokhyllatreffhtml = str_replace ("kildeString" , $singeltreff['kilde'] , $bokhyllatreffhtml);
        $bokhyllahtml .= $bokhyllatreffhtml;
}




$timenow = microtime(true);
$execution_time = ($timenow - $time_start);
//echo "<h2>Ferdig med å søke i bokhylla. Tidsforbruk: " . $execution_time . "</h2>";


// Søke i NBs bilder

$rawurl = "http://www.nb.no/services/search/v2/search?q=<!QUERY!>&fq=mediatype:(Bilder)&fq=contentClasses:public&fq=digital:Ja&itemsPerPage=" . $makstreff . "&ft=" . $bilderft;

$rawurl = str_replace ("<!QUERY!>" , $search_string , $rawurl); // sette inn søketerm

// LASTE TREFFLISTE SOM XML
$xmlfile = get_content($rawurl);
$xmldata = simplexml_load_string($xmlfile);

// FINNE ANTALL TREFF
$bilderantalltreff = substr(stristr($xmldata->subtitle, " of ") , 4);

// ... SÅ HVERT ENKELT TREFF
	$teller = 0;
	foreach ($xmldata->entry as $entry) {

//echo "<pre>";
//print_r ($entry);
//echo "</pre>";


		if ($teller < $makstreff) {

			// METADATA SOM XML FOR DETTE TREFFET
			$childxml = ($entry->link[0]->attributes()->href); // Dette er XML med metadata
			$xmlfile = get_content($childxml);
			$childxmldata = simplexml_load_string($xmlfile);

			$namespaces = $entry->getNameSpaces(true);
			$nb = $entry->children($namespaces['nb']);

			// FINNE URN			
			if (stristr($nb->urn , ";")) {
				$tempura = explode (";" , $nb->urn);
				$urn = trim($tempura[1]); // vi tar nummer 2 
			} else {
				$urn = $nb->urn[0];
			}
			
			$bildertreff[$teller]['url'] = "http://urn.nb.no/" . $urn;
			$bildertreff[$teller]['opphav'] = $nb->mainentry;
			$bildertreff[$teller]['eier'] = $childxmldata->recordInfo->recordContentSource;

			$bildertreff[$teller]['beskrivelse'] = '<img class="lokalhistthumb" src="' . $childxmldata->location->url[2] . '" alt="miniatyr" />';
			$bildertreff[$teller]['beskrivelse'] .= str_ireplace ("prot: ", "" , $entry->summary); // Fjerne "Prot: " i starten
			$bildertreff[$teller]['aar'] .= $nb->year;
					
			$bildertreff[$teller]['kilde'] = "NB - bilder";
			
			$teller++;
		}
	} // SLUTT PÅ HVERT ENKELT TREFF

foreach ($bildertreff as $singeltreff) {
        $bildertreffhtml = str_replace ("urlString" , $singeltreff['url'] , $singlehtml);
        $bildertreffhtml = str_replace ("titleString" , $singeltreff['beskrivelse'] , $bildertreffhtml);
        $bildertreffhtml = str_replace ("authorString" , $singeltreff['opphav'] , $bildertreffhtml);
        $bildertreffhtml = str_replace ("publishedString" , $singeltreff['eier'] , $bildertreffhtml);
        $bildertreffhtml = str_replace ("yearString" , $singeltreff['aar'] , $bildertreffhtml);
        $bildertreffhtml = str_replace ("materialString" , "Fotografi" , $bildertreffhtml);
		$bildertreffhtml = str_replace ("kildeString" , $singeltreff['kilde'] , $bildertreffhtml);
		$bildertreffhtml = str_replace ("classString" , "bildertreff" , $bildertreffhtml);
       
        $bilderhtml .= $bildertreffhtml;
}


$timenow = microtime(true);
$execution_time = ($timenow - $time_start);
//echo "<h2>Ferdig med å søke i NBs bilder. Tidsforbruk: " . $execution_time . "</h2>";

// Søke i NBs aviser

$rawurl = "http://www.nb.no/services/search/v2/search?q=<!QUERY!>&fq=mediatype:(Aviser)&fq=contentClasses:(restricted%20OR%20public)&fq=digital:Ja&itemsPerPage=" . $makstreff . "&ft=" . $aviserft;

$rawurl = str_replace ("<!QUERY!>" , $search_string , $rawurl); // sette inn søketerm

// LASTE TREFFLISTE SOM XML
$xmlfile = get_content($rawurl);
$xmldata = simplexml_load_string($xmlfile);

// FINNE ANTALL TREFF
$aviserantalltreff = substr(stristr($xmldata->subtitle, " of ") , 4);

// ... SÅ HVERT ENKELT TREFF
	$teller = 0;
	foreach ($xmldata->entry as $entry) {
		if ($teller < $makstreff) {
			// METADATA SOM XML FOR DETTE TREFFET
			$childxml = ($entry->link[0]->attributes()->href); // Dette er XML med metadata
			$xmlfile = get_content($childxml);
			$childxmldata = simplexml_load_string($xmlfile);
			$namespaces = $entry->getNameSpaces(true);
			$nb = $entry->children($namespaces['nb']);

/*
			$structxml = ($entry->link[3]->attributes()->href); // Dette er XML med struct, inkl. omslagsbilde
			$xmlfile = get_content($structxml);
			$structxmldata = simplexml_load_string($xmlfile);			
*/
			// FINNE URN			
			if (stristr($nb->urn , ";")) {
				$tempura = explode (";" , $nb->urn);
				$urn = trim($tempura[1]); // vi tar nummer 2 
			} else {
				$urn = $nb->urn[0];
			}

			// FINNE OMSLAG DROPPER VI, TAR FOR LANG TID
/*
			foreach ($structxmldata->div as $utgave) {
				if ($utgave->attributes()->ORDER == "1") { // Hvis første side
				$sideid = $utgave->resource->attributes("xlink", TRUE)->href; //  FÅR MED FILENDELSE!
				}
			}			
			$avisertreff[$teller]['bokomslag'] = "http://www.nb.no/services/image/resolver?url_ver=geneza&urn=" . $sideid . "&maxLevel=6&level=1&col=0&row=0&resX=6000&resY=6000&tileWidth=2048&tileHeight=2048";
*/			
			$avisertreff[$teller]['url'] = "http://urn.nb.no/" . $urn;
			//$avisertreff[$teller]['tittel'] = '<img class="lokalhistthumb" src="' . $avisertreff[$teller]['bokomslag'] . '" alt="Miniatyrbilde" />';
			$avisertreff[$teller]['tittel'] = $childxmldata->titleInfo->title;
			$avisertreff[$teller]['aar'] = $nb->year;
			$avisertreff[$teller]['opphav'] = '';
			$avisertreff[$teller]['published'] = substr($childxmldata->titleInfo->title , 0 , -10);
			
			$avisertreff[$teller]['kilde'] = "NB - aviser";
			
			$teller++;
		}
	} // SLUTT PÅ HVERT ENKELT TREFF

foreach ($avisertreff as $singeltreff) {
        $avisertreffhtml = str_replace ("urlString" , $singeltreff['url'] , $singlehtml);
        $avisertreffhtml = str_replace ("titleString" , trunc($singeltreff['tittel'], 12) , $avisertreffhtml);
        $avisertreffhtml = str_replace ("authorString" , $singeltreff['opphav'] , $avisertreffhtml);
        $avisertreffhtml = str_replace ("publishedString" , $singeltreff['published'] , $avisertreffhtml);
		$avisertreffhtml = str_replace ("materialString" , "Avis" , $avisertreffhtml);
		$avisertreffhtml = str_replace ("yearString" , $singeltreff['aar'] , $avisertreffhtml);
		$avisertreffhtml = str_replace ("classString" , "avisertreff" , $avisertreffhtml);
		$avisertreffhtml = str_replace ("kildeString" , $singeltreff['kilde'] , $avisertreffhtml);
       
        $aviserhtml .= $avisertreffhtml;
}



$timenow = microtime(true);
$execution_time = ($timenow - $time_start);
//echo "<h2>Ferdig med å søke i NBs aviser. Tidsforbruk: " . $execution_time . "</h2>";

// Søke i norvegiana.no

//$rawurl = "http://kulturnett2.delving.org/api/search?query=<!QUERY!>%20AND%20abm_county_text%3Abuskerud%20AND%20delving_hasDigitalObject%3Atrue";
$rawurl = "http://kulturnett2.delving.org/api/search?query=<!QUERY!>%20AND%20delving_hasDigitalObject%3Atrue";

$rawurl = str_replace ("<!QUERY!>" , $search_string , $rawurl); // sette inn søketerm

// LASTE TREFFLISTE SOM XML
$xmlfile = get_content($rawurl);
$xmldata = simplexml_load_string($xmlfile);

//echo "<pre>";
//print_r ($xmldata);
//echo "</pre>";

// FINNE ANTALL TREFF
$norvegianaantalltreff = $xmldata->query->attributes()->numFound;

// ... SÅ HVERT ENKELT TREFF
	$teller = 0;
	foreach ($xmldata->items->item as $entry) {
		if ($teller < $makstreff) {
			
			// DATO
			if (substr($dc->date, 0, 6) == "start=") { // dilledato
				$rawdate = substr ($dc->date, 6, 10);
				$mindato = substr ($rawdate, 8, 2) . "." . substr ($rawdate, 5, 2) . "." . substr ($rawdate , 0, 4);
				if (substr($mindato, 0, 6) == "01.01.") { // Første i første er som regel bare tull
					$mindato = substr($mindato, 6, 4);
				}
			} else { // ikke tulledato
			$mindato = $dc->date;
			}
			
			unset ($stedsinfo);
			$delving = $entry->fields->children('delving', true);
			$dc = $entry->fields->children('dc', true);
			$abm = $entry->fields->children('abm', true);
			
			if ($abm->estateNr != "") {
				$stedsinfo[] = $abm->estateName . " " . $abm->estateNr;
			}
			if ($abm->namedPlace != "") {
				$stedsinfo[] = $abm->namedPlace;
			}
			if ($abm->municipality != "") {
				$stedsinfo[] = $abm->municipality;
			}
			if ($abm->county != "") {
				$stedsinfo[] = $abm->county;
			}
			$stedet = @implode (", " , $stedsinfo);
			if (trim($stedet) != "") {
				$stedet = "<br><i>Sted: </i>" . $stedet;
			}

			$norvegianatreff[$teller]['url'] = $delving->landingPage;
			$norvegianatreff[$teller]['beskrivelse'] = htmlspecialchars(strip_tags($delving->description));
			$norvegianatreff[$teller]['beskrivelse'] .= $stedet;
			if ($mindato != "") {
				$norvegianatreff[$teller]['beskrivelse'] .= "<br /><i>Datering: </i>" . $mindato;
			}
			$norvegianatreff[$teller]['tittel'] = htmlspecialchars($delving->creator);
			$norvegianatreff[$teller]['bokomslag'] = $delving->thumbnail;
			
			$norvegianatreff[$teller]['tittel'] = '<img class="lokalhistthumb" src="' . $delving->thumbnail . '" alt="Miniatyrbilde" />';
			if (is_array($delving->description)) {
				$norvegianatreff[$teller]['tittel'] = implode (" " , $delving->description);
			} else {
				$norvegianatreff[$teller]['tittel'] = $delving->description;
			}

			$norvegianatreff[$teller]['kilde'] = 'Norvegiana';

			$teller++;
			
		}
	} // SLUTT PÅ HVERT ENKELT TREFF

foreach ($norvegianatreff as $singeltreff) {
        $norvegianatreffhtml = str_replace ("urlString" , $singeltreff['url'] , $singlehtml);
        $norvegianatreffhtml = str_replace ("titleString" , $singeltreff['tittel'] , $norvegianatreffhtml);
        $norvegianatreffhtml = str_replace ("authorString" , $delving->creator , $norvegianatreffhtml);
        $norvegianatreffhtml = str_replace ("publishedString" , $delving->collection , $norvegianatreffhtml);
        $norvegianatreffhtml = str_replace ("materialString" , "Fotografi" , $norvegianatreffhtml);
        $norvegianatreffhtml = str_replace ("yearString" , $mindato , $norvegianatreffhtml);
		$norvegianatreffhtml = str_replace ("classString" , "norvegianatreff" , $norvegianatreffhtml);
		$norvegianatreffhtml = str_replace ("kildeString" , $singeltreff['kilde'] , $norvegianatreffhtml);
       
        $norvegianahtml .= $norvegianatreffhtml;
}



$timenow = microtime(true);
$execution_time = ($timenow - $time_start);
//echo "<h2>Ferdig med å søke i Norvegiana. Tidsforbruk: " . $execution_time . "</h2>";


// FERDIG MED Å SØKE - SKRIVE UT RESULTATER

?>

<!DOCTYPE html>
<html lang="no">
  <head>
    <meta charset="utf-8">
    <title>Resultater for s&oslash;k etter '<?php echo stripslashes(strip_tags($_REQUEST['query']));?>'</title>

<style>
body {
	background:#fff url(g/tablesort/bg.gif) repeat-x;
	color:#091f30;
	font-family: "HelveticaNeue-Light", "Helvetica Neue Light", "Helvetica Neue", Helvetica, Arial, "Lucida Grande", sans-serif;
	font-weight: 300;
}

a {
	text-decoration: none;
	}

.sortable {
	width: 100%;
	border-left:1px solid #c6d5e1;
	border-top:1px solid #c6d5e1;
	border-bottom:none;
	margin:0 auto 15px;
	}

.sortable th {
	background-color: #000;
	text-align:left;
	color:#fff;
}

.sortable th h3 {
	font-size:18px; 
	padding:6px 8px 8px;
	margin: 0;
}

.sortable td {
	padding:4px 6px 6px;
	border-bottom:1px solid #c6d5e1;
	border-right:1px solid #c6d5e1;
}

.sortable .head h3 {
	background:url(g/tablesort/sort.gif) 7px center no-repeat;
	cursor:pointer;
	padding-left:18px;
}

.sortable .desc h3 {
	background:url(g/tablesort/desc.gif) 7px center no-repeat;
	cursor:pointer;
	padding-left:18px
}

.sortable .asc h3 {
	background:url(g/tablesort/asc.gif) 7px center no-repeat;
	cursor:pointer;
	padding-left:18px
}

.sortable .head:hover, .sortable .desc:hover, .sortable .asc:hover {
	color:#fff;
	}

.sortable .evenrow td {
	background:#fff;
	}

.sortable .oddrow td {
	background:#ecf2f6;
	}

.sortable td.evenselected {
	background:#ecf2f6;
	}

.sortable td.oddselected {
	background:#dce6ee;
	}

#controls {
	width: 100%;
	margin:0 auto; 
	height:20px
}

#perpage {
	float:left;
	width:20%;
}

#perpage select {
	float:left;
}

#perpage span {
	float:left;
	margin:2px 0 0 5px;
}

#navigation {
	float:left;
	width: 60%;
	text-align:center;
}

#navigation img {
	cursor:pointer;
}

#text {
	float:left;
	width: 20%;
	text-align:right;
	margin-top:2px;
}

img.lokalhistthumb {
	float: left;
	margin: 5px;
	}

</style>

  </head>


  <body>
<div class="loader"></div>

	<div id="controls">
		<div id="perpage">
			<select onchange="sorter.size(this.value)">
				<option value="50" selected="selected">50</option>
				<option value="100">100</option>
				<option value="250">250</option>
			</select>
			<span>Treff per side</span>
		</div>
		<div id="navigation">
			<img src="g/tablesort/first.gif" width="16" height="16" alt="First Page" onclick="sorter.move(-1,true)" />
			<img src="g/tablesort/previous.gif" width="16" height="16" alt="First Page" onclick="sorter.move(-1)" />
			<img src="g/tablesort/next.gif" width="16" height="16" alt="First Page" onclick="sorter.move(1)" />
			<img src="g/tablesort/last.gif" width="16" height="16" alt="Last Page" onclick="sorter.move(1,true)" />
		</div>
		<div id="text">Viser side <span id="currentpage"></span> av <span id="pagelimit"></span></div>
	</div>

<table id="table" class="sortable">
<thead><tr><th style="width: 30%;"><h3>Tittel</h3></th><th style="width: 10%;"><h3>Materialtype</h3></th><th style="width: 20%;"><h3>Opphav</h3></th><th style="width: 25%;"><h3>Utgitt</h3></th><th style="width: 5%;"><h3>År</h3></th><th style="width: 10%;"><h3>Kilde</h3></th></tr></thead>
<tbody>
<?php echo $bokhyllahtml;?>
<?php echo $bilderhtml;?>
<?php echo $aviserhtml;?>
<?php echo $norvegianahtml;?>

</tbody>
</table>

	<div id="controls">
		<div id="perpage">
			<select onchange="sorter.size(this.value)">
				<option value="50" selected="selected">50</option>
				<option value="100">100</option>
				<option value="250">250</option>
			</select>
			<span>Treff per side</span>
		</div>
		<div id="navigation">
			<img src="g/tablesort/first.gif" width="16" height="16" alt="Første side" onclick="sorter.move(-1,true)" />
			<img src="g/tablesort/previous.gif" width="16" height="16" alt="Forrige side" onclick="sorter.move(-1)" />
			<img src="g/tablesort/next.gif" width="16" height="16" alt="Neste side" onclick="sorter.move(1)" />
			<img src="g/tablesort/last.gif" width="16" height="16" alt="Siste side" onclick="sorter.move(1,true)" />
		</div>
	</div>

<script src="js/table.js"></script>
<script type="text/javascript">
var sorter = new TINY.table.sorter('sorter');
sorter.head = 'head'; //header class name
sorter.asc = 'asc'; //ascending header class name
sorter.desc = 'desc'; //descending header class name
sorter.even = 'evenrow'; //even row class name
sorter.odd = 'oddrow'; //odd row class name
sorter.evensel = 'evenselected'; //selected column even class
sorter.oddsel = 'oddselected'; //selected column odd class
sorter.paginate = true; //toggle for pagination logic
sorter.pagesize = 50; //toggle for pagination logic
sorter.currentid = 'currentpage'; //current page id
sorter.limitid = 'pagelimit'; //page limit id
sorter.init('table',0);

</script>



</body>
</html>
