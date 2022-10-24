<?php 
/**
 * @version		1.0.0
 * @package		CGZipDir content plugin
 * @author		ConseilGouz
 * @copyright	Copyright (C) 2022 ConseilGouz. All rights reserved.
 * @license		GNU/GPL v2; see LICENSE.php
 **/
defined( '_JEXEC' ) or die( 'Restricted access' );
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

class plgContentCGZipDir extends CMSPlugin
{	
    public $myname='CGZipDir';
    public function __construct(& $subject, $config)
    {
        parent::__construct($subject, $config);
        $this->loadLanguage();
    }
    
	public function onContentPrepare($context, &$article, &$params, $page = 0) {
		// Don't run this plugin when the content is being indexed
		if ($context == 'com_finder.indexer') {
			return true;
		}
		// check zipdir tags
		$regex_one		= '/({zipdir\s*)(.*?)(})/si';
		$regex_all		= '/{zipdir\s*.*?}/si';
		$shortcode = $this->params->get('shortcode','zipdir'); 
		if (strpos($article->text, '{'.$shortcode.'') === false ) {
			return true;
		}
		$regex = '/{'.$shortcode.'[\s\S]+?{\/'.$shortcode.'}*\s*/'; // get each accordeon chunk
		if (preg_match_all($regex,$article->text,$matches,PREG_OFFSET_CAPTURE | PREG_PATTERN_ORDER)) {
		    $regex = '/(?:<(div|p)[^>]*>)?{'.$shortcode.'(?:=(.+))?}(?(1) *<\/\1>)?([\s\S]+?)(?:<(div|p)[^>]*>)?{\/'.$shortcode.'}(?(4) *<\/\4>)/i';
		    foreach($matches[0] as $key=>$ashort) {
		        if (preg_match_all($regex, $ashort[0], $dirs, PREG_SET_ORDER)) { // ensure the more specific regex matches
		            foreach ($dirs as $onedir) {
		                $backup = 'tmp/'.$onedir[3].'.zip';
		                $this->createzip($onedir[3],$backup); // zip a directory
		                $output = "<a href=".$backup." download=".$onedir[3].'.zip'." class='btn btn_zipdir'>".TEXT::_('PLG_CONTENT_CGZIPDIR_BTNTXT').$onedir[3]."</a>";
		                $article->text = str_replace($onedir[0], $output, $article->text);
		            }
		        }
		    }
		}
		return true;
	}
// from https://stackoverflow.com/questions/1334613/how-to-recursively-zip-a-directory-in-php/1334949#1334949	
    function createzip($dir,$dest){
        $exclusions = [];
    // Excluding an entire directory
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('tmp/'), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($files as $file){
            array_push($exclusions,$file);
        }
    // Excluding a file
        array_push($exclusions,'.htaccess');
        array_push($exclusions,'index.html');
    // Excluding the backup file
        array_push($exclusions,$dest);
        $this->Zip($dir,$dest, false, $exclusions);
    }	
    function Zip($source, $destination, $include_dir = false, $exclusions = false){
    // Remove existing archive
        if (file_exists($destination)) {
            unlink ($destination);
        }
        $zip = new ZipArchive();
        if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
            return false;
        }
        $base = $source;
        $folder = 'images/'.$this->params->get('folder','');
        
        $zip = $this->zip_r($folder.'/'.$source, $zip, $base,$exclusions);
        $zip->close();
    }
    function zip_r($from, $zip, $base=false,$exclusions=false) {
        if (!file_exists($from) OR !extension_loaded('zip')) {return false;}
        if (!$base) {$base = $from;}
        $base = trim($base, '/');
        $zip->addEmptyDir($base);
        $dir = opendir($from);
        while (false !== ($file = readdir($dir))) {
            if ($file == '.' OR $file == '..') {continue;}
            if(($exclusions)&&(is_array($exclusions))){
                if(in_array($file, $exclusions)){
                    continue;
                }
            }
            if (is_dir($from . '/' . $file)) {
                $this->zip_r($from . '/' . $file, $zip, $base . '/' . $file);
            } else {
                $zip->addFile($from . '/' . $file, $base . '/' . $file);
            }
        }
        return $zip;
    }
}
?>