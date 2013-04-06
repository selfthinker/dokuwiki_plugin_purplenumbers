<?php
/*
 * PurpleNumbers plugin, default configuration settings
 *
 */

$conf['numbering']        = 1; //0= consecutive IDs, 1= hierarchical IDs
$conf['uniqueness']       = 0; //0= IDs unique per page, 1= IDs unique per site
$conf['internalID']       = 0; //create IDs that can be used as internal anchors?
$conf['linkText']         = 0; //0= display paragraph/section sign, 1= display ID
$conf['restrictionNS']    = ''; //restrict usage of plugin to these first level namespaces (comma separated)
$conf['restrictionType']  = 1; //0= exclude restricted NS, 1=include restricted NS
$conf['includeStartpage'] = 1;
$conf['annotationPage']   = '';
