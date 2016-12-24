<?php
/**
* class: NestedXmlReader
* purpose: import an xml structure into a sql NestedSet structure
* Focus: Data/tree consistency
*
* @copyright Copyright (C) 2001-2016 Webschreinerei
* @author Dennis Suchomsky dennis.suchomsky@gmail.com
* @license GPL
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*/
class NestedXmlReader extends NestedSets {
	
	/**
	 * 
	 * @var Object $xmlReader xmlReader object
	 */
	protected $xmlReader;
	
	/**
	 * 
	 * @var String $xmlUrl path to a xml document
	 */
	protected $xmlUrl;
	
	function __construct($xmlUrl, $dbParams, $columns){
		parent::__construct($dbParams, $columns);
		
	//	$xmlFileString = file_get_contents($xmlUrl);
	//	$this->xmlReader = XMLReader::xml($xmlFileString);	
		$this->xmlReader = new XmlReader();
		if ($this->xmlReader->open($xmlUrl)) {
			//$this->xmlReader->setSchema('/home/sushi/workspace_neon/vinyl-preorder/tmp/jpc.xsd');
			//$this->xmlReader->setParserProperty(XMLReader::VALIDATE, true);
			$this->xmlReader->read();//skip root element
			$this->processElements();
		}
		$this->closeConnection();
	}
	
	/**
	 * 
	 * Imports xml file into sql nested set table
	 */
	private function processElements(){
		$parents[] = 0;
		$depth = 0;
		while ($this->xmlReader->read()) {
			switch($this->xmlReader->nodeType){
				case(XMLReader::END_ELEMENT):
					array_pop($parents);
					$depth--;
				break;
				
				case(XMLREADER::ELEMENT):
						if ($depth == 0) {
							$parents[] = $this->createRootNode($this->xmlReader->name);	;
						}else {
							$parents[] = $this->createSubNode($parents[$depth],$this->xmlReader->name);	
						}
						$depth++;
				break;
				
				case(XMLREADER::TEXT):
					$this->createSubNode($parents[$depth], $this->xmlReader->value);
				break;
			}
		}
	}
}
?>