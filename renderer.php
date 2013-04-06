<?php
/**
 * Renderer for adding Purple Numbers
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author Anika Henke <anika@selfthinker.org>
 * @author Andreas Gohr <andi@splitbrain.org>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

// we inherit from the XHTML renderer instead directly of the base renderer
require_once DOKU_INC.'inc/parser/xhtml.php';

class renderer_plugin_purplenumbers extends Doku_Renderer_xhtml {

    var $PNitemCount = 0;

    function getFormat(){
        return 'xhtml';
    }

    function canRender($format) {
        return ($format=='xhtml');
    }


    function document_end() {
        parent::document_end();

        // make sure there are no empty paragraphs
        $this->doc = preg_replace('#<p[^>]*>\s*<!--PN-->.*?(?:</p>)#','',$this->doc);
        // remove PN comment again (see _getLink())
        $this->doc = preg_replace('/<!--PN-->/','',$this->doc);
    }

    function header($text, $level, $pos) {
        parent::header($text, $level, $pos);

        if ($this->_displayPN()) {
            $pnid = $this->_getID($this->getConf('numbering')?2:1);
            $linkText = $this->getConf('linkText') ? $pnid : '§';

            $link = '&nbsp;<a href="#'.$pnid.'" id="'.$pnid;
            $link .= '" class="pn" title="'.$this->getLang('sectionlink').'">'.$linkText.'</a>';
            $link .= $this->_getAnnotationLink();

            $this->doc = preg_replace('/(<\/h[1-5]>)$/', $link.'\\1', $this->doc);
        }
    }

    function p_open() {
        $this->doc .= DOKU_LF.'<p'.$this->_getID(1,1).'>'.DOKU_LF;
    }

    function p_close() {
        $this->doc .= $this->_getLink().'</p>'.DOKU_LF;
        if (preg_match('/<p[^>]*>\s*<!--PN-->.*?(?:<\/p>)$/',$this->doc)) $this->PNitemCount--;
    }

    function listitem_open($level) {
        $this->doc .= '<li class="level'.$level.'"'.$this->_getID(1,1).'>';
    }

    function listcontent_close() {
        $this->doc .= $this->_getLink().'</div>'.DOKU_LF;
    }

    function preformatted($text, $type='code') {
        $this->doc .= '<pre class="'.$type.'"'.$this->_getID(1,1).'>'.
                      trim($this->_xmlEntities($text),"\n\r").
                      $this->_getLink().'</pre>'.DOKU_LF;
    }

    function table_open($maxcols = NULL, $numrows = NULL){
        $this->_counter['row_counter'] = 0;
        $this->doc .= '<div class="table"><table class="inline"'.$this->_getID(1,1).'>'.DOKU_LF;
    }

    function table_close(){
        $this->doc .= '</table></div>'.$this->_getLink(1).DOKU_LF;
    }


    function php($text, $wrapper='code') {
        global $conf;

        if($conf['phpok']){
          ob_start();
          eval($text);
          $this->doc .= ob_get_contents();
          ob_end_clean();
        } elseif($wrapper != 'code') {
            $code  = '<'.$wrapper.$this->_getID(1,1).' class="code php">';
            $code .= trim(p_xhtml_cached_geshi($text, 'php', false),"\n\r");
            $code .= $this->_getLink();
            $code .= '</'.$wrapper.'>';
            $this->doc .= $code;
        } else {
            $this->doc .= p_xhtml_cached_geshi($text, 'php', $wrapper);
        }
    }

    function html($text, $wrapper='code') {
        global $conf;

        if($conf['htmlok']){
            $this->doc .= $text;
        } elseif($wrapper != 'code') {
            $code  = '<'.$wrapper.$this->_getID(1,1).' class="code html4strict">';
            $code .= trim(p_xhtml_cached_geshi($text, 'html4strict', false),"\n\r");
            $code .= $this->_getLink();
            $code .= '</'.$wrapper.'>';
            $this->doc .= $code;
        } else {
            $this->doc .= p_xhtml_cached_geshi($text, 'html4strict', $wrapper);
        }
    }

    function _highlight($type, $text, $language=null, $filename=null) {
        global $conf;
        global $ID;
        global $lang;

        if($filename){
            list($ext) = mimetype($filename,false);
            $class = preg_replace('/[^_\-a-z0-9]+/i','_',$ext);
            $class = 'mediafile mf_'.$class;

            $this->doc .= '<dl class="'.$type.'">'.DOKU_LF;
            $this->doc .= '<dt><a href="'.exportlink($ID,'code',array('codeblock'=>$this->_codeblock)).'" title="'.$lang['download'].'" class="'.$class.'">';
            $this->doc .= hsc($filename);
            $this->doc .= '</a></dt>'.DOKU_LF.'<dd>';
        }

        if ($text{0} == "\n") {
            $text = substr($text, 1);
        }
        if (substr($text, -1) == "\n") {
            $text = substr($text, 0, -1);
        }

        if ( is_null($language) ) {
            $this->preformatted($text, $type);
        } else {
            $class = 'code';
            if($type != 'code') $class .= ' '.$type;

            $this->doc .= "<pre class=\"$class $language\" ".$this->_getID(1,1).">".
                          p_xhtml_cached_geshi($text, $language, '').
                          $this->_getLink().'</pre>'.DOKU_LF;
        }

        if($filename){
            $this->doc .= '</dd></dl>'.DOKU_LF;
        }

        $this->_codeblock++;
    }


    /**
     * Builds Purple Number ID.
     *
     * $setCount: increases (1) or resets (2) $PNitemCount
     * $wrap: wrap output in 'id=""'
     * $noprefix: lets you get the current ID without its prefix
     * $internalID: clean ID, if it needs to be used as an internal ID
     */
    function _getID($setCount=0, $wrap=0, $noprefix=0, $internalID=0) {
        if ($this->_displayPN()) {

            if (!$internalID) {
                $internalID = $this->getConf('internalID');
            }

            if ($setCount == 1) {
                //increase for each new paragraph, etc
                $this->PNitemCount++;
            } else if ($setCount == 2) {
                //reset for each new section (headline)
                $this->PNitemCount = 0;
            }

            // build prefix
            if ($noprefix) {
                $prefix = '';
            } else if ($this->getConf('uniqueness')) {
                //site-wide
                global $ID;
                $prefix = $ID.'.';
            } else {
                //page-wide
                $prefix = 'ID';
            }

            if ($this->getConf('numbering')) {
                //hierarchical
                $nodeID = preg_replace('/(\.0)*$/','',join('.',$this->node));
                $itemNo = str_replace(':0','',':'.$this->PNitemCount);
                $out = $prefix.$nodeID.$itemNo;
            } else {
                //consecutive
                $out = $prefix.$this->PNitemCount;
            }

            // if the ID should be re-usable as an anchor in an internal link
            if ($internalID) {
                // sectionID() will strip out ':' and '.'
                $out = str_replace(array(':','.'), array('-','_'), $out);
                $out = cleanID($out);
            }

            if ($wrap) return ' id="'.$out.'"';
            return $out;
        }
        return '';
    }

    /**
     * Creates a link to the current Purple Number ID.
     *
     * $outside: puts a p.pnlink around the link, useful if
     *     the link cannot be inside its corresponding element (e.g. tables)
     */
    function _getLink($outside=0) {
        if ($this->_displayPN()) {
            $linkText = $this->getConf('linkText') ? $this->_getID() : '¶';
            $sep = $outside ? '' : '&nbsp;';

            $pnlink = $sep.'<a href="#'.$this->_getID().'" class="pn" title="'.$this->getLang('sectionlink').'">'.$linkText.'</a>';
            $pnlink .= $this->_getAnnotationLink();

            if ($outside) {
                return '<p class="pnlink">'.$pnlink.'</p>';
            }
            return '<!--PN-->'.$pnlink;
            // that <!--PN--> comment is added to be able to delete empty paragraphs in document_end()
        }
        return '';
    }

    /**
     * Creates a link to an annotation page per Purple Number.
     */
    function _getAnnotationLink() {
        $annotationPage = $this->getConf('annotationPage');
        if ($annotationPage) {
            global $ID;
            // resolve placeholders
            $aID = str_replace(
                       array(
                          '@PN@',
                          '@PNID@',
                          '@ID@',
                          '@PAGE@'
                       ),
                       array(
                           $this->_getID(0,0,1,1),
                           $this->_getID(0,0,0,1),
                           $ID,
                           noNSorNS($ID)
                       ),
                       $annotationPage
                   );
            // in case linkText is only a pilcrow, only show the icon
            $onlyIcon = $this->getConf('linkText') ? '' : 'onlyIcon';
            $sep = $onlyIcon ? '&nbsp;' : ' ';

            return $sep.'<span class="pn '.$onlyIcon.'">'.
                   html_wikilink($aID,$this->getLang('comment')).
                   '</span>';
        }
        return '';
    }

    /**
     * Checks if the adding of the Purple Number should be restricted
     *   (by configuration settings 'restrictionNS' and 'restrictionType').
     */
    function _displayPN() {
        global $ID, $INFO, $ACT, $conf;

        if (!page_exists($ID)) return false;
        // only show PNs in the main content, not in included pages (like sidebars)
        if ($ID != $INFO['id']) return false;
        if ($ACT != 'show') return false;
        if (!$this->getConf('includeStartpage') && noNS($ID)==$conf['start']) return false;

        if ($this->getConf('restrictionNS')) {
            $curRootNS = substr($ID, 0, strpos($ID,':'));
            $restrictionNS = explode(',', $this->getConf('restrictionNS'));
            $restrictionType = $this->getConf('restrictionType');

            foreach ($restrictionNS as $r) {
                if (trim($r) == $curRootNS) {
                    if ($restrictionType)
                        return true;
                    return false;
                }
            }
            if ($restrictionType)
                return false;
            return true;
        }
        return true;
    }

}

