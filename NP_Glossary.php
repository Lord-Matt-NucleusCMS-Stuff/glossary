<?php

/*
Nucleus CMS Glossary Plugin V1.1
(C) 2005 Lord Matt

This is possibly the laziest hack I ever wrote. It is dire code.

That said the principle is great an needs to be improved.

Version history
  v1.1 First release 

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
Contact me : letters@lordmatt.co.uk
You may use and distribute this freely as long as you leave the copyrights intact.

*/



// spelling error cope while coding
class NP_Glossery extends NP_Glossary {}

class NP_Glossary extends NucleusPlugin {

	var $ar_body = array();
	var $ar_more = array();
	var $all = array();

	// name of plugin
	function getName() {
		return 'Glossary Maker';
	}

	// author of plugin
	function getAuthor()  {
		return 'Lord Matt';
	}

	// an URL to the plugin website
	// can also be of the form mailto:foo@bar.com
	function getURL() {
		return 'http://wiki.lordmatt.co.uk/index.php/NP_Glossary';
	}

	function getEventList() { return array('AddItemFormExtras', 'PreAddItem', 'EditItemFormExtras', 'PreUpdateItem','PostItem'); }

	// not the miss-spelling in the variable names! 
	function install(){
		$this->createOption("glossery_item", "item (row) format", "text", "#listitem#, ");
		$this->createOption("glossery_link_x", "add extra html attributes?", "text", "rel=\"tag\"");
		$this->createOption("glossery_url", "where are your deffinitions kept?", "text", "http://lordmatt.co.uk/fact/#def#");
		$this->createOption("glossery_block", "block format", "textarea", "<p style=\"font-size:90%;\"><strong>Glossary:</strong> #listitems# <span class=\"NP_Glossary\" title=\"Why Offer A Glossary?\"><a href=\"http://wiki.lordmatt.co.uk/index.php/Why_Define\"  style=\"COLOR: #CC6633;CURSOR: help;TEXT-DECORATION: none;\">Why Define</a></span></p>");
		$this->createOption("glossery_glossary", "Glossary format", "textarea", "<p><strong>#def#</strong><br />#full#</p>");
		
		$query = 'CREATE TABLE IF NOT EXISTS '.sql_table("plugin_glossery_words").' ( '.
				'define varchar(55),'.
				'short varchar(255),'.
				'full text ) ';
		mysql_query($query);
	}

	// version of the plugin
	function getVersion() {
		return '1.1';
	}

	// a description to be shown on the installed plugins listing
	function getDescription() {
		return 'Scans your post for keywords as it is shown and then creates a glossary applied with skinvar Glossary(list) there are lots of other skinvars please see http://wiki.lordmatt.co.uk/index.php/NP_Glossary for the full lowdown.  One new word may be added with each post or Admin Area Edit (not pop-up edit).';
	}

	function event_PostItem($data) {
		$this->currentItem = $data["item"];
		$this->ar_body = $this->finddefs($this->currentItem->body);
		$this->ar_more = $this->finddefs($this->currentItem->more);

	}

	function finddefs($text){
		// init a var
		$findings = array();
		
		// only load the entire collection once.
		if (count($this->all) < 1){
			$sql = "SELECT * FROM " . sql_table("plugin_glossery_words");
			$rez = mysql_query($sql);
			if ($rez !== false){
				while($row = mysql_fetch_array($rez, MYSQL_ASSOC)){
					$this->all[$row['define']] = array('define' => $row['define'], 'short' => $row['short'], 'full' => $row['full']) ;
				}
			}
		}
		
		foreach ($this->all as $needle){
			if (strpos ( strtolower($text), strtolower($needle['define']) ) !== false){
				$findings[] = $needle['define'];
			}
		}
		
		$findings = array_unique($findings); // remove dupes.
		sort ($findings , SORT_LOCALE_STRING); // now put them in order
		
		return $findings;
	}

	function event_EditItemFormExtras($data){
		$this->event_AddItemFormExtras($data);
	}
	
	function event_AddItemFormExtras($data){
		//BLOGOBJECT
		//echo "VALUE:" . $this->getBlogOption($_GET['blogid'], "review_use");
			?>
				<h3>New Glossery Entry?</h3>
				<p>You are encuraged to define one new word each time you post.  In no time you will have a full dictionary and thankful readers</p>
				<p><input type="checkbox" name="Glossary_add" value="yes"/> Check to add a new one</p>
				<p>Define This* : <br />
				<input type="text" name="Glossary_define" /></p>
				<p>Meaning, Short no HTML*: <br /> 
				<input type="text" name="Glossary_short" style="width:90%;"/></p>
				<p>Full version HTML if you like: <br /> 
				<textarea name="Glossary_full" rows="3" style="width:90%;"></textarea></p>
				
				
			<?php			
	}
	
	function event_PreUpdateItem($data){
		$this->event_PreAddItem($data);
	}
	function event_PreAddItem($data){
		if(isset($_POST['Glossary_add']) AND ($_POST['Glossary_add'] == 'yes')){
			if(get_magic_quotes_gpc()) {
				$word        = stripslashes($_POST['Glossary_define']);
				$short		 = stripslashes($_POST['Glossary_short']);
				$full		 = stripslashes($_POST['Glossary_full']);
			} else {
				$word        = $_POST['Glossary_define'];
				$short		 = $_POST['Glossary_short'];
				$full		 = $_POST['Glossary_full'];
			}
	
			// Make a safe query
			$query = sprintf("INSERT INTO %s (`define`, `short`, `full`) VALUES ('%s', '%s', '%s')",
						sql_table("plugin_glossery_words"),
						mysql_real_escape_string($word ),
						mysql_real_escape_string($short),
						mysql_real_escape_string($full));
	
			mysql_query($query);
		}
	}
	
	function format_def($word, $short=''){
	
		// build url
		$url = str_replace ( '#def#' , $word , $this->getOption("glossery_url") );
		
		$formatted = '
			<span class="NP_Glossery" title="' . $short . '">
			<a href="' . $url . '" ' . $this->getOption("glossery_link_x") . ' style="COLOR: #CC6633;CURSOR: help;TEXT-DECORATION: none;">
			' . $word . '
			</a></span>
			';
		
		// drop this into the "row" format
		$formatted = str_replace( '#listitem#' , $formatted , $this->getOption("glossery_item") );
		
		return $formatted;
	}
	
	function output_list($data = ''){
		$fullest = $this->ar_body + $this->ar_more;
		if (count($fullest) > 0){
			$list ='';
			foreach( $fullest as $bit){
				$list .= $this->format_def($this->all[$bit]['define'],$this->all[$bit]['short']);
			}
			$output = str_replace( '#listitems#' , $list , $this->getOption("glossery_block") );
			echo $output;
		}
	}
	
	// $limit = the single word
	function output_glossary($limit = ''){
		$format = $this->geteOption("glossery_glossary");
		$sql = "SELECT * FROM " . sql_table("plugin_glossery_words") . " ";
		if ($limit !== ''){
			$sql .= "WHERE `define` = '" . mysql_real_escape_string($limit) . "' ";
		}
		$rez = mysql_query($sql);
		if ($rez !== false){
			while($row = mysql_fetch_array($rez, MYSQL_ASSOC)){
				$local[] = array('define' => $row['define'], 'short' => $row['short'], 'full' => $row['full']) ;
			}
			$local = array_unique($findings); // remove dupes.
			sort ($local , SORT_LOCALE_STRING);
			foreach($local as $row){
				$output = str_replace( '#def#', $row['define'], $this->getOption("glossery_glossary"));
				$output = str_replace( '#full#', $row['full'], $output);
				echo $output;
			}
		}
	}
	
	function output_dynamic($getvar){
		$this->output_glossary($_GET[$getvar]);
	}
	
	function doTemplateVar(&$item, $thing='', $data=''){
		// this time we need a reading on the item content
		$this->currentItem = $item;
		$this->ar_body = $this->finddefs($this->currentItem->body);
		$this->ar_more = $this->finddefs($this->currentItem->more);
		$this->doskinvar('', $thing, $data);
	}
	
	function doskinvar($skinType, $thing='', $data=''){
		if (method_exists($this, 'output_' . $thing)){
			call_user_func(array($this,'output_' . $thing), $data);
		}else{
			$this->output_list($data);
		}
		//echo "<!--[$skinType|$thing]";
		//print_r($this->ar_body);
		//echo"-->";
	}
}


?>
