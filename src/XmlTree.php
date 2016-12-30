<?php
/**
 * class: XmlTree
 * purpose: import an xml structure into a sql NestedSet structure
 * Focus: Data/tree consistency
 *
 * @copyright Copyright (C) 2001-2016 Webschreinerei
 * @author Dennis Suchomsky dennis.suchomsky@gmail.com
 * @license GPL
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */
namespace Suchomsky\SqlTree;

class XmlTree extends SqlTree {

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

    function __construct($xmlUrl, &$pdo, $columns = NULL){
        parent::__construct($pdo, $columns);

        $this->xmlReader = $xmlUrl;
        $this->xmlReader = new \XmlReader();
        $this->xmlReader->open($xmlUrl);

        $this->processElements();
    }

    private function processElements(){
        $depth = 0;
        while ($this->xmlReader->read()) {
            switch($this->xmlReader->nodeType){

                case(\XMLReader::END_ELEMENT):
                    $this->levelUp();
                    $depth--;
                    break;

                case(\XMLREADER::ELEMENT):
                    if ($depth == 0) {
                        $this->insertRootNode($this->xmlReader->name);	;
                    }else {
                        $this->insertSubNode($this->xmlReader->name);
                    }
                    $depth++;
                    break;

                case(\XMLREADER::TEXT):
                    $this->insertSubNode($this->xmlReader->value);
                    break;
            }
        }
    }
}
?>