<?php

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
$makstreff = (int)$_REQUEST['makstreff']; // hvor mange treff henter vi maks fra hvert sted? Definert i shortcode
$bokhyllaft = 'false'; // fulltekstsøk i Bokhylla? (gir myriader av treff)
$bilderft = 'true'; // fulltekstsøk i bilder? Kan like gjerne stå på
$aviserft = 'true'; // fulltekstsøk i aviser? Kan like gjerne stå på

// vi trenger funksjoner
require_once ('includes/functions.php');

// Get Search
$search_string = urlencode(stripslashes(strip_tags($_POST['query'])));
$search_string = str_replace ("\"", "%22" , $search_string);
$search_string = str_replace (" ", "%20" , $search_string);

// Define Output HTML Formatting of single item

$singlehtml = '';
$singlehtml .= "<div class=\"lokalhistorieresult classString\">\n";
$singlehtml .= "<a class=\"lokalhistorieresultlink\" href=\"urlString\" target=\"_blank\">";
$singlehtml .= "<img class=\"lokalhistorieresultcover\" src=\"omslagString\" alt=\"titleString - descriptionString\" />\n";
$singlehtml .= "<b>titleString</b>\n";
$singlehtml .= "</a>\n";
$singlehtml .= "<br /><span class=\"lokalhistorieresultdescription\">descriptionString</span><br />\n";
$singlehtml .= '<a target="_blank" href="https://twitter.com/intent/tweet?url=twitterurlString&via=bibvenn&text=twitterdescriptionString&related=bibvenn,sundaune&lang=no"><img style="width: 20px; height: 20px;" src="' . $litentwitt . '" alt="Twitter-deling" /></a>&nbsp;';
$singlehtml .= "<a target=\"_self\" href=\"javascript:fbShare('urlString', 700, 350)\"><img style=\"width: 50px; height: 21px;\" src=\"" . $litenface . "\" alt=\"Facebook-deling\" /></a>";
$singlehtml .= "<br style=\"clear: both;\">";
$singlehtml .= "</div>\n\n";


// Søke i bokhylla emne lokalhistorie

//$rawurl = "http://www.nb.no/services/search/v2/search?q=<!QUERY!>&fq=digital:Ja&fq=mediatype:(Bøker)&fq=contentClasses:<!MATERIAL!>&itemsPerPage=" . $makstreff . "&ft=" . $bokhyllaft;

$rawurl = "http://www.nb.no/services/search/v2/search?q=<!QUERY!>&fq=mediatype:(Bøker)&fq=contentClasses:(bokhylla%20OR%20public)&fq=subject:lokalhistorie&fq=digital:Ja&itemsPerPage=" . $makstreff . "&ft=" . $bokhyllaft;

// HER KOMMER EV. SJEKKBOKSER INN I SPILL, PARAMETER SENDES FRA JAVASCRIPTET

/*
switch ($_REQUEST['format']) {
	case "NaN":
		//echo "Velg en materialtype!";
		$rawurl = str_replace ("<!MATERIAL!>" , "(DUMMY)" , $rawurl);
		break;
	case "undefined2":
		$rawurl = str_replace ("<!MATERIAL!>" , "(epub)" , $rawurl);
		break;
	case "1undefined":
		$rawurl = str_replace ("<!MATERIAL!>" , "(public)" , $rawurl);
		break;
	case "12";
		$rawurl = str_replace ("<!MATERIAL!>" , "(public OR epub)" , $rawurl);
		break;
}
*/

$rawurl = str_replace ("<!QUERY!>" , $search_string , $rawurl); // sette inn søketerm

// LASTE TREFFLISTE SOM XML
$xml = get_content($rawurl);
$xmldata = simplexml_load_string($xml);

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
			$bokhyllatreff[$teller]['kilde'] = "Nasjonalbiblioteket";
			$teller++;
		}
	} // SLUTT PÅ HVERT ENKELT TREFF

foreach ($bokhyllatreff as $singeltreff) {
		$bokhyllatreffhtml = str_replace ("twitterurlString" , urlencode($singeltreff['url']) , $singlehtml);
		$bokhyllatreffhtml = str_replace ("twitterdescriptionString" , htmlspecialchars($tittel). htmlspecialchars(" (".$forfatter.")"), $bokhyllatreffhtml);		
        $bokhyllatreffhtml = str_replace ("urlString" , $singeltreff['url'] , $bokhyllatreffhtml);
        $bokhyllatreffhtml = str_replace ("titleString" , trunc($singeltreff['tittel'], 12) , $bokhyllatreffhtml);
        $bokhyllatreffhtml = str_replace ("descriptionString" , trunc($singeltreff['forfatter'], 5) , $bokhyllatreffhtml);
		$bokhyllatreffhtml = str_replace ("omslagString" , $singeltreff['bokomslag'] , $bokhyllatreffhtml);
		$bokhyllatreffhtml = str_replace ("classString" , "bokhyllatreff" , $bokhyllatreffhtml);
       
        $bokhyllahtml .= $bokhyllatreffhtml;
}


// Søke i NBs bilder

$rawurl = "http://www.nb.no/services/search/v2/search?q=<!QUERY!>&fq=mediatype:Bilder&fq=contentClasses:public&fq=digital:Ja&itemsPerPage=" . $makstreff . "&ft=" . $bilderft;

$rawurl = str_replace ("<!QUERY!>" , $search_string , $rawurl); // sette inn søketerm

// LASTE TREFFLISTE SOM XML
$xmlfile = get_content($rawurl);
$xmldata = simplexml_load_string($xmlfile);

// FINNE ANTALL TREFF
$bilderantalltreff = substr(stristr($xmldata->subtitle, " of ") , 4);

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

			// FINNE URN			
			if (stristr($nb->urn , ";")) {
				$tempura = explode (";" , $nb->urn);
				$urn = trim($tempura[1]); // vi tar nummer 2 
			} else {
				$urn = $nb->urn[0];
			}
			
			$bildertreff[$teller]['bokomslag'] = "http://www.nb.no/gallerinor/hent_bilde.php?id=" . $childxmldata->recordInfo->recordIdentifier . "&size=1";
	
			if (stristr($nb->contentclasses , "jp2")) {
				$bildertreff[$teller]['bokomslag'] = "http://www.nb.no/services/image/resolver?url_ver=geneza&urn=" . $urn . "&maxLevel=6&level=2&col=0&row=0&resX=6000&resY=6000&tileWidth=2048&tileHeight=2048";
			}
			
			$bildertreff[$teller]['url'] = "http://urn.nb.no/" . $urn;
			$bildertreff[$teller]['tittel'] = $nb->mainentry;
			if (trim($nb->year) != '') {
				$bildertreff[$teller]['tittel'] .= " (" . $nb->year . ")";
			}
			$bildertreff[$teller]['beskrivelse'] = str_ireplace ("prot: ", "" , $entry->summary); // Fjerne "Prot: " i starten
			
			$bildertreff[$teller]['kilde'] = "Nasjonalbiblioteket - bilder";
			
			$teller++;
		}
	} // SLUTT PÅ HVERT ENKELT TREFF

foreach ($bildertreff as $singeltreff) {
		$bildertreffhtml = str_replace ("twitterurlString" , urlencode($singeltreff['url']) , $singlehtml);
		$bildertreffhtml = str_replace ("twitterdescriptionString" , htmlspecialchars($tittel). htmlspecialchars(" (".$singeltreff['beskrivelse'].")"), $bildertreffhtml);		
        $bildertreffhtml = str_replace ("urlString" , $singeltreff['url'] , $bildertreffhtml);
        $bildertreffhtml = str_replace ("titleString" , trunc($singeltreff['tittel'], 12) , $bildertreffhtml);
        $bildertreffhtml = str_replace ("descriptionString" , trunc($singeltreff['beskrivelse'], 20) , $bildertreffhtml);
		$bildertreffhtml = str_replace ("omslagString" , $singeltreff['bokomslag'] , $bildertreffhtml);
		$bildertreffhtml = str_replace ("classString" , "bildertreff" , $bildertreffhtml);
       
        $bilderhtml .= $bildertreffhtml;
}




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
			$structxml = ($entry->link[3]->attributes()->href); // Dette er XML med struct, inkl. omslagsbilde
			$xmlfile = get_content($structxml);
			$structxmldata = simplexml_load_string($xmlfile);			

			// FINNE URN			
			if (stristr($nb->urn , ";")) {
				$tempura = explode (";" , $nb->urn);
				$urn = trim($tempura[1]); // vi tar nummer 2 
			} else {
				$urn = $nb->urn[0];
			}

			// FINNE OMSLAG
			foreach ($structxmldata->div as $utgave) {
				if ($utgave->attributes()->ORDER == "1") { // Hvis første side
				$sideid = $utgave->resource->attributes("xlink", TRUE)->href; //  FÅR MED FILENDELSE!
				}
			}			
			
			$avisertreff[$teller]['bokomslag'] = "http://www.nb.no/services/image/resolver?url_ver=geneza&urn=" . $sideid . "&maxLevel=6&level=2&col=0&row=0&resX=6000&resY=6000&tileWidth=2048&tileHeight=2048";
			
			$avisertreff[$teller]['url'] = "http://urn.nb.no/" . $urn;
			$avisertreff[$teller]['tittel'] = $childxmldata->titleInfo->title;
			
			$avisertreff[$teller]['kilde'] = "Nasjonalbiblioteket - aviser";
			
			$teller++;
		}
	} // SLUTT PÅ HVERT ENKELT TREFF

foreach ($avisertreff as $singeltreff) {
		$avisertreffhtml = str_replace ("twitterurlString" , urlencode($singeltreff['url']) , $singlehtml);
		$avisertreffhtml = @str_replace ("twitterdescriptionString" , htmlspecialchars($tittel). htmlspecialchars(" (".$singeltreff['beskrivelse'].")"), $avisertreffhtml);		
        $avisertreffhtml = str_replace ("urlString" , $singeltreff['url'] , $avisertreffhtml);
        $avisertreffhtml = str_replace ("titleString" , trunc($singeltreff['tittel'], 12) , $avisertreffhtml);
        $avisertreffhtml = @str_replace ("descriptionString" , trunc($singeltreff['beskrivelse'], 20) , $avisertreffhtml);
		$avisertreffhtml = str_replace ("omslagString" , $singeltreff['bokomslag'] , $avisertreffhtml);
		$avisertreffhtml = str_replace ("classString" , "avisertreff" , $avisertreffhtml);
       
        $aviserhtml .= $avisertreffhtml;
}


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
	
			$teller++;
			
		}
	} // SLUTT PÅ HVERT ENKELT TREFF

foreach ($norvegianatreff as $singeltreff) {
		$norvegianatreffhtml = str_replace ("twitterurlString" , urlencode($singeltreff['url']) , $singlehtml);
		$norvegianatreffhtml = str_replace ("twitterdescriptionString" , htmlspecialchars($tittel). htmlspecialchars(" (".$singeltreff['beskrivelse'].")"), $norvegianatreffhtml);		
        $norvegianatreffhtml = str_replace ("urlString" , $singeltreff['url'] , $norvegianatreffhtml);
        $norvegianatreffhtml = str_replace ("titleString" , trunc($singeltreff['tittel'], 20) , $norvegianatreffhtml);
        $norvegianatreffhtml = str_replace ("descriptionString" , trunc($singeltreff['beskrivelse'], 35) , $norvegianatreffhtml);
		$norvegianatreffhtml = str_replace ("omslagString" , $singeltreff['bokomslag'] , $norvegianatreffhtml);
		$norvegianatreffhtml = str_replace ("classString" , "norvegianatreff" , $norvegianatreffhtml);
       
        $norvegianahtml .= $norvegianatreffhtml;
}



// Søke i historieboka.no


// FERDIG MED Å SØKE - SKRIVE UT RESULTATER

?>

<ul id="lokalhistorietrefftabs" class="lokalhistorieshadetabs" style="margin-bottom: 0;">
<li style="margin: 0;"><a href="#" rel="lokalhistorietab1">Bokhylla (<?php echo (int)$bokhyllaantalltreff;?>)</a></li>
<li style="margin: 0;"><a href="#" rel="lokalhistorietab2">NB-bilder (<?php echo (int)$bilderantalltreff;?>)</a></li>
<li style="margin: 0;"><a href="#" rel="lokalhistorietab3">NB-aviser (<?php echo (int)$aviserantalltreff;?>)</a></li>
<li style="margin: 0;"><a href="#" rel="lokalhistorietab4">Norvegiana (<?php echo (int)$norvegianaantalltreff;?>)</a></li>
</ul>

<div style="background-color: #eee; margin-bottom: 1em; padding: 10px; max-height: 400px; overflow: auto;">

<div id="lokalhistorietab1" class="lokalhistorietabcontent">
<?php 
if ((int)$bokhyllaantalltreff > 0) {
	echo $bokhyllahtml;
} else {
	echo "Ingen treff!";
}
?>
</div>

<div id="lokalhistorietab2" class="lokalhistorietabcontent">
<?php 
if ((int)$bilderantalltreff > 0) {
	echo $bilderhtml;
} else {
	echo "Ingen treff!";
}
?>
</div>

<div id="lokalhistorietab3" class="lokalhistorietabcontent">
<?php 
if ((int)$aviserantalltreff > 0) {
	echo $aviserhtml;
} else {
	echo "Ingen treff!";
}
?>
</div>

<div id="lokalhistorietab4" class="lokalhistorietabcontent">
<?php 
if ((int)$norvegianaantalltreff > 0) {
	echo $norvegianahtml;
} else {
	echo "Ingen treff!";
}
?>
</div>


</div>

<script type="text/javascript">

var lokalhistorietabber=new ddtabcontent("lokalhistorietrefftabs")
lokalhistorietabber.setpersist(false)
lokalhistorietabber.setselectedClassTarget("link") //"link" or "linkparent"
lokalhistorietabber.init()

</script>


<?php

//echo $bokselskaphtml;
//echo $bokhyllahtml;
//echo $openlibraryhtml;
?>
