MiddMedia Plugin for WordPress
=================================

About
------------------
The MiddMedia WordPress plugin adds a new media-browser tab to the WordPress 'add-media'
screen that allows users to insert audio and video hosted in the MiddMedia system.

Additionally, the plugin inserts the correct embed-code for the media files when
displaying posts.


Author and Contributors
---------------------
Author: Ian McBride
Author URI: http://blogs.middlebury.edu/imcbride

Contributers:
Adam Franco
Brendan Owens


Copyright and License
---------------------
Copyright &copy; 2009, The President and Fellows of Middlebury College
License: http://www.gnu.org/copyleft/gpl.html GNU General Public License (GPL)


Downloads
---------------------
Please see the MiddMedia space on Assembla for details:

	https://www.assembla.com/spaces/MiddMedia/

For the latest and archived versions, see:

	http://github.com/adamfranco/middmedia-wordpressplugin/downloads


Documentation
---------------------
Developer documentation can be found in the MiddMedia space on Assembla:

	https://www.assembla.com/wiki/show/MiddMedia/WordPress_Plugin

End-user documentation can be found at:

	https://mediawiki.middlebury.edu/wiki/LIS/MiddMedia


Installation
---------------------
1. Rename the directory containing this file to "middmedia".
2. Copy the directory containing this file to 
        wordpress/wp-content/plugins/
3. Enable the plugin in your blog.


Bug Tracker
---------------------
https://www.assembla.com/spaces/MiddMedia/tickets


Current Version Notes
---------------------
This is the first release of this plugin.


Change Log
---------------------
  
Updated: 2009-02-05 (Brendan Smith) Added code to set video dimensions to global variables set in
the wordpress template. Makes video fill page in the regular single page view of Middtube.

Updated: 2009-02-27 (Adam Franco) Added support for writing <enclosure/> tags to the RSS feeds
in order to support podcasting.