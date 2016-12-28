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
class NestedXmlWriter extends NestedSets {
	
	/**
	 * 
	 * @var Object $xmlReader xmlReader object
	 */
	protected $xmlWriter;
	
	function __construct($dbParams, $columns){
		parent::__construct($dbParams, $columns);
		
		$this->xmlReader = $xmlUrl;	
		$this->xmlReader = new XmlReader();
		$this->xmlReader->open($xmlUrl);
		
		$this->processElements();
	}
	
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