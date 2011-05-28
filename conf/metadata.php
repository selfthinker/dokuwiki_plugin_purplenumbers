<?php
/*
 * PurpleNumbers plugin, configuration metadata
 *
 */

$meta['numbering']       = array('multichoice','_choices' => array(0,1));
$meta['uniqueness']      = array('multichoice','_choices' => array(0,1));
$meta['internalID']      = array('onoff');
$meta['linkText']        = array('multichoice','_choices' => array(0,1));
$meta['restrictionNS']   = array('string');
$meta['restrictionType'] = array('multichoice','_choices' => array(0,1));
$meta['annotationPage']  = array('string');
