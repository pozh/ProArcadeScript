<?php
/*******************************************************************
/ ProArcadeScript
/ File description:
/ Implementation of the CCache class
/
/*******************************************************************/


//-----------------------------------------------------------------------------------------
// helper function which recursively removes a directory
//
// to use this function to totally remove a directory, write:
// remove_directory('path/to/directory/to/delete');
// to use this function to empty a directory, write:
// remove_directory('path/to/full_directory',TRUE);
//-----------------------------------------------------------------------------------------
function remove_directory( $directory, $empty=FALSE )
{
	if(substr($directory,-1) == '/')
		$directory = substr($directory,0,-1);
	if(!file_exists($directory) || !is_dir($directory))
		return FALSE;
	elseif(is_readable($directory))
	{
		$handle = opendir($directory);
		while (FALSE !== ($item = readdir($handle)))
		{
			if($item != '.' && $item != '..')
			{
				$path = $directory.'/'.$item;
				if(is_dir($path))
					remove_directory($path);
				else
					unlink($path);
			}
		}
		closedir($handle);
		if($empty == FALSE)
			if(!rmdir($directory))
				return FALSE;
	}
	return TRUE;
}





//-----------------------------------------------------------------------------------------
// Class CCache
//-----------------------------------------------------------------------------------------
class CCache
{
	var $ttl = 2000; // Time to leave, seconds
	var $cache_root = '';
	var $enabled = false;

//-----------------------------------------------------------------------------------------
function CCache()
{
	$path = $_SERVER['DOCUMENT_ROOT'] . $GLOBALS['cSite']['sSiteRoot'] . 'cache';
	$this->ttl = $GLOBALS['cSite']['cacheTTL'];
	if( !is_dir($path) )
	{
		mkdir( $path, 0775 );
		$file = fopen( $path . '/index.html', 'w' );
		if( $file )
		{
			fwrite( $file, ' ' );
			fclose( $file );
		}
	}
	$this->cache_root = "$path/";
	$this->enabled = $GLOBALS["cSite"]["bCache"];
}

//-----------------------------------------------------------------------------------------
function get()
{
	if( !$this->enabled )
	   return false;
	   
	$name = $_SERVER['REQUEST_URI'];
	$ext =  $_SESSION['user'];
	$hash = sha1($name);
	$hash_ext = sha1($ext);
	$cache_dir = substr( $hash, 0, 5 );
	$cache_subdir = substr( $hash, 5, 5 );
	$time = date( 'U' );
	$cache_file = $this->cache_root . $cache_dir . '/' . $cache_subdir . '/' . $hash . '.' . $hash_ext;
	if( file_exists($cache_file) && ($time - filemtime($cache_file) < $this->ttl) )
	{
		$file = fopen( $cache_file, 'r' );
		if( $file )
		{
			$data = fread( $file, filesize($cache_file) );
			fclose( $file );
			return unserialize( $data );
		}
		else
			return false;
	}
	else
	   return false;

}
	
//-----------------------------------------------------------------------------------------
function write( $data )
{
	if( !$this->enabled )
	   return true;
	   
	$name = $_SERVER['REQUEST_URI'];
	$ext =  $_SESSION['user'];
	$hash = sha1($name);
	$hash_ext = sha1($ext);
	$cache_dir = $this->cache_root . '/' . substr( $hash, 0, 5 );
	$cache_subdir = $cache_dir . '/' . substr( $hash, 5, 5 );
	if( !is_dir($cache_dir) )
		mkdir( $cache_dir, 0775 );
	if( !is_dir($cache_subdir) )
		mkdir( $cache_subdir, 0775 );
	$file = fopen( $cache_subdir.'/'.$hash.'.'.$hash_ext, 'w' );
	if( $file )
	{
		$res = fwrite( $file, serialize($data) );
		fclose( $file );
		return $res;
	}
	return false;
}

//-----------------------------------------------------------------------------------------
function delete( $what, $id, $title )
{
	switch( $what )
	{
	   case 'game':
	      $name = GameURL( $id, $title );
			$hash = sha1($name);
   		$cache_dir = (substr($this->cache_root,-1) == '/') ? substr( $this->cache_root, 0, -1 ) : $this->cache_root;
 			$cache_dir .= '/' . substr( $hash, 0, 5 ) . '/' . substr( $hash, 5, 5 );
         remove_directory( $cache_dir );
	   	break;
		case 'cat':
	      $name = CategoryURL( $title );
			$hash = sha1($name);
   		$cache_dir = (substr($this->cache_root,-1) == '/') ? substr( $this->cache_root, 0, -1 ) : $this->cache_root;
   		$cache_dir .= '/' . substr( $hash, 0, 5 ) . '/' . substr( $hash, 5, 5 );
         remove_directory( $cache_dir );
		case 'home':
	      $name = $GLOBALS['cSite']['sSiteRoot'];
			$hash = sha1($name);
   		$cache_dir = (substr($this->cache_root,-1) == '/') ? substr( $this->cache_root, 0, -1 ) : $this->cache_root;
   		$cache_dir .= '/' . substr( $hash, 0, 5 ) . '/' . substr( $hash, 5, 5 );
         remove_directory( $cache_dir );
		   break;
		case 'page':
         $name = $GLOBALS['cSite']['sSiteRoot'] . 'docs/' . $id;
			$hash = sha1($name);
   		$cache_dir = (substr($this->cache_root,-1) == '/') ? substr( $this->cache_root, 0, -1 ) : $this->cache_root;
   		$cache_dir .= '/' . substr( $hash, 0, 5 ) . '/' . substr( $hash, 5, 5 );
         remove_directory( $cache_dir );
			break;
	   default:
	   	break;
	}
}


//-----------------------------------------------------------------------------------------
function clear_cache()
{
	// empty the cache dir
	remove_directory( $this->cache_root, TRUE );
}


}//class


?>