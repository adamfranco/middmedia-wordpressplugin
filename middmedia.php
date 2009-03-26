<?php
/*
  Plugin Name: MiddMedia
  Plugin URI: http://blogs.middlebury.edu
  Description: Manage and use content from your MiddMedia account
  Version: 1.0
  Author: Ian McBride
  Author URI: http://blogs.middlebury.edu/imcbride
  
  This program is not free software.
  &copy; The President and Fellows of Middlebury College. All Rights Reserved.
  
  
  *** Change log ***
  
  Updated: 2009-02-05 (Brendan Smith) Added code to set video dimensions to global variables set in
  the wordpress template. Makes video fill page in the regular single page view of Middtube.

  Updated: 2009-02-27 (Adam Franco) Added support for writing <enclosure/> tags to the RSS feeds
  in order to support podcasting.
  
  
*/

// pre 2.6 compatibility

if (!defined('WP_CONTENT_URL')) {
  define('WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
}

$middmedia = new middmedia();

include_once('config.inc');

class middmedia {

  function __construct() {
  
    // add the tab to the image upload pages
    add_action('media_upload_file', array($this, 'add_media_tab'));
    add_action('media_upload_gallery', array($this, 'add_media_tab'));
    add_action('media_upload_library', array($this, 'add_media_tab'));
    add_action('media_upload_middmedia', array($this, 'add_media_tab'));

    // adds the html to the middmedia tab
    add_action('media_upload_middmedia', array($this, 'insert_media_upload'));
  
    // adds the javascript
    add_action('admin_print_scripts', array($this, 'js_admin_header'));
    
    // adds the ajax function handlers
    add_action('wp_ajax_middmedia_get_user_files', array($this, 'get_user_files'));
    add_action('wp_ajax_middmedia_del_user_files', array($this, 'del_user_files'));
  }
  
  function add_middmedia_tab($tabs) {
    $tabs['middmedia'] = __('MiddMedia');
    return $tabs;
  }
  
  function add_media_tab() {
    add_filter('media_upload_tabs', array($this, 'add_middmedia_tab'));
  }

  function insert_media_upload() {
    global $wp_version;
    
    if ($wp_version < '2.6') {
      add_action('admin_head_middmedia_media_upload', 'media_admin_css');
    } else {
      wp_admin_css('media');
    }
    
    media_upload_header();
    
    return wp_iframe('middmedia_media_upload', $this);
  }
  
  function get_user_files() {
    global $current_user;
    get_currentuserinfo();
    
    $client = new SoapClient(MIDDMEDIA_SOAP_WSDL);
    $files = $client->serviceGetVideos($current_user->user_login, 'blogs', MIDDMEDIA_SOAP_KEY, $_POST['directory']);
  
    $xml = "<data>";

    foreach($files as $file) {
      $labels = array('B', 'KB', 'MB', 'GB', 'TB');
      for ($i = 0; $file['size'] >= 1024 && $i < (count($labels) - 1); $file['size'] /= 1024, $i++);
      $size = round($file['size'], 2) . ' ' . $labels[$i];

      $xml .= "<file ";
      $xml .= "name=\"" . $file['name'] . "\" ";
      $xml .= "httpurl=\"" . $file['httpurl'] . "\" ";
      $xml .= "rtmpurl=\"" . $file['rtmpurl'] . "\" ";
      $xml .= "mimetype=\"" . $file['mimetype'] . "\" ";
      $xml .= "size=\"" . $size . "\" ";
      $xml .= "date=\"" . date('D M j, Y g:i a', strtotime($file['date'])) . "\" ";
      $xml .= "creator=\"" . $file['creator'] . "\" ";
      $xml .= "fullframeurl=\"" . $file['fullframeurl'] . "\" ";
      $xml .= "thumburl=\"" . $file['thumburl'] . "\" ";
      $xml .= "splashurl=\"" . $file['splashurl'] . "\" ";
      $xml .= "/>";
    }
    
    $xml .= "</data>";
      
    header("Content-type: text/xml");
    die($xml);
  }
  
  function del_user_files() {
    global $current_user;
    get_currentuserinfo();
          
    $xml = "<data><response>";
    
    $response = "Success";
    
    try {
      $client = new SoapClient(MIDDMEDIA_SOAP_WSDL);
      $files = $client->serviceDelVideo($current_user->user_login, 'blogs', MIDDMEDIA_SOAP_KEY, $_POST['directory'], $_POST['file']);
    } catch(Exception $ex) {
      $response = $ex->faultstring;
    }
    
    $xml .= $response. "</response></data>";
    
    header("Content-type: text/xml");
    die($xml);
  }
  
  function js_admin_header() {
  
    wp_print_scripts(array('jquery'));
  ?>
    <script type="text/javascript">
    //<![CDATA[
    
      function middmediaShowFiles() {
        var middmediaResponse = document.getElementById("middmediaResponse");
        middmediaResponse.style.display = "none";

        var middmediaDirectory = document.getElementById("middmediaDirectory");
        var middmediaDirectoryIndex = middmediaDirectory.selectedIndex;
        var middmediaDirectoryValue = middmediaDirectory.options[middmediaDirectoryIndex].value;

	var tbody = document.getElementById("middmediaFilesTableBody");
	tbody.parentNode.replaceChild(tbody.cloneNode(false), tbody);
	var tbody = document.getElementById("middmediaFilesTableBody");
	var tr = document.createElement("TR");

	var th_use = document.createElement("TH");
	th_use.innerHTML = "&nbsp;";
	tr.appendChild(th_use);

	var th_icon = document.createElement("TH");
	th_icon.innerHTML = "&nbsp;";
	tr.appendChild(th_icon);

	var th_name = document.createElement("TH");
	th_name.innerHTML = "Name";
	tr.appendChild(th_name);

	var th_type = document.createElement("TH");
	th_type.innerHTML = "Type";
	tr.appendChild(th_type);

	var th_size = document.createElement("TH");
	th_size.innerHTML = "Size";
	tr.appendChild(th_size);

	var th_date = document.createElement("TH");
	th_date.innerHTML = "Date Added";
	tr.appendChild(th_date);

	var th_user = document.createElement("TH");
	th_user.innerHTML = "User";
	tr.appendChild(th_user);

	var th_delete = document.createElement("TH");
	th_delete.innerHTML = "&nbsp;";
	tr.appendChild(th_delete);

	tbody.appendChild(tr);

        var ajax = jQuery.ajax({
          type: "POST",
          url: "admin-ajax.php",
          dataType: "xml",
          data: "action=middmedia_get_user_files&directory=" + middmediaDirectoryValue + "&post_id=" + jQuery("#post_ID").val(),
          success: function(xml, status) {
            document.getElementById("middmediaFiles").style.display = "";
            jQuery(xml).find('file').each(function(i) {
	      var name = jQuery(this).attr("name");
              var tr = document.createElement("TR");
              var td_button = document.createElement("TD");
              var td_button_input = document.createElement("INPUT");
              td_button_input.className = "button";
              td_button_input.type = "button";
              td_button_input.onclick = function() { middmediaInsertFiles(name); }
	      td_button_input.value = "use";
              td_button.appendChild(td_button_input);
              tr.appendChild(td_button);

              var td_icon = document.createElement("TD");
	      if (jQuery(this).attr("thumburl")) {
	      	      var img = document.createElement("IMG");
		      img.src = jQuery(this).attr("thumburl");
		      img.height = "100";
		      img.width = "100";
		      td_icon.appendChild(img);
	      } else {
		td_icon.innerHTML = "&nbsp;";
	      }
	      tr.appendChild(td_icon);

              var td_name = document.createElement("TD");
	      td_name.innerHTML = jQuery(this).attr("name");
              tr.appendChild(td_name);

              var td_type = document.createElement("TD");
	      td_type.innerHTML = jQuery(this).attr("mimetype");
              tr.appendChild(td_type);

              var td_size = document.createElement("TD");
	      td_size.innerHTML = jQuery(this).attr("size");
              tr.appendChild(td_size);

              var td_date = document.createElement("TD");
	      td_date.innerHTML = jQuery(this).attr("date");
              tr.appendChild(td_date);

              var td_user = document.createElement("TD");
	      if (jQuery(this).attr("creator")) {
	      	      td_user.innerHTML = jQuery(this).attr("creator");
	      } else {
		      td_user.innerHTML = "&nbsp;";
	      }
              tr.appendChild(td_user);

              var td_delete = document.createElement("TD");
	      var td_delete_input = document.createElement("INPUT");
	      td_delete_input.className = "button";
	      td_delete_input.type = "button";
	      td_delete_input.onclick = function() { middmediaShowDelete(name); }
	      td_delete_input.value = "delete";
	      td_delete.appendChild(td_delete_input);
              tr.appendChild(td_delete);

	      var middmediaFilesTable = document.getElementById("middmediaFilesTableBody");
	      middmediaFilesTable.appendChild(tr);
            });
          }
        });
      }
      
      function middmediaInsertFiles(file) {
        var middmediaResponse = document.getElementById("middmediaResponse");
        middmediaResponse.style.display = "none";

        var middmediaDirectory = document.getElementById("middmediaDirectory");
        var middmediaDirectoryIndex = middmediaDirectory.selectedIndex;
        var middmediaDirectoryValue = middmediaDirectory.options[middmediaDirectoryIndex].value;

        var middmediaCurrentUser = document.getElementById("middmediaCurrentUser").innerHTML;
        
        var html = "[middmedia " + middmediaCurrentUser + " " + middmediaDirectoryValue + " " + file + "]";
        var win = window.dialogArguments || opener || parent || top;
        win.send_to_editor(html);
        return false;
      }
      
      function middmediaShowDelete(fileName) {
        var middmediaResponse = document.getElementById("middmediaResponse");
        middmediaResponse.style.display = "none";

        var div = document.getElementById("deleteConfirmation");
        
        div.innerHTML = "<h3>Delete Confirmation</h3>Are you sure that you want to delete " + fileName + "? If you do, any page using it will break!<br /><br />";

	var yes = document.createElement("INPUT");
	yes.className = "button";
	yes.type = "button";
	yes.onclick = function() { middmediaDeleteFile(fileName); }
	yes.value = "Yes";
	div.appendChild(yes);

	var no = document.createElement("INPUT");
	no.className = "button";
	no.type = "button";
	no.onclick = function() { middmediaHideDelete(); }
	no.value = "No";
	div.appendChild(no);
      }
      
      function middmediaHideDelete() {
        var middmediaResponse = document.getElementById("middmediaResponse");
        middmediaResponse.style.display = "none";

        document.getElementById("deleteConfirmation").innerHTML = "";
      }
      
      function middmediaDeleteFile(fileName) {
        var middmediaResponse = document.getElementById("middmediaResponse");
        middmediaResponse.style.display = "none";

        var middmediaDirectory = document.getElementById("middmediaDirectory");
        var middmediaDirectoryIndex = middmediaDirectory.selectedIndex;
        var middmediaDirectoryValue = middmediaDirectory.options[middmediaDirectoryIndex].value;
      
        var ajax = jQuery.ajax({
          type: "POST",
          url: "admin-ajax.php",
          dataType: "xml",
          data: "action=middmedia_del_user_files&directory=" + middmediaDirectoryValue + "&file=" + fileName + "&post_id=" + jQuery("#post_ID").val(),
          success: function(xml, status) {
            jQuery(xml).find('response').each(function(i) {
              var middmediaResponse = document.getElementById("middmediaResponse");
              middmediaResponse.style.display = "";
              middmediaResponse.innerHTML = "<h3>Result</h3>" + jQuery(this).text();
              document.getElementById("deleteConfirmation").innerHTML = "";
	      middmediaShowFiles();
            });
          }
        });
      }
      
    //]]>
    </script>
    <style>
      #middmediaFilesTable th {
        font-size: 12px;
        font-weight: bold;
      }

      #middmediaFilesTable th, #middmediaFilesTable td {
        border-left: 1px solid black;
	border-top: 1px solid black;
      }

      #middmediaFilesTable {
        border-bottom: 1px solid black;
	border-right: 1px solid black;
      }
    </style>
  <?php
  }
}

function middmedia_media_upload($middmedia) {
  
  $submiturl = "/wp-content/plugins/middmedia/upload.php";
  
  global $current_user;
  get_currentuserinfo();
  
  $client = new SoapClient(MIDDMEDIA_SOAP_WSDL);
  
  $dirinfo = $client->serviceGetDirs($current_user->user_login, 'blogs', MIDDMEDIA_SOAP_KEY);
  $directories = array();
  foreach($dirinfo as $directory) {
    $directories[] = $directory['name'];
  }
  
  // Find media and insert it into posts
  echo "<form method='post' enctype='multipart/form-data' id='file-form' action='{$submiturl}' class='media-upload-form type-form validate'>";

  echo "<p>Welcome, <span
  id='middmediaCurrentUser'>{$current_user->user_login}</span>! Need help? View the <a href=\"http://go.middlebury.edu/middmedia?help\" target=\"_blank\">MiddMedia Documentation</a>.</p>";
  
  echo "<div id='deleteConfirmation'></div>";
  
  if(isset($_GET['response'])) {
    echo "<div id='middmediaResponse'><h3>Result</h3>" . htmlentities($_GET['response']) . "</div>";
  } else {
    echo "<div id='middmediaResponse' style='display: none;'></div>";
  }

  echo "<h3>Insert Media</h3>";
  
  echo "<p><select id='middmediaDirectory' name='middmediaDirectory'>";
  
  foreach($directories as $directory) {
    echo "<option value='{$directory}'>{$directory}</option>";
  }
  
  echo "</select>";

  echo "<input class='button' type='button' name='show' value='Show Files' onclick='middmediaShowFiles()' /></p>"; 
  
  echo "<p id='middmediaFiles' style='display:none;'>";

  echo "<table id='middmediaFilesTable' width='600' cellpadding='1' cellspacing='0'><tbody id='middmediaFilesTableBody'>";
  echo "</tbody></table></p>";

  // Upload new media
  echo "<hr /><h3>Upload Media</h3>";

  echo "<p>For large files or multi-file uploads, use the central <a href=\"http://go.middlebury.edu/middmedia\" target=\"_blank\">MiddMedia Service</a>.</p>";

  $types = $client->serviceGetTypes($current_user->user_login, 'blogs', MIDDMEDIA_SOAP_KEY);
  echo "<p>Allowed file types: " . implode(", ", $types) . "</p>";

  echo "<input name='post_id' id='post_id' value='{$_GET['post_id']}' type='hidden' />";
   
  echo "<input name='middmediaUploadUsername' value='{$current_user->user_login}' type='hidden' />";
    
  echo "<input id='tab' name='tab' value='middmedia' type='hidden' />";

  $uri = $_SERVER['REQUEST_URI'];
  if (strpos($uri, '&response') !== false) {
    $uri = substr($uri, 0, strpos($uri, '&response'));
  }
    
  echo "<input name='_wp_http_referer' value='{$_SERVER['REQUEST_URI']}' type='hidden' />";

  echo "<p><select id='middmediaUploadDirectory' name='middmediaUploadDirectory'>";
  
  foreach($directories as $directory) {
    echo "<option value='{$directory}'>{$directory}</option>";
  }
  
  echo "</select>";
  
  echo "<input id='async-upload' name='async-upload' type='file' />";
 
  echo "<input class='button' type='submit' name='html-upload' value='Upload File' /></p><hr />";

  // Quotas
  echo "<h3>Quota</h3><table border='1' cellpadding='5'><tr><td>Directory</td><td>Used</td><td>Remaining</td></tr>";
  
  foreach($dirinfo as $dir) {
    echo "<tr><td>{$dir['name']}</td><td>" . 
	 bytes_to_readable($dir['bytesused']) .
	 "</td><td>" .
	 bytes_to_readable($dir['bytesavailable']) .
	 "</td></tr>";
  }
  
  echo "</table>";

  echo "<br class='clear' /></form><br />";
}

function bytes_to_readable($bytes) {
      $labels = array('B', 'KB', 'MB', 'GB', 'TB');
      for ($i = 0; $bytes >= 1024 && $i < (count($labels) - 1); $bytes /= 1024, $i++);
      return round($bytes, 2) . ' ' . $labels[$i];
}

/**
 * Parses a formatted string and returns embed code for a video or audio player. Structure is as follows:
 * 	[middmedia user directory file]
 * user: the user who added the video to the post
 * directory: the user or group name for the MiddMedia directory containing the file
 * file: the name of the video or audio file to play
 */

function middmedia_plugin_callback($match)
{
  $customVideoHeight = $GLOBALS["customVideoHeight"];
  $customVideoWidth = $GLOBALS["customVideoWidth"];

  if (!isset($match[1])) {
    return middmedia_playback_error();
  }
  
  $tags = explode(" ", $match[1]);
  $service = substr($match[0], 1, strpos($match[0], " ") - 1);

  if (
     ($service == "middtube" && count($tags) < 2) ||
     ($service == "middmedia" && count($tags) < 3)) {
    return middmedia_playback_error();
  }
  
  $output = "";

  try {
    $client = new SoapClient(MIDDMEDIA_SOAP_WSDL);

    switch($service) {
      case "middtube":
       $filename = $tags[1];
       for($i = 2; $i < count($tags); $i++) {
         $filename .= " " . $tags[$i];
       }

       if (strpos($filename, '.') === FALSE) {
	  $filename .= ".flv";
       }

       $ext = strpos($filename, ':');
       if ($ext !== FALSE) {
	  $filename = substr($filename, $ext + 1, strlen($filename) - $ext);
       }

       $media = $client->serviceGetVideo($tags[0], 'blogs', MIDDMEDIA_SOAP_KEY, $tags[0], $filename);
       break;
      case "middmedia":
       $filename = $tags[2];
       for($i = 3; $i < count($tags); $i++) { 
         $filename .= " " . $tags[$i];
       }

       $media = $client->serviceGetVideo($tags[0], 'blogs', MIDDMEDIA_SOAP_KEY, $tags[1], trim($filename));
       break;
      default:
       return middmedia_playback_error();
    }
    $output = $media['embedcode'];
  } catch (Exception $ex) {
    $output = $ex->faultstring;
  }

  if (isset($customVideoHeight) && intval($customVideoHeight)) {
    $output = preg_replace("/height=\"\d+\"/", "height=\"$customVideoHeight\"", $output);
    $output = preg_replace("/width=\"\d+\"/", "width=\"$customVideoWidth\"", $output);
  }

  $heights = array();
  preg_match("/height=\"(\d+)\"/", $output, $heights);

  $widths = array();
  preg_match("/width=\"(\d+)\"/", $output, $widths);

  if (intval($heights[1]) < 200 || intval($widths[1]) < 200) {
     $output = "<div id=\"youtubevideo\"
     style=\"width:"
     . $widths[1] . "px;height:" . ($heights[1]-30)
     . "px;overflow:hidden;\">" . $output . "</div>";

     $output = preg_replace("/embed\s/", "embed wmode=\"transparent\"", $output);
  }

  if (isset($GLOBALS["textWrap"]) && $GLOBALS["textWrap"] == TRUE) {
     $output = "<div id=\"textwrapdiv\"
     style=\"margin-top:5px;margin-right:10px;position:relative;float:left;overflow:hidden;display:block;\">"
     . $output . "</div>";
  }

  // Enclosure support.
  // Added by Adam Franco on 2009-02-27
  // 
  // We will store the data for the MiddMedia files in the post in a global array that
  // we will reference later when rendering the RSS feed via our 'rss2_item' action hook
  // that calls our middmedia_add_enclosures() function.
  //
  // The other way to accomplish this result would be to add a custom field to the post
  // called 'enclosure' (that contains this data) at the time the post is saved. The downside
  // of that approach however is that updates to the media (such as URL or size changes)
  // would not be automatically passed through to the feed. This approach keeps that data
  // out of the WordPress database.
  if (!isset($GLOBALS['middmedia_enclosures']))
    $GLOBALS['middmedia_enclosures'] = array();

  $post = get_post();
  $post_id = $post->ID;
  if (!isset($GLOBALS['middmedia_enclosures'][$post_id]))
    $GLOBALS['middmedia_enclosures'][$post_id] = array();

  $GLOBALS['middmedia_enclosures'][$post_id][$media['name']] = array (
      'url'    => $media['httpurl'],
      'length' => intval($media['size']),
      'type'   => $media['mimetype']
    );
  // -- END Enclosure support --

  return $output;
}

function middmedia_playback_error() {
  return "MIDDMEDIA ERROR: Cannot play this file. Contact the blog administrator";
}

function middmedia_plugin($content)
{
  return (preg_replace_callback(MIDDMEDIA_REGEXP, 'middmedia_plugin_callback', $content));
}

/**
 * Print out an enclosure tag for each of our MiddMedia files.
 * Added for enclosure support.
 *
 * @since 2009-02-27 
 */
function middmedia_add_enclosures($post_id) {
  $enclosures = $GLOBALS['middmedia_enclosures'][get_post()->ID];
  foreach ($enclosures as $name => $data) {
  	print "\n\t\t<enclosure url=\"".$data['url']."\" length=\"".$data['length']."\" type=\"".$data['type']."\" />";
  }
  if (count($enclosures))
    print "\n";
}

add_filter('the_content', 'middmedia_plugin');
add_filter('the_content_rss', 'middmedia_plugin');
add_filter('comment_text', 'middmedia_plugin');
add_action('rss2_item', 'middmedia_add_enclosures');  // Added for enclosure support.


